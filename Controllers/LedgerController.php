<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Support\PettyDatabase;
use App\Modules\PettyCash\Support\TabularExport;
use App\Modules\PettyCash\Support\UnifiedLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\DomPDF\Facade\Pdf;

class LedgerController extends Controller
{
    public function pdf(Request $request)
    {
        $request->merge(['export' => 'pdf']);
        return $this->spendings($request);
    }

    public function index(Request $request)
    {
        return $this->spendings($request);
    }

    public function spendings(Request $request)
    {
        [$period, $from, $to, $periodOptions, $periodLabel] = $this->resolveLedgerPeriod($request);
        $batchId = $request->query('batch_id');
        $type    = $request->query('type');
        $q       = trim((string) $request->query('q', ''));
        $calc    = (int) $request->query('calc', 0) === 1;
        $exportRaw = $request->query('export');

        $exportFormat = null;
        if ($exportRaw !== null && $exportRaw !== '') {
            $raw = strtolower((string) $exportRaw);
            $exportFormat = in_array($raw, ['1', 'true', 'yes'], true) ? 'pdf' : $raw;
        }
        $export = in_array($exportFormat, ['pdf', 'csv', 'excel', 'xls', 'xlsx'], true);

        // batches list for dropdown (adjust if your table differs)
        $batches = PettyDatabase::table('petty_batches')
            ->select('id', 'batch_no')
            ->orderByDesc('id')
            ->get();

        $query = UnifiedLedger::query();

        if (!empty($from)) $query->whereDate('date', '>=', $from);
        if (!empty($to))   $query->whereDate('date', '<=', $to);

        // Batch only applies to spendings rows (services have batch_id null)
        if (!empty($batchId)) {
            $query->where(function ($x) use ($batchId) {
                $x->where('source', 'spending')
                  ->where('batch_id', $batchId);
            });
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('reference', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $orderedQuery = clone $query;
        if ($export) {
            $orderedQuery
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc');
        } else {
            $orderedQuery
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc');
        }

        $spendings = $export
            ? $orderedQuery->get()
            : $orderedQuery->paginate(20)->withQueryString();

        // AJAX response for live search (not for export)
        if ($request->ajax() && !$export) {
            $html = view('pettycash::ledger._table', ['spendings' => $spendings])->render();
            return response()->json([
                'html'  => $html,
                'count' => $spendings instanceof LengthAwarePaginator ? $spendings->total() : $spendings->count(),
            ]);
        }

        // Totals for PDF (optional)
        $sumByTypeAmount = [];
        $sumByTypeFee = [];
        $sumByTypeTotal = [];
        $grandAmount = 0.0;
        $grandFee = 0.0;
        $grandTotal = 0.0;

        if ($calc) {
            foreach ($spendings as $s) {
                $fee = (float) data_get($s, 'transaction_cost', 0);
                $amt = (float) data_get($s, 'amount', 0);
                $tot = $amt + $fee;

                $t = strtolower(trim((string) data_get($s, 'type', 'other')));
                if ($t === '') $t = 'other';

                $sumByTypeAmount[$t] = ($sumByTypeAmount[$t] ?? 0) + $amt;
                $sumByTypeFee[$t]    = ($sumByTypeFee[$t] ?? 0) + $fee;
                $sumByTypeTotal[$t]  = ($sumByTypeTotal[$t] ?? 0) + $tot;

                $grandAmount += $amt;
                $grandFee    += $fee;
                $grandTotal  += $tot;
            }
        }

        if ($export) {
            $exportBaseName = $this->buildExportBaseName($period, $from, $to);

            if (in_array($exportFormat, ['csv', 'excel', 'xls', 'xlsx'], true)) {
                $rows = $spendings->map(function ($s) {
                    $amount = (float) data_get($s, 'amount', 0);
                    $fee = (float) data_get($s, 'transaction_cost', 0);
                    $date = data_get($s, 'date');

                    return [
                        'date' => $date ? date('Y-m-d', strtotime((string) $date)) : '',
                        'reference' => (string) data_get($s, 'reference', ''),
                        'type' => strtoupper((string) data_get($s, 'type', '')),
                        'sub_type' => strtoupper((string) data_get($s, 'sub_type', '')),
                        'description' => (string) data_get($s, 'description', ''),
                        'batch_no' => (string) (data_get($s, 'batch_no') ?? ''),
                        'plate_no' => (string) (data_get($s, 'plate_no') ?? ''),
                        'meter_no' => (string) (data_get($s, 'meter_no') ?? ''),
                        'amount' => number_format($amount, 2, '.', ''),
                        'transaction_cost' => number_format($fee, 2, '.', ''),
                        'total' => number_format($amount + $fee, 2, '.', ''),
                        'source' => strtoupper((string) data_get($s, 'source', '')),
                    ];
                })->all();

                return TabularExport::download(
                    $exportFormat,
                    $exportBaseName,
                    [
                        'Date' => 'date',
                        'Reference' => 'reference',
                        'Category' => 'type',
                        'Sub Type' => 'sub_type',
                        'Description' => 'description',
                        'Batch' => 'batch_no',
                        'Plate' => 'plate_no',
                        'Meter' => 'meter_no',
                        'Amount' => 'amount',
                        'Fee' => 'transaction_cost',
                        'Total' => 'total',
                        'Source' => 'source',
                    ],
                    $rows
                );
            }

            $payload = [
                'spendings' => $spendings,
                'from' => $from,
                'to' => $to,
                'batchId' => $batchId,
                'type' => $type,
                'q' => $q,
                'calc' => $calc,

                // Pass totals (your PDF blade can use these)
                'sumByTypeAmount' => $sumByTypeAmount,
                'sumByTypeFee' => $sumByTypeFee,
                'sumByTypeTotal' => $sumByTypeTotal,
                'grandAmount' => $grandAmount,
                'grandFee' => $grandFee,
                'grandTotal' => $grandTotal,
            ];

            $name = $exportBaseName . '.pdf';

            // ✅ LANDSCAPE + DOWNLOAD
            return Pdf::loadView('pettycash::ledger.spendings_pdf', $payload)
                ->setPaper('a4', 'landscape')
                ->download($name);
        }

        return view('pettycash::ledger.spendings', compact(
            'spendings', 'from', 'to', 'batches', 'batchId', 'type', 'q', 'calc', 'period', 'periodOptions', 'periodLabel'
        ));
    }

    private function resolveLedgerPeriod(Request $request): array
    {
        $today = now();
        $thisMonthStart = $today->copy()->startOfMonth();
        $thisMonthEnd = $today->copy()->endOfMonth();
        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $today->copy()->subMonthNoOverflow()->endOfMonth();

        $periodOptions = [
            'this_month' => [
                'label' => 'This Month (' . $thisMonthStart->format('F') . ')',
                'from' => $thisMonthStart->toDateString(),
                'to' => $thisMonthEnd->toDateString(),
            ],
            'last_month' => [
                'label' => 'Last Month (' . $lastMonthStart->format('F') . ')',
                'from' => $lastMonthStart->toDateString(),
                'to' => $lastMonthEnd->toDateString(),
            ],
            'custom' => [
                'label' => 'Custom Date Range',
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
            ],
        ];

        $period = strtolower(trim((string) $request->query('period', '')));
        if (!array_key_exists($period, $periodOptions)) {
            $period = filled($request->query('from')) || filled($request->query('to')) ? 'custom' : '';
        }

        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        if (in_array($period, ['this_month', 'last_month'], true)) {
            $from = $periodOptions[$period]['from'];
            $to = $periodOptions[$period]['to'];
            $request->merge([
                'from' => $from,
                'to' => $to,
                'period' => $period,
            ]);
        } elseif ($period === 'custom') {
            $request->merge(['period' => 'custom']);
        } else {
            $request->merge([
                'period' => '',
                'from' => $from,
                'to' => $to,
            ]);
        }

        $periodLabel = $period === '' ? 'All Records' : ($periodOptions[$period]['label'] ?? 'Custom Date Range');
        if ($period === 'custom' && ($from !== '' || $to !== '')) {
            $displayFrom = $from !== '' ? Carbon::parse($from)->format('d M Y') : '...';
            $displayTo = $to !== '' ? Carbon::parse($to)->format('d M Y') : '...';
            $periodLabel = 'Custom Range (' . $displayFrom . ' - ' . $displayTo . ')';
        }

        return [$period, $from, $to, $periodOptions, $periodLabel];
    }

    private function buildExportBaseName(string $period, string $from, string $to): string
    {
        if (in_array($period, ['this_month', 'last_month'], true) && $from !== '') {
            return 'pettycash-ledger-for-' . strtolower(Carbon::parse($from)->format('FY'));
        }

        if ($period === 'custom' && $from !== '' && $to !== '') {
            return 'pettycash-ledger-' . $from . '-to-' . $to;
        }

        return 'pettycash-ledger-all-records';
    }
}
