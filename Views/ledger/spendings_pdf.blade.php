@php
    use Carbon\Carbon;

    $calc = !empty($calc);

    $sumByTypeAmount = [];
    $sumByTypeFee = [];
    $sumByTypeTotal = [];

    $grandAmount = 0.0;
    $grandFee = 0.0;
    $grandTotal = 0.0;

    $normType = function ($t) {
        $t = strtolower(trim((string) $t));
        return $t === '' ? 'other' : $t;
    };

    $fmtDate = function ($v, $format = 'Y-m-d') {
        if (empty($v)) {
            return '-';
        }

        try {
            if ($v instanceof \DateTimeInterface) {
                return $v->format($format);
            }

            return Carbon::parse($v)->format($format);
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };

    $dateOrNull = function ($v) {
        if (empty($v)) {
            return null;
        }

        try {
            return $v instanceof \DateTimeInterface ? Carbon::instance($v) : Carbon::parse($v);
        } catch (\Throwable $e) {
            return null;
        }
    };

    $ordinalUpper = function (?Carbon $date) {
        if (!$date) {
            return '-';
        }

        return strtoupper($date->format('jS'));
    };

    $plateFromSpending = function ($s) {
        $plate = data_get($s, 'plate_no')
            ?? data_get($s, 'bike.plate_no')
            ?? data_get($s, 'bike.plate')
            ?? data_get($s, 'bike.plate_number')
            ?? data_get($s, 'vehicle.plate_no')
            ?? data_get($s, 'vehicle.plate')
            ?? data_get($s, 'vehicle.plate_number')
            ?? data_get($s, 'asset.plate_no')
            ?? data_get($s, 'asset.plate')
            ?? data_get($s, 'asset.plate_number');

        if (!empty($plate)) {
            return $plate;
        }

        $hay = implode(' ', array_filter([
            (string) data_get($s, 'description'),
            (string) data_get($s, 'reference'),
            (string) data_get($s, 'sub_type'),
            (string) data_get($s, 'type'),
        ]));

        if (preg_match('/\b([A-Z]{2,3}\s?\d{3,4}[A-Z]?)\b/i', $hay, $m)) {
            return strtoupper(str_replace(' ', '', $m[1]));
        }

        return null;
    };

    foreach ($spendings as $s) {
        $fee = (float) data_get($s, 'transaction_cost', 0);
        $amt = (float) data_get($s, 'amount', 0);
        $total = $amt + $fee;

        $t = $normType(data_get($s, 'type', 'other'));

        $sumByTypeAmount[$t] = ($sumByTypeAmount[$t] ?? 0) + $amt;
        $sumByTypeFee[$t] = ($sumByTypeFee[$t] ?? 0) + $fee;
        $sumByTypeTotal[$t] = ($sumByTypeTotal[$t] ?? 0) + $total;

        $grandAmount += $amt;
        $grandFee += $fee;
        $grandTotal += $total;
    }

    $preferredOrder = ['bike', 'fuel', 'meal', 'token', 'other'];
    $allTypes = array_unique(array_merge($preferredOrder, array_keys($sumByTypeTotal)));

    $spendings = collect($spendings)->sortBy([
        fn ($a, $b) => strcmp(
            (string) data_get($a, 'date', data_get($a, 'service_date', '')),
            (string) data_get($b, 'date', data_get($b, 'service_date', ''))
        ),
        fn ($a, $b) => ((int) data_get($a, 'id', 0)) <=> ((int) data_get($b, 'id', 0)),
    ])->values();

    $fromDate = $dateOrNull($from);
    $toDate = $dateOrNull($to);
    $reportMonthSource = $fromDate ?: $toDate ?: now();

    if ($fromDate && $toDate && $fromDate->format('F Y') === $toDate->format('F Y')) {
        $reportTitle = 'PETTY CASH REPORT FOR ' . strtoupper($fromDate->format('F'));
        $dateRangeLabel = $ordinalUpper($fromDate) . ' - ' . $ordinalUpper($toDate) . ' ' . strtoupper($toDate->format('F Y'));
    } elseif ($fromDate && $toDate) {
        $reportTitle = 'PETTY CASH REPORT';
        $dateRangeLabel = $ordinalUpper($fromDate) . ' ' . strtoupper($fromDate->format('F Y')) . ' - ' . $ordinalUpper($toDate) . ' ' . strtoupper($toDate->format('F Y'));
    } elseif ($fromDate) {
        $reportTitle = 'PETTY CASH REPORT FOR ' . strtoupper($fromDate->format('F'));
        $dateRangeLabel = strtoupper($fromDate->format('jS F Y'));
    } elseif ($toDate) {
        $reportTitle = 'PETTY CASH REPORT FOR ' . strtoupper($toDate->format('F'));
        $dateRangeLabel = strtoupper($toDate->format('jS F Y'));
    } else {
        $reportTitle = 'PETTY CASH REPORT FOR ' . strtoupper($reportMonthSource->format('F'));
        $dateRangeLabel = strtoupper($reportMonthSource->format('jS F Y'));
    }

    $logoAbsolutePath = '/root/Marcep/netbil/app/Modules/PettyCash/Skybrix_Logo.png';
    $logoExists = is_file($logoAbsolutePath);
    $logoSrc = $logoExists ? 'file://' . realpath($logoAbsolutePath) : null;

@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spendings Ledger</title>
    <style>
        @page { margin: 8mm; }

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.8px;
            color:#111827;
            margin:0;
        }

        .report-header{
            width:100%;
            border-bottom:2px solid #111827;
            padding-bottom:8px;
            margin-bottom:10px;
        }
        .report-header-table{
            width:100%;
            border-collapse:collapse;
        }
        .report-header-table td{
            vertical-align:middle;
        }
        .brand-cell{
            width:24%;
        }
        .title-cell{
            width:54%;
            text-align:center;
        }
        .meta-cell{
            width:22%;
            text-align:right;
        }
        .brand-logo{
            width:220px;
            max-width:220px;
            height:auto;
        }
        .brand-fallback{
            font-size:18px;
            font-weight:900;
            letter-spacing:.02em;
            color:#0b6b53;
        }
        .brand-fallback small{
            display:block;
            font-size:11px;
            letter-spacing:.08em;
            color:#111827;
        }
        .report-title{
            margin:0;
            font-size:30px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.01em;
            color:#111827;
        }
        .report-range{
            margin-top:4px;
            font-size:18px;
            font-weight:900;
            text-transform:uppercase;
            color:#111827;
        }
        .generated-label{
            font-size:9px;
            color:#6b7280;
            margin-bottom:2px;
        }
        .generated-value{
            font-size:11px;
            font-weight:800;
            color:#111827;
        }
        table.data{
            width:100%;
            border-collapse:collapse;
            table-layout:fixed;
            margin-top:2px;
            font-size:7.8px;
        }
        table.data th,
        table.data td{
            border:1px solid #e5e7eb;
            padding:2px 3px;
            vertical-align:top;
            line-height:1.12;
        }
        table.data th{
            background:#f3f4f6;
            font-size:8px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#111827;
        }

        .num{
            text-align:right;
            white-space:nowrap;
        }
        .col-date { width: 7%; }
        .col-reference { width: 8%; }
        .col-category { width: 6%; }
        .col-description { width: 50%; }
        .col-batch { width: 8%; }
        .col-amount { width: 7%; }
        .col-fee { width: 6%; }
        .col-total { width: 8%; }
        .col-date,
        .col-reference,
        .col-category,
        .col-batch,
        .col-amount,
        .col-fee,
        .col-total{
            white-space:nowrap;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .col-description{
            white-space:normal;
            word-break:normal;
            overflow-wrap:break-word;
        }
        .badge{
            display:inline-block;
            padding:1px 4px;
            border-radius:4px;
            background:#eef2ff;
            border:1px solid #dbe3ff;
            font-size:7.5px;
            font-weight:800;
            line-height:1.15;
        }
        .small{
            font-size:8.2px;
            color:#6b7280;
            margin-top:1px;
        }

        .totals{
            margin-top:10px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            padding:8px 10px;
        }
        .totals h3{
            margin:0 0 6px 0;
            font-size:10.4px;
            font-weight:900;
            color:#111827;
        }
        table.sum{
            width:100%;
            border-collapse:collapse;
        }
        table.sum th,
        table.sum td{
            padding:4px;
            border-top:1px solid #eeeeee;
            font-size:9px;
        }
        table.sum th{
            text-align:left;
            background:#fafafa;
            font-weight:800;
        }
        table.sum td.num{
            text-align:right;
        }
        .grandRow td{
            font-weight:900;
            border-top:2px solid #d1d5db;
        }
    </style>
</head>
<body>

    <div class="report-header">
        <table class="report-header-table">
            <tr>
                <td class="brand-cell">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Skybrix Internet" class="brand-logo">
                    @else
                        <div class="brand-fallback">
                            SKYBRIX
                            <small>INTERNET</small>
                        </div>
                    @endif
                </td>
                <td class="title-cell">
                    <div class="report-title">{{ $reportTitle }}</div>
                    <div class="report-range">{{ $dateRangeLabel }}</div>
                </td>
                <td class="meta-cell">
                    <div class="generated-label">Generated</div>
                    <div class="generated-value">{{ now()->format('d M Y H:i') }}</div>
                </td>
            </tr>
        </table>

    </div>

    <table class="data">
        <thead>
        <tr>
            <th class="col-date">Date</th>
            <th class="col-reference">Reference</th>
            <th class="col-category">Category</th>
            <th class="col-description">Description</th>
            <th class="col-batch">Batch</th>
            <th class="col-amount num">Amount</th>
            <th class="col-fee num">Fee</th>
            <th class="col-total num">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($spendings as $s)
            @php
                $fee = (float) data_get($s, 'transaction_cost', 0);
                $amt = (float) data_get($s, 'amount', 0);
                $total = $amt + $fee;

                $typeRaw = (string) data_get($s, 'type', '');
                $t = strtolower($typeRaw);
                $sub = strtolower((string) data_get($s, 'sub_type', ''));

                $dateVal = data_get($s, 'date') ?? data_get($s, 'service_date');
                $batchNo = data_get($s, 'batch_no') ?? data_get($s, 'batch.batch_no') ?? '-';

                $meterNo = trim((string) data_get($s, 'meter_no', ''));
                $descRaw = trim((string) data_get($s, 'description', ''));
                $plate = $plateFromSpending($s);

                if ($t === 'token') {
                    $base = $descRaw !== '' ? $descRaw : 'Token payment';
                    $desc = $base;

                    if ($meterNo !== '' && stripos($desc, $meterNo) === false) {
                        $desc .= ' - ' . $meterNo;
                    }
                } else {
                    $desc = $descRaw;
                }

                if ($desc === '' && in_array($t, ['bike', 'fuel'], true)) {
                    $desc = $plate ? ('Plate: ' . $plate) : '-';
                }
                if ($desc === '') {
                    $desc = '-';
                }

                $shouldShowPlateInline = !empty($plate)
                    && ($t === 'bike' || $t === 'fuel' || $sub === 'service')
                    && stripos($desc, 'plate:') === false
                    && stripos($desc, $plate) === false;

                if ($shouldShowPlateInline) {
                    $desc .= ' | Plate: ' . $plate;
                }
            @endphp

            <tr>
                <td class="col-date">{{ $fmtDate($dateVal, 'Y-m-d') }}</td>
                <td class="col-reference">{{ data_get($s, 'reference', '-') ?: '-' }}</td>
                <td class="col-category"><span class="badge">{{ strtoupper($typeRaw ?: 'N/A') }}</span></td>
                <td class="col-description">{{ $desc }}</td>
                <td class="col-batch">{{ $batchNo }}</td>
                <td class="col-amount num">{{ number_format($amt, 2) }}</td>
                <td class="col-fee num">{{ number_format($fee, 2) }}</td>
                <td class="col-total num"><b>{{ number_format($total, 2) }}</b></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <h3>Totals Summary</h3>
        <table class="sum">
            <thead>
            <tr>
                <th>Category</th>
                <th class="num">Amount</th>
                <th class="num">Fee</th>
                <th class="num">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($allTypes as $t)
                @php
                    $exists = array_key_exists($t, $sumByTypeTotal);
                    $a = (float) ($sumByTypeAmount[$t] ?? 0);
                    $f = (float) ($sumByTypeFee[$t] ?? 0);
                    $tt = (float) ($sumByTypeTotal[$t] ?? 0);
                @endphp
                @if($exists)
                    <tr>
                        <td>{{ strtoupper($t) }}</td>
                        <td class="num">{{ number_format($a, 2) }}</td>
                        <td class="num">{{ number_format($f, 2) }}</td>
                        <td class="num"><b>{{ number_format($tt, 2) }}</b></td>
                    </tr>
                @endif
            @endforeach

            <tr class="grandRow">
                <td>GRAND TOTAL</td>
                <td class="num">{{ number_format($grandAmount, 2) }}</td>
                <td class="num">{{ number_format($grandFee, 2) }}</td>
                <td class="num">{{ number_format($grandTotal, 2) }}</td>
            </tr>
            </tbody>
        </table>

        <div class="small" style="margin-top:6px">
            THIS IS A SYSTEM GENERATED RECEIPT AND DOES NOT REQUIRE A SIGNATURE.
        </div>
    </div>

</body>
</html>
