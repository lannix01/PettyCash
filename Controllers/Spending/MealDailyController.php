<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\MealDailySpending;
use App\Modules\PettyCash\Models\MealPayment;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Modules\PettyCash\Support\PettyDatabase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MealDailyController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return redirect()
                ->route('petty.meals.index')
                ->with('error', 'Meal Daily Bills is not ready yet. Please run migrations first.');
        }

        $respondentId = $request->integer('respondent_id') ?: null;
        $from = $request->query('from');
        $to = $request->query('to');
        $q = trim((string) $request->query('q', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'date_desc')));
        $allowedSorts = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date_desc';
        }
        $status = strtolower(trim((string) $request->query('status', 'all')));
        if (!in_array($status, ['all', 'paid', 'unpaid'], true)) {
            $status = 'all';
        }

        $baseQuery = MealDailySpending::query()
            ->when($from, fn ($q) => $q->whereDate('spending_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('spending_date', '<=', $to))
            ->when($status === 'paid', fn ($q) => $q->whereNotNull('meal_payment_id'))
            ->when($status === 'unpaid', fn ($q) => $q->whereNull('meal_payment_id'))
            ->when($respondentId, function ($q) use ($respondentId) {
                $q->where(function ($w) use ($respondentId) {
                    $w->where('respondent_id', $respondentId)
                        ->orWhereHas('respondents', fn ($r) => $r->where('petty_respondents.id', $respondentId));
                });
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('notes', 'like', '%' . $q . '%')
                        ->orWhereHas('respondent', fn ($respondent) => $respondent
                            ->where('name', 'like', '%' . $q . '%')
                            ->orWhere('phone', 'like', '%' . $q . '%'))
                        ->orWhereHas('respondents', fn ($respondents) => $respondents
                            ->where('petty_respondents.name', 'like', '%' . $q . '%')
                            ->orWhere('petty_respondents.phone', 'like', '%' . $q . '%'))
                        ->orWhereHas('payment', fn ($payment) => $payment
                            ->where('reference', 'like', '%' . $q . '%')
                            ->orWhere('receiver_name', 'like', '%' . $q . '%')
                            ->orWhere('receiver_phone', 'like', '%' . $q . '%')
                            ->orWhere('notes', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $baseQuery->orderBy('spending_date')->orderBy('id'),
            'amount_desc' => $baseQuery->orderByDesc('amount')->orderByDesc('spending_date')->orderByDesc('id'),
            'amount_asc' => $baseQuery->orderBy('amount')->orderByDesc('spending_date')->orderByDesc('id'),
            default => $baseQuery->orderByDesc('spending_date')->orderByDesc('id'),
        };

        $dailySpendings = (clone $baseQuery)
            ->with(['respondent:id,name', 'respondents:id,name', 'payment:id,date,reference'])
            ->paginate(25)
            ->withQueryString();

        $totalLogged = round((float) (clone $baseQuery)->sum('amount'), 2);
        $totalUnpaid = round((float) (clone $baseQuery)->whereNull('meal_payment_id')->sum('amount'), 2);

        $payments = MealPayment::query()
            ->with([
                'respondent:id,name',
                'batch:id,batch_no',
                'dailySpendings:id,meal_payment_id,respondent_id,spending_date,amount',
                'dailySpendings.respondent:id,name',
                'dailySpendings.respondents:id,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $respondents = Respondent::query()
            ->selectable()
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();
        $serviceSpentNet = $this->serviceSpentNetTotal();
        $actualBalance = round($totalBalance - $serviceSpentNet, 2);

        $selectedDailyIds = $this->normalizeIdList((array) old('daily_ids', []));
        $selectionStats = [
            'entries_count' => 0,
            'days_count' => 0,
            'amount' => 0.0,
            'range_from' => null,
            'range_to' => null,
            'people' => [],
        ];

        if (!empty($selectedDailyIds)) {
            $selectionStats = $this->selectedDailyStats($selectedDailyIds);
        }

        return view('pettycash::spendings.meals.daily', compact(
            'dailySpendings',
            'payments',
            'respondents',
            'respondentId',
            'from',
            'to',
            'status',
            'totalLogged',
            'totalUnpaid',
            'batches',
            'totalBalance',
            'serviceSpentNet',
            'actualBalance',
            'selectionStats',
            'selectedDailyIds',
            'q',
            'sort'
        ));
    }

    public function calculate(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return response()->json([
                'ok' => false,
                'message' => 'Meal Daily Bills is not ready yet. Run migrations first.',
            ], 503);
        }

        $data = $request->validate([
            'daily_ids' => ['required', 'array', 'min:1'],
            'daily_ids.*' => ['integer', 'exists:petty_meal_daily_spendings,id'],
        ]);

        $ids = $this->normalizeIdList((array) ($data['daily_ids'] ?? []));
        if (empty($ids)) {
            return response()->json([
                'ok' => false,
                'message' => 'No valid records selected.',
            ], 422);
        }

        $stats = $this->selectedDailyStats($ids);

        return response()->json([
            'ok' => true,
            'entries_count' => $stats['entries_count'],
            'days_count' => $stats['days_count'],
            'amount' => $stats['amount'],
            'range_from' => $stats['range_from'],
            'range_to' => $stats['range_to'],
            'people' => $stats['people'],
            'has_rows' => $stats['entries_count'] > 0,
        ]);
    }

    public function storeDaily(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return back()
                ->withErrors(['spending_date' => 'Meal Daily Bills is not ready yet. Please run migrations first.'])
                ->withInput();
        }

        $data = $request->validate([
            'range_from' => ['required', 'date'],
            'range_to' => ['required', 'date'],
            'day_entries' => ['required', 'array', 'min:1'],
            'day_entries.*.date' => ['required', 'date'],
            'day_entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'day_entries.*.notes' => ['required', 'string', 'max:255'],
            'day_entries.*.respondent_ids' => ['required', 'array', 'min:1'],
            'day_entries.*.respondent_ids.*' => ['integer', 'exists:petty_respondents,id'],
        ]);

        $from = Carbon::parse($data['range_from'])->startOfDay();
        $to = Carbon::parse($data['range_to'])->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $expectedDates = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $expectedDates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $rawEntries = collect($data['day_entries'] ?? [])
            ->map(function (array $entry) {
                return [
                    'date' => Carbon::parse($entry['date'])->toDateString(),
                    'amount' => round((float) ($entry['amount'] ?? 0), 2),
                    'notes' => trim((string) ($entry['notes'] ?? '')),
                    'respondent_ids' => $this->normalizeIdList((array) ($entry['respondent_ids'] ?? [])),
                ];
            });

        $enteredDates = $rawEntries->pluck('date')->all();
        if ($enteredDates !== $expectedDates) {
            return back()->withErrors([
                'day_entries' => 'Each day in the selected range must be listed once so the bill stays fully auditable.',
            ])->withInput();
        }

        $duplicateDate = collect($enteredDates)->duplicates()->first();
        if ($duplicateDate) {
            return back()->withErrors([
                'day_entries' => 'Duplicate day entry found for ' . $duplicateDate . '.',
            ])->withInput();
        }

        $respondentIds = $rawEntries
            ->pluck('respondent_ids')
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $respondentsById = Respondent::query()
            ->whereIn('id', $respondentIds)
            ->get()
            ->keyBy('id');

        foreach ($rawEntries as $index => $entry) {
            foreach ($entry['respondent_ids'] as $respondentId) {
                $respondent = $respondentsById->get((int) $respondentId);
                if (!$respondent || !$respondent->isSelectableForSpending()) {
                    return back()->withErrors([
                        "day_entries.$index.respondent_ids" => ($respondent?->name ?: 'A selected respondent') . ' is not active and cannot be assigned to a new daily bill.',
                    ])->withInput();
                }
            }
        }

        $createdRows = 0;

        PettyDatabase::transaction(function () use ($rawEntries, &$createdRows) {
            foreach ($rawEntries as $entry) {
                $row = MealDailySpending::query()->create([
                    'respondent_id' => null,
                    'spending_date' => $entry['date'],
                    'amount' => $entry['amount'],
                    'notes' => $entry['notes'],
                    'recorded_by' => auth('petty')->id(),
                ]);

                $row->respondents()->sync($entry['respondent_ids']);
                $createdRows++;
            }
        });

        return redirect()
            ->route('petty.meals.daily.index', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ])
            ->with('success', 'Daily meal bill saved for ' . $createdRows . ' day(s).');
    }

    public function edit(MealDailySpending $dailySpending)
    {
        return view('pettycash::spendings.meals.daily_edit', [
            'dailySpending' => $dailySpending->load('respondents:id,name,phone'),
            'respondents' => Respondent::query()
                ->where(function ($query) use ($dailySpending) {
                    $query->where('status', Respondent::STATUS_ACTIVE)
                        ->orWhereIn('id', $dailySpending->respondents->pluck('id')->all());
                })
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'status']),
        ]);
    }

    public function update(Request $request, MealDailySpending $dailySpending)
    {
        if ($dailySpending->meal_payment_id) {
            return back()->with('error', 'Paid daily bills cannot be edited directly. Edit the payment instead.');
        }

        $data = $request->validate([
            'spending_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['required', 'string', 'max:255'],
            'involved_respondent_ids' => ['required', 'array', 'min:1'],
            'involved_respondent_ids.*' => ['integer', 'exists:petty_respondents,id'],
        ]);

        $involvedIds = $this->normalizeIdList((array) ($data['involved_respondent_ids'] ?? []));
        $allowedIds = $dailySpending->respondents()->pluck('petty_respondents.id')->all();
        $invalidRespondent = Respondent::query()
            ->whereIn('id', $involvedIds)
            ->get()
            ->first(function (Respondent $respondent) use ($allowedIds) {
                return !$respondent->isSelectableForSpending()
                    && !in_array((int) $respondent->id, array_map('intval', $allowedIds), true);
            });
        if ($invalidRespondent) {
            return back()->withErrors([
                'involved_respondent_ids' => $invalidRespondent->name . ' is not active and cannot be newly assigned.',
            ])->withInput();
        }

        PettyDatabase::transaction(function () use ($dailySpending, $data, $involvedIds) {
            $dailySpending->update([
                'spending_date' => $data['spending_date'],
                'amount' => round((float) $data['amount'], 2),
                'notes' => $data['notes'],
            ]);

            $dailySpending->respondents()->sync($involvedIds);
        });

        return redirect()
            ->route('petty.meals.daily.index', [
                'from' => $data['spending_date'],
                'to' => $data['spending_date'],
            ])
            ->with('success', 'Daily meal bill updated.');
    }

    public function storePayment(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return back()
                ->withErrors(['daily_ids' => 'Meal Daily Bills is not ready yet. Please run migrations first.'])
                ->withInput();
        }

        $data = $request->validate([
            'daily_ids' => ['required', 'array', 'min:1'],
            'daily_ids.*' => ['integer', 'exists:petty_meal_daily_spendings,id'],

            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'date' => ['required', 'date'],
            'reference' => ['required', 'string', 'max:255'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
            'description_mode' => ['required', 'in:auto,manual'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        $selectedIds = $this->normalizeIdList((array) ($data['daily_ids'] ?? []));
        if (empty($selectedIds)) {
            return back()->withErrors(['daily_ids' => 'No valid daily records selected.'])->withInput();
        }

        $fee = round((float) ($data['transaction_cost'] ?? 0), 2);
        $allocator = app(FundsAllocatorService::class);

        try {
            PettyDatabase::transaction(function () use ($data, $selectedIds, $fee, $allocator) {
                $rows = $this->selectedDailyRows($selectedIds, true);
                if ($rows->count() !== count($selectedIds)) {
                    throw new \RuntimeException('Some selected records are already paid or unavailable. Refresh and try again.');
                }

                $stats = $this->statsFromRows($rows);
                $amount = $stats['amount'];
                if ($amount <= 0) {
                    throw new \RuntimeException('Selected records do not have a payable amount.');
                }

                if ($data['funding'] === 'auto') {
                    $required = $amount + $fee;
                    $available = $this->actualAvailableBalance($allocator);
                    if ($required > $available) {
                        throw new \RuntimeException(
                            'Insufficient available balance. Needed: ' . number_format($required, 2)
                            . ' Available: ' . number_format($available, 2)
                        );
                    }
                }

                $description = $this->resolveMealPaymentDescription($data, $stats);

                $spending = Spending::query()->create([
                    'batch_id' => null,
                    'type' => 'meal',
                    'sub_type' => 'lunch',
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => null,
                    'description' => $description,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int) $data['batch_id'] : null;
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                $payment = MealPayment::query()->create([
                    'spending_id' => $spending->id,
                    'respondent_id' => null,
                    'batch_id' => $spending->batch_id,
                    'range_from' => $stats['range_from'] ?? $data['date'],
                    'range_to' => $stats['range_to'] ?? $data['date'],
                    'days_count' => $stats['days_count'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'reference' => $data['reference'],
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'] ?? null,
                    'notes' => $data['notes'],
                    'recorded_by' => auth('petty')->id(),
                ]);

                MealDailySpending::query()
                    ->whereIn('id', $rows->pluck('id')->all())
                    ->update(['meal_payment_id' => $payment->id]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['daily_ids' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('petty.meals.daily.index')
            ->with('success', 'Meal payment recorded and balance updated.');
    }

    public function editPayment(MealPayment $payment)
    {
        $allocator = app(FundsAllocatorService::class);
        $stats = $this->statsFromRows($payment->dailySpendings()->with(['respondent:id,name', 'respondents:id,name'])->get());

        return view('pettycash::spendings.meals.payment_edit', [
            'payment' => $payment->load('batch:id,batch_no', 'spending', 'dailySpendings.respondent:id,name', 'dailySpendings.respondents:id,name'),
            'batches' => $allocator->batchesWithNetAvailable(),
            'descriptionPreview' => $this->autoMealPaymentDescription($stats, round((float) $payment->amount, 2)),
        ]);
    }

    public function updatePayment(Request $request, MealPayment $payment)
    {
        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:petty_batches,id'],
            'date' => ['required', 'date'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['required', 'numeric', 'min:0'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
            'description_mode' => ['required', 'in:auto,manual'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $fee = round((float) $data['transaction_cost'], 2);
        $allocator = app(FundsAllocatorService::class);
        $stats = $this->statsFromRows($payment->dailySpendings()->with(['respondent:id,name', 'respondents:id,name'])->get());
        $description = $this->resolveMealPaymentDescription($data, $stats, $amount);

        PettyDatabase::transaction(function () use ($payment, $data, $amount, $fee, $allocator, $description) {
            $payment->update([
                'batch_id' => (int) $data['batch_id'],
                'date' => $data['date'],
                'reference' => $data['reference'],
                'amount' => $amount,
                'transaction_cost' => $fee,
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'notes' => $data['notes'],
            ]);

            if ($payment->spending_id) {
                $spending = Spending::query()->lockForUpdate()->find($payment->spending_id);
                if ($spending) {
                    $spending->update([
                        'reference' => $data['reference'],
                        'amount' => $amount,
                        'transaction_cost' => $fee,
                        'date' => $data['date'],
                        'description' => $description,
                    ]);

                    $allocator->forceAllocateToBatch($spending, $amount, $fee, (int) $data['batch_id']);
                }
            }
        });

        return redirect()
            ->route('petty.meals.daily.index')
            ->with('success', 'Meal payment updated.');
    }

    public function destroy(MealDailySpending $dailySpending)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);

        if ($dailySpending->meal_payment_id) {
            return back()->with('error', 'Delete the linked payment first before deleting this bill row.');
        }

        $dailySpending->respondents()->detach();
        $dailySpending->delete();

        return redirect()->route('petty.meals.daily.index')->with('success', 'Daily meal bill deleted.');
    }

    public function destroyPayment(MealPayment $payment)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);

        PettyDatabase::transaction(function () use ($payment) {
            MealDailySpending::query()
                ->where('meal_payment_id', $payment->id)
                ->update(['meal_payment_id' => null]);

            if ($payment->spending_id) {
                \App\Modules\PettyCash\Models\SpendingAllocation::query()
                    ->where('spending_id', $payment->spending_id)
                    ->delete();
                Spending::query()->whereKey($payment->spending_id)->delete();
            }

            $payment->delete();
        });

        return redirect()->route('petty.meals.daily.index')->with('success', 'Meal payment deleted.');
    }

    /**
     * @param array<int,mixed> $ids
     * @return array<int,int>
     */
    private function normalizeIdList(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            $v = (int) $id;
            if ($v > 0) {
                $normalized[$v] = $v;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param array<int,int> $ids
     */
    private function selectedDailyRows(array $ids, bool $lockForUpdate = false): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $query = MealDailySpending::query()
            ->with(['respondent:id,name', 'respondents:id,name'])
            ->whereIn('id', $ids)
            ->whereNull('meal_payment_id')
            ->orderBy('spending_date')
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * @param array<int,int> $ids
     * @return array{entries_count:int,days_count:int,amount:float,range_from:?string,range_to:?string,people:array<int,string>}
     */
    private function selectedDailyStats(array $ids): array
    {
        $rows = $this->selectedDailyRows($ids, false);

        return $this->statsFromRows($rows);
    }

    /**
     * @return array{entries_count:int,days_count:int,amount:float,range_from:?string,range_to:?string,people:array<int,string>}
     */
    private function statsFromRows(Collection $rows): array
    {
        $entriesCount = (int) $rows->count();
        $amount = round((float) $rows->sum('amount'), 2);

        $dates = $rows
            ->pluck('spending_date')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->values();

        $rangeFrom = $dates->isNotEmpty() ? $dates->min() : null;
        $rangeTo = $dates->isNotEmpty() ? $dates->max() : null;
        $daysCount = (int) $dates->unique()->count();

        $peopleMap = [];
        foreach ($rows as $row) {
            if (!empty($row->respondent?->name)) {
                $peopleMap[$row->respondent->name] = $row->respondent->name;
            }

            foreach ($row->respondents as $person) {
                if (!empty($person->name)) {
                    $peopleMap[$person->name] = $person->name;
                }
            }
        }

        return [
            'entries_count' => $entriesCount,
            'days_count' => $daysCount,
            'amount' => $amount,
            'range_from' => $rangeFrom,
            'range_to' => $rangeTo,
            'people' => array_values($peopleMap),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @param array{entries_count:int,days_count:int,amount:float,range_from:?string,range_to:?string,people:array<int,string>} $stats
     */
    private function resolveMealPaymentDescription(array $data, array $stats, ?float $overrideAmount = null): string
    {
        $mode = strtolower(trim((string) ($data['description_mode'] ?? '')));
        if ($mode === 'manual') {
            $description = trim((string) ($data['description'] ?? ''));
            if ($description === '') {
                throw ValidationException::withMessages([
                    'description' => 'Type a manual description or choose auto description.',
                ]);
            }

            return $description;
        }

        return $this->autoMealPaymentDescription($stats, $overrideAmount ?? (float) ($stats['amount'] ?? 0));
    }

    /**
     * @param array{entries_count:int,days_count:int,amount:float,range_from:?string,range_to:?string,people:array<int,string>} $stats
     */
    private function autoMealPaymentDescription(array $stats, float $amount): string
    {
        $people = array_values(array_filter((array) ($stats['people'] ?? [])));
        $peopleLabel = !empty($people) ? implode(', ', array_slice($people, 0, 3)) : 'selected respondents';
        if (count($people) > 3) {
            $peopleLabel .= ' +' . (count($people) - 3) . ' more';
        }

        $range = trim(implode(' to ', array_filter([
            (string) ($stats['range_from'] ?? ''),
            (string) ($stats['range_to'] ?? ''),
        ])));

        $parts = [
            'Meal bill for ' . $peopleLabel,
            ($range !== '' ? ('for ' . $range) : null),
            ((int) ($stats['days_count'] ?? 0) > 0 ? ((int) $stats['days_count'] . ' day(s)') : null),
            'total ' . number_format($amount, 2, '.', ''),
        ];

        return substr(implode(', ', array_filter($parts)), 0, 255);
    }

    private function mealDailyTablesReady(): bool
    {
        static $ready = null;

        if ($ready === null) {
            $ready = PettyDatabase::schema()->hasTable('petty_meal_daily_spendings')
                && PettyDatabase::schema()->hasTable('petty_meal_payments')
                && PettyDatabase::schema()->hasTable('petty_meal_daily_respondents');
        }

        return $ready;
    }

    private function serviceSpentNetTotal(): float
    {
        if (!PettyDatabase::schema()->hasTable('petty_bike_services')) {
            return 0.0;
        }

        $total = (float) PettyDatabase::table('petty_bike_services')
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost,0)),0) as t')
            ->value('t');

        return round($total, 2);
    }

    private function actualAvailableBalance(FundsAllocatorService $allocator): float
    {
        $net = (float) $allocator->totalNetBalance();
        $serviceSpent = $this->serviceSpentNetTotal();

        return round($net - $serviceSpent, 2);
    }
}
