<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TabularExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class MealController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');
        $q = trim((string) $request->query('q', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'date_desc')));
        $allowedSorts = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date_desc';
        }

        $listQuery = Spending::with(['batch', 'allocations.batch'])
            ->where('type', 'meal')
            ->whereIn('sub_type', ['lunch', 'daily_payment'])
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $listQuery->orderBy('date')->orderBy('id'),
            'amount_desc' => $listQuery->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $listQuery->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $listQuery->orderByDesc('date')->orderByDesc('id'),
        };

        $meals = $listQuery->paginate(20)->withQueryString();

        $total = (float) (clone $listQuery)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost, 0)), 0) as total_amount')
            ->value('total_amount');

        $batches = Batch::orderByDesc('id')->limit(50)->get();

        return view('pettycash::spendings.meals.index', compact('meals', 'total', 'from', 'to', 'batchId', 'batches', 'q', 'sort'));
    }

    public function create(Request $request)
    {
        $allocator = app(FundsAllocatorService::class);

        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $prefBatchId = $request->query('batch_id');

        return view('pettycash::spendings.meals.create', compact('batches', 'prefBatchId', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],

            'date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],

            'mass' => ['nullable', 'boolean'],
            'range_from' => ['nullable', 'date'],
            'range_to' => ['nullable', 'date'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        $isMass = (bool)($request->input('mass') ?? false);

        if ($isMass) {
            if (empty($data['range_from']) || empty($data['range_to'])) {
                return back()->withErrors(['range_from' => 'Range dates are required for mass disbursement.'])->withInput();
            }

            $start = Carbon::parse($data['range_from'])->startOfDay();
            $end = Carbon::parse($data['range_to'])->startOfDay();
            if ($start->gt($end)) [$start, $end] = [$end, $start];

            $dates = [];
            $cur = $start->copy();
            while ($cur->lte($end)) {
                $dates[] = $cur->toDateString();
                $cur->addDay();
            }
        } else {
            if (empty($data['date'])) {
                return back()->withErrors(['date' => 'Date is required.'])->withInput();
            }
            $dates = [Carbon::parse($data['date'])->toDateString()];
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        // upfront balance check for AUTO (total)
        if ($data['funding'] === 'auto') {
            $requiredTotal = ($amount + $fee) * count($dates);
            $availableTotal = $allocator->totalNetBalance();
            if ($requiredTotal > $availableTotal) {
                return back()->withErrors([
                    'amount' => 'Insufficient TOTAL balance. Needed: '.number_format($requiredTotal,2).' Available: '.number_format($availableTotal,2)
                ])->withInput();
            }
        }

        try {
            PettyDatabase::transaction(function () use ($data, $dates, $amount, $fee, $allocator) {
                foreach ($dates as $d) {
                    $sp = Spending::create([
                        'batch_id' => null,
                        'type' => 'meal',
                        'sub_type' => 'lunch',
                        'reference' => $data['reference'],
                        'amount' => $amount,
                        'transaction_cost' => $fee,
                        'date' => $d,
                        'description' => $data['description'],
                    ]);

                    $onlyBatch = ($data['funding'] === 'single') ? (int)$data['batch_id'] : null;
                    $allocator->allocateSmallestFirst($sp, $amount, $fee, $onlyBatch);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.meals.index')->with('success', $isMass
            ? ('Mass lunch disbursement recorded for '.count($dates).' day(s).')
            : 'Meal spending recorded.'
        );
    }

    public function pdf(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');
        $q = trim((string) $request->query('q', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'date_desc')));
        $allowedSorts = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date_desc';
        }

        $mealsQuery = Spending::with(['batch', 'allocations.batch'])
            ->where('type', 'meal')
            ->whereIn('sub_type', ['lunch', 'daily_payment'])
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $mealsQuery->orderBy('date')->orderBy('id'),
            'amount_desc' => $mealsQuery->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $mealsQuery->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $mealsQuery->orderByDesc('date')->orderByDesc('id'),
        };

        $meals = $mealsQuery->get();

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $meals->map(function ($m) {
                $amount = (float) $m->amount;
                $fee = (float) ($m->transaction_cost ?? 0);
                $allocationBatches = $m->allocations
                    ->map(fn($a) => $a->batch?->batch_no)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'date' => $m->date?->format('Y-m-d'),
                    'reference' => $m->reference ?? '',
                    'description' => $m->description ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'primary_batch' => $m->batch?->batch_no ?? '',
                    'allocated_batches' => $allocationBatches,
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-meals-lunch-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'MPESA Ref' => 'reference',
                    'Description' => 'description',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Primary Batch' => 'primary_batch',
                    'Allocated Batches' => 'allocated_batches',
                ],
                $rows
            );
        }

        $total = (float) (clone $mealsQuery)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost, 0)), 0) as total_amount')
            ->value('total_amount');

        $pdf = Pdf::loadView('pettycash::reports.meals_pdf', [
            'meals' => $meals,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'batchId' => $batchId,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-meals-lunch.pdf');
    }

    public function edit(Spending $spending)
    {
        abort_unless($spending->type === 'meal' && in_array((string) $spending->sub_type, ['lunch', 'daily_payment'], true), 404);

        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();

        return view('pettycash::spendings.meals.edit', compact('spending', 'batches'));
    }

    public function update(Request $request, Spending $spending)
    {
        abort_unless($spending->type === 'meal' && in_array((string) $spending->sub_type, ['lunch', 'daily_payment'], true), 404);

        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:petty_batches,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $amount = (float) $data['amount'];
        $fee = (float) ($data['transaction_cost'] ?? 0);
        $allocator = app(FundsAllocatorService::class);

        try {
            PettyDatabase::transaction(function () use ($spending, $data, $amount, $fee, $allocator) {
                $spending->update([
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'description' => $data['description'],
                ]);

                $allocator->forceAllocateToBatch($spending, $amount, $fee, (int) $data['batch_id']);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.meals.index')->with('success', 'Meal updated.');
    }

    public function destroy(Spending $spending)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);
        abort_unless($spending->type === 'meal' && in_array((string) $spending->sub_type, ['lunch', 'daily_payment'], true), 404);

        PettyDatabase::transaction(function () use ($spending) {
            \App\Modules\PettyCash\Models\SpendingAllocation::query()
                ->where('spending_id', $spending->id)
                ->delete();
            $spending->delete();
        });

        return redirect()->route('petty.meals.index')->with('success', 'Meal spending deleted.');
    }
}
