<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TabularExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BikeController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $sub = $request->query('sub_type');
        $batchId = $request->query('batch_id');
        $q = trim((string) $request->query('q', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'date_desc')));
        $allowedSorts = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date_desc';
        }

        $query = Spending::with(['respondent', 'bike', 'batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->when($sub, fn($q) => $q->where('sub_type', $sub))
            //  batch filter must look at allocations (supports split)
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhere('particulars', 'like', '%' . $q . '%')
                        ->orWhereHas('bike', fn ($bike) => $bike->where('plate_no', 'like', '%' . $q . '%')->orWhere('model', 'like', '%' . $q . '%'))
                        ->orWhereHas('respondent', fn ($respondent) => $respondent->where('name', 'like', '%' . $q . '%')->orWhere('phone', 'like', '%' . $q . '%'))
                        ->orWhereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $query->orderBy('date')->orderBy('id'),
            'amount_desc' => $query->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $query->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $query->orderByDesc('date')->orderByDesc('id'),
        };

        $spendings = $query->paginate(20)->withQueryString();

        $total = (float) (clone $query)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost, 0)), 0) as total_amount')
            ->value('total_amount');

        $batches = Batch::orderByDesc('id')->limit(50)->get();

        return view('pettycash::spendings.bikes.index', compact('spendings', 'total', 'from', 'to', 'sub', 'batchId', 'batches', 'q', 'sort'));
    }

    public function create(Request $request)
    {
        $allocator = app(FundsAllocatorService::class);

        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $bikes = Bike::query()
            ->where('status', Bike::STATUS_ACTIVE)
            ->orderBy('plate_no')
            ->get();
        $respondents = Respondent::query()
            ->selectable()
            ->orderBy('name')
            ->get();

        $prefBatchId = $request->query('batch_id');
        $prefBikeId = $request->query('bike_id');

        return view('pettycash::spendings.bikes.create', compact(
            'batches', 'bikes', 'respondents', 'prefBatchId', 'prefBikeId', 'totalBalance'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'sub_type' => ['required', 'in:fuel,maintenance'],
            'bike_id' => ['required', 'integer', 'exists:petty_bikes,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'particulars' => ['nullable', 'string'],
        ]);

        $bike = Bike::query()->findOrFail((int) $data['bike_id']);
        if (!$bike->isSelectableForSpending()) {
            return back()->withErrors([
                'bike_id' => 'Only active bikes can be selected for new spending records.',
            ])->withInput();
        }

        $respondentId = !empty($data['respondent_id']) ? (int) $data['respondent_id'] : null;
        if ($respondentId) {
            $respondent = Respondent::query()->findOrFail($respondentId);
            if (!$respondent->isSelectableForSpending()) {
                return back()->withErrors([
                    'respondent_id' => 'Only active respondents can be selected for new spending records.',
                ])->withInput();
            }
        }

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        if ($data['sub_type'] === 'maintenance' && empty(trim((string)($data['particulars'] ?? '')))) {
            return back()->withErrors(['particulars' => 'Particulars is required for maintenance.'])->withInput();
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        try {
            return PettyDatabase::transaction(function () use ($data, $amount, $fee, $allocator) {
                $spending = Spending::create([
                    'batch_id' => null, // allocator will set primary batch
                    'type' => 'bike',
                    'sub_type' => $data['sub_type'],
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => $data['respondent_id'] ?? null,
                    'description' => $data['description'] ?? null,
                    'related_id' => $data['bike_id'],
                    'particulars' => $data['particulars'] ?? null,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int)$data['batch_id'] : null;
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                return redirect()->route('petty.bikes.index')->with('success', 'Bike spending recorded with auto allocation.');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function byBike(Bike $bike, Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Spending::with(['respondent', 'bike', 'batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->where('related_id', $bike->id)
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $spendings = $query->paginate(20)->withQueryString();

        $total = (float) PettyDatabase::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'bike')
            ->where('petty_spendings.related_id', $bike->id)
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        return view('pettycash::spendings.bikes.by_bike', compact('bike', 'spendings', 'total', 'from', 'to'));
    }

    public function pdf(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $from = $request->query('from');
        $to = $request->query('to');
        $sub = $request->query('sub_type');
        $batchId = $request->query('batch_id');
        $q = trim((string) $request->query('q', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'date_desc')));
        $allowedSorts = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date_desc';
        }

        $spendingsQuery = Spending::with(['respondent', 'bike', 'batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->when($sub, fn($q) => $q->where('sub_type', $sub))
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhere('particulars', 'like', '%' . $q . '%')
                        ->orWhereHas('bike', fn ($bike) => $bike->where('plate_no', 'like', '%' . $q . '%')->orWhere('model', 'like', '%' . $q . '%'))
                        ->orWhereHas('respondent', fn ($respondent) => $respondent->where('name', 'like', '%' . $q . '%')->orWhere('phone', 'like', '%' . $q . '%'))
                        ->orWhereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $spendingsQuery->orderBy('date')->orderBy('id'),
            'amount_desc' => $spendingsQuery->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $spendingsQuery->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $spendingsQuery->orderByDesc('date')->orderByDesc('id'),
        };

        $spendings = $spendingsQuery->get();

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $spendings->map(function ($s) {
                $amount = (float) $s->amount;
                $fee = (float) ($s->transaction_cost ?? 0);
                $allocationBatches = $s->allocations
                    ->map(fn($a) => $a->batch?->batch_no)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'date' => $s->date?->format('Y-m-d'),
                    'plate_no' => $s->bike?->plate_no ?? '',
                    'sub_type' => strtoupper((string) ($s->sub_type ?? '')),
                    'reference' => $s->reference ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'respondent' => $s->respondent?->name ?? '',
                    'description' => $s->description ?? '',
                    'particulars' => $s->particulars ?? '',
                    'primary_batch' => $s->batch?->batch_no ?? '',
                    'allocated_batches' => $allocationBatches,
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-bike-spendings-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'Plate' => 'plate_no',
                    'Subtype' => 'sub_type',
                    'MPESA Ref' => 'reference',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Respondent' => 'respondent',
                    'Description' => 'description',
                    'Particulars' => 'particulars',
                    'Primary Batch' => 'primary_batch',
                    'Allocated Batches' => 'allocated_batches',
                ],
                $rows
            );
        }

        $total = (float) (clone $spendingsQuery)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost, 0)), 0) as total_amount')
            ->value('total_amount');

        $pdf = Pdf::loadView('pettycash::reports.bikes_spendings_pdf', [
            'spendings' => $spendings,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'sub' => $sub,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('pettycash-bikes-spendings.pdf');
    }

    // edit/update: keep working (single batch UI) but allocations will still reflect real math if you want later.
    public function edit(Spending $spending)
    {
        abort_unless($spending->type === 'bike', 404);

        $batches = Batch::orderByDesc('id')->limit(50)->get();
        $bikes = Bike::query()
            ->where(function ($query) use ($spending) {
                $query->where('status', Bike::STATUS_ACTIVE)
                    ->orWhere('id', (int) $spending->related_id);
            })
            ->orderBy('plate_no')
            ->get();
        $respondents = Respondent::query()
            ->where(function ($query) use ($spending) {
                $query->where('status', Respondent::STATUS_ACTIVE)
                    ->orWhere('id', (int) $spending->respondent_id);
            })
            ->orderBy('name')
            ->get();

        return view('pettycash::spendings.bikes.edit', compact('spending', 'batches', 'bikes', 'respondents'));
    }

    public function update(Request $request, Spending $spending)
    {
        abort_unless($spending->type === 'bike', 404);

        $data = $request->validate([
            'funding' => ['nullable', 'in:auto,single'], // if edit view doesn't have it, fallback to single
            'batch_id' => ['required', 'integer', 'exists:petty_batches,id'],

            'sub_type' => ['required', 'in:fuel,maintenance'],
            'bike_id' => ['required', 'integer', 'exists:petty_bikes,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'particulars' => ['nullable', 'string'],
        ]);

        $bike = Bike::query()->findOrFail((int) $data['bike_id']);
        if ((int) $bike->id !== (int) $spending->related_id && !$bike->isSelectableForSpending()) {
            return back()->withErrors([
                'bike_id' => 'Only active bikes can be selected for new spending records.',
            ])->withInput();
        }

        $respondentId = !empty($data['respondent_id']) ? (int) $data['respondent_id'] : null;
        if ($respondentId) {
            $respondent = Respondent::query()->findOrFail($respondentId);
            if ((int) $respondent->id !== (int) $spending->respondent_id && !$respondent->isSelectableForSpending()) {
                return back()->withErrors([
                    'respondent_id' => 'Only active respondents can be selected for new spending records.',
                ])->withInput();
            }
        }

        if ($data['sub_type'] === 'maintenance' && empty(trim((string)($data['particulars'] ?? '')))) {
            return back()->withErrors(['particulars' => 'Particulars is required for maintenance.'])->withInput();
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        try {
            return PettyDatabase::transaction(function () use ($spending, $data, $amount, $fee, $allocator) {
                $spending->update([
                    'sub_type' => $data['sub_type'],
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => $data['respondent_id'] ?? null,
                    'description' => $data['description'] ?? null,
                    'related_id' => $data['bike_id'],
                    'particulars' => $data['particulars'] ?? null,
                ]);

                // default to single-batch on edit (safe)
                $onlyBatch = (int)$data['batch_id'];
                $allocator->forceAllocateToBatch($spending, $amount, $fee, $onlyBatch);

                return redirect()->route('petty.bikes.index')->with('success', 'Bike spending updated.');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Spending $spending)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);
        abort_unless($spending->type === 'bike', 404);

        PettyDatabase::transaction(function () use ($spending) {
            \App\Modules\PettyCash\Models\SpendingAllocation::query()
                ->where('spending_id', $spending->id)
                ->delete();
            $spending->delete();
        });

        return redirect()->route('petty.bikes.index')->with('success', 'Bike spending deleted.');
    }
}
