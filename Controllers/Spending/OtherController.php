<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TabularExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OtherController extends Controller
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

        $listQuery = Spending::with(['batch', 'respondent', 'allocations.batch'])
            ->where('type', 'other')
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('respondent', fn ($respondent) => $respondent->where('name', 'like', '%' . $q . '%')->orWhere('phone', 'like', '%' . $q . '%'))
                        ->orWhereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $listQuery->orderBy('date')->orderBy('id'),
            'amount_desc' => $listQuery->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $listQuery->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $listQuery->orderByDesc('date')->orderByDesc('id'),
        };

        $others = $listQuery->paginate(20)->withQueryString();

        $total = (float) (clone $listQuery)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost, 0)), 0) as total_amount')
            ->value('total_amount');

        $batches = Batch::orderByDesc('id')->limit(50)->get();

        return view('pettycash::spendings.others.index', compact('others', 'total', 'from', 'to', 'batchId', 'batches', 'q', 'sort'));
    }

    public function create(Request $request)
    {
        $allocator = app(FundsAllocatorService::class);

        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $respondents = Respondent::query()
            ->selectable()
            ->orderBy('name')
            ->get();
        $prefBatchId = $request->query('batch_id');

        return view('pettycash::spendings.others.create', compact('batches', 'respondents', 'prefBatchId', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
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

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        if ($data['funding'] === 'auto') {
            $required = $amount + $fee;
            if ($required > $allocator->totalNetBalance()) {
                return back()->withErrors([
                    'amount' => 'Insufficient TOTAL balance. Needed: '.number_format($required,2).' Available: '.number_format($allocator->totalNetBalance(),2)
                ])->withInput();
            }
        }

        try {
            PettyDatabase::transaction(function () use ($data, $amount, $fee, $allocator) {
                $sp = Spending::create([
                    'batch_id' => null,
                    'type' => 'other',
                    'sub_type' => null,
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => $data['respondent_id'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int)$data['batch_id'] : null;
                $allocator->allocateSmallestFirst($sp, $amount, $fee, $onlyBatch);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.others.index')->with('success', 'Other spending recorded.');
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

        $othersQuery = Spending::with(['respondent','batch','allocations.batch'])
            ->where('type', 'other')
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('respondent', fn ($respondent) => $respondent->where('name', 'like', '%' . $q . '%')->orWhere('phone', 'like', '%' . $q . '%'))
                        ->orWhereHas('batch', fn ($batch) => $batch->where('batch_no', 'like', '%' . $q . '%'));
                });
            });

        match ($sort) {
            'date_asc' => $othersQuery->orderBy('date')->orderBy('id'),
            'amount_desc' => $othersQuery->orderByDesc('amount')->orderByDesc('date')->orderByDesc('id'),
            'amount_asc' => $othersQuery->orderBy('amount')->orderByDesc('date')->orderByDesc('id'),
            default => $othersQuery->orderByDesc('date')->orderByDesc('id'),
        };

        $others = $othersQuery->get();

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $others->map(function ($o) {
                $amount = (float) $o->amount;
                $fee = (float) ($o->transaction_cost ?? 0);
                $allocationBatches = $o->allocations
                    ->map(fn($a) => $a->batch?->batch_no)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'date' => $o->date?->format('Y-m-d'),
                    'reference' => $o->reference ?? '',
                    'description' => $o->description ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'respondent' => $o->respondent?->name ?? '',
                    'primary_batch' => $o->batch?->batch_no ?? '',
                    'allocated_batches' => $allocationBatches,
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-others-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'MPESA Ref' => 'reference',
                    'Description' => 'description',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Respondent' => 'respondent',
                    'Primary Batch' => 'primary_batch',
                    'Allocated Batches' => 'allocated_batches',
                ],
                $rows
            );
        }

        $total = (float) (clone $othersQuery)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost, 0)), 0) as total_amount')
            ->value('total_amount');

        $pdf = Pdf::loadView('pettycash::reports.others_pdf', [
            'others' => $others,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'batchId' => $batchId,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-others.pdf');
    }

    public function edit(Spending $spending)
    {
        abort_unless($spending->type === 'other', 404);

        $batches = Batch::orderByDesc('id')->limit(50)->get();
        $respondents = Respondent::query()
            ->where(function ($query) use ($spending) {
                $query->where('status', Respondent::STATUS_ACTIVE)
                    ->orWhere('id', (int) $spending->respondent_id);
            })
            ->orderBy('name')
            ->get();

        return view('pettycash::spendings.others.edit', compact('spending', 'batches', 'respondents'));
    }

    public function update(Request $request, Spending $spending)
    {
        abort_unless($spending->type === 'other', 404);

        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:petty_batches,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $fee = (float) ($data['transaction_cost'] ?? 0);
        $amount = (float) $data['amount'];
        $allocator = app(FundsAllocatorService::class);

        $respondentId = !empty($data['respondent_id']) ? (int) $data['respondent_id'] : null;
        if ($respondentId) {
            $respondent = Respondent::query()->findOrFail($respondentId);
            if ((int) $respondent->id !== (int) $spending->respondent_id && !$respondent->isSelectableForSpending()) {
                return back()->withErrors([
                    'respondent_id' => 'Only active respondents can be selected for new spending records.',
                ])->withInput();
            }
        }

        PettyDatabase::transaction(function () use ($spending, $data, $amount, $fee, $allocator) {
            $spending->update([
                'reference' => $data['reference'],
                'amount' => $amount,
                'transaction_cost' => $fee,
                'date' => $data['date'],
                'respondent_id' => $data['respondent_id'] ?? null,
                'description' => $data['description'],
            ]);

            $allocator->forceAllocateToBatch($spending, $amount, $fee, (int) $data['batch_id']);
        });

        return redirect()->route('petty.others.index')->with('success', 'Other spending updated.');
    }

    public function destroy(Spending $spending)
    {
        abort_unless(PettyAccess::isAdmin(auth('petty')->user()), 403);
        abort_unless($spending->type === 'other', 404);

        PettyDatabase::transaction(function () use ($spending) {
            \App\Modules\PettyCash\Models\SpendingAllocation::query()
                ->where('spending_id', $spending->id)
                ->delete();
            $spending->delete();
        });

        return redirect()->route('petty.others.index')->with('success', 'Other spending deleted.');
    }
}
