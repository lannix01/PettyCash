@extends('pettycash::layouts.app')

@section('title','PettyCash Dashboard')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    .container { max-width: 1320px !important; padding: 12px 8px !important; }
    .wrap { max-width: 1280px; margin: 0 auto; }

    /* Header */
    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 18px;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #f5fbf8 100%);
        border: 1px solid #dfe9e6;
        border-radius: 24px;
        padding: 26px 28px;
        box-shadow: 0 18px 40px rgba(18, 33, 47, 0.07);
    }

    .header h1 {
        font-size: 30px;
        font-weight: 800;
        color: #12212f;
        margin-bottom: 8px;
        letter-spacing: -0.03em;
    }

    .header-sub {
        font-size: 14px;
        color: #5f7283;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        line-height: 1.5;
    }

    .header::after {
        content: "";
        position: absolute;
        right: -60px;
        top: -52px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(13,139,111,.12), transparent 68%);
        pointer-events: none;
    }

    .header-brand {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
        flex: 1 1 520px;
        min-width: 0;
    }

    .header-mark {
        width: 76px;
        height: 76px;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #ebf9f4 100%);
        border: 1px solid rgba(13,139,111,.16);
        box-shadow: 0 18px 34px rgba(13,139,111,.14);
        padding: 12px;
        flex: 0 0 auto;
    }

    .header-mark img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    .header-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
        padding: 6px 11px;
        border-radius: 999px;
        background: #e5f6f0;
        border: 1px solid rgba(13,139,111,.14);
        color: #0c3c35;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .header-kicker::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #0d8b6f;
        display: block;
    }

    .header-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 16px;
        background: rgba(255,255,255,.78);
        border: 1px solid #d9e7e2;
        color: #415466;
        font-size: 12px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .header-status strong {
        color: #12212f;
        font-size: 14px;
    }

    .date-badge {
        padding: 6px 12px;
        background: #ffffff;
        border: 1px solid #d9ebe4;
        border-radius: 999px;
        font-size: 13px;
        color: #415466;
        font-weight: 600;
    }

    .filter-bar {
        background: #ffffff;
        border: 1px solid #dfe9e6;
        border-radius: 18px;
        padding: 16px 18px;
        margin-bottom: 20px;
        box-shadow: 0 12px 28px rgba(18, 33, 47, 0.05);
    }

    .filter-form {
        display: grid;
        grid-template-columns: minmax(150px, 180px) minmax(150px, 180px) auto;
        gap: 12px;
        align-items: end;
    }

    .form-group {
        min-width: 0;
    }

    .form-label {
        font-size: 11px;
        font-weight: 800;
        color: #5f7283;
        margin-bottom: 6px;
        display: block;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .form-input {
        width: 100%;
        border: 1px solid #cfddd8;
        padding: 11px 12px;
        border-radius: 12px;
        font-size: 13px;
        transition: all 0.2s ease;
        background: #fbfefd;
    }

    .form-input:focus {
        outline: none;
        border-color: #0d8b6f;
        box-shadow: 0 0 0 4px rgba(13,139,111,.10);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: end;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .btn-primary {
        border: none;
        background: #0d8b6f;
        color: #fff;
        padding: 11px 16px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
        transition: all 0.2s ease;
        box-shadow: 0 12px 24px rgba(13,139,111,.16);
    }

    .btn-primary:hover {
        background: #0b5c4d;
    }

    .btn-secondary {
        border: 1px solid #cfddd8;
        background: #fff;
        color: #415466;
        padding: 10px 16px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s ease;
    }

    .btn-secondary:hover {
        background: #f6fbf9;
        border-color: #b8cec7;
    }

    .filter-tip {
        font-size: 12px;
        color: #5f7283;
        padding: 11px 12px;
        background: #f5faf8;
        border-radius: 12px;
        border: 1px solid #e0ece8;
        margin-top: 0;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .insight-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #dfe9e6;
        border-radius: 18px;
        padding: 22px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(18, 33, 47, 0.05);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #dce7e3;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(18, 33, 47, 0.08);
        border-color: #cfddd8;
    }

    .stat-card:hover::before { width: 6px; }

    .stat-card.credited::before { background: #16a34a; }
    .stat-card.spent::before { background: #dc2626; }
    .stat-card.balance::before { background: #15283a; }
    .stat-card.top::before { background: #0d8b6f; }

    .stat-label {
        font-size: 12px;
        font-weight: 700;
        color: #5f7283;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stat-label i { font-size: 14px; opacity: 0.7; }

    .stat-value {
        font-size: clamp(28px, 2.5vw, 36px);
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
        color: #12212f;
    }

    .stat-value.green { color: #16a34a; }
    .stat-value.red { color: #dc2626; }

    .stat-meta { font-size: 13px; color: #708191; line-height: 1.45; }

    /* Tables Section */
    .tables-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .table-card {
        background: #fff;
        border: 1px solid #dfe9e6;
        border-radius: 18px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 12px 30px rgba(18, 33, 47, 0.05);
        min-width: 0;
    }

    .table-card:hover {
        box-shadow: 0 16px 34px rgba(18, 33, 47, 0.08);
        transform: translateY(-2px);
    }

    .table-header {
        background: #fbfefd;
        padding: 18px 20px;
        border-bottom: 1px solid #e4edeb;
    }

    .table-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #12212f;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-title i { font-size: 16px; opacity: 0.6; }
    .table-subtitle { font-size: 13px; color: #5f7283; }

    table { width: 100%; border-collapse: collapse; }
    th, td {
        text-align: left;
        padding: 14px 18px;
        border-bottom: 1px solid #edf3f1;
        font-size: 13px;
        vertical-align: middle;
    }

    th {
        background: #f8fcfb;
        color: #5f7283;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    tbody tr { transition: all 0.2s ease; }
    tbody tr:hover { background: #f7fbfa; }
    tbody tr:last-child td { border-bottom: none; }

    .category-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        background: #f3f8f6;
        border: 1px solid #deebe7;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        color: #415466;
    }

    .category-badge i { font-size: 12px; opacity: 0.7; }
    .amount-green { color: #16a34a; font-weight: 600; }
    .amount-red { color: #dc2626; font-weight: 600; }

    .empty-state {
        padding: 48px 24px;
        text-align: center;
        color: #7f8f9d;
    }

    .empty-icon { font-size: 40px; opacity: 0.3; margin-bottom: 12px; }

    @media (max-width: 980px) {
        .filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .filter-actions { grid-column: 1 / -1; }
        .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tables-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 600px) {
        .header {
            padding: 22px 20px;
        }
        .header-brand {
            align-items: flex-start;
        }
        .header-mark {
            width: 64px;
            height: 64px;
        }
        .header h1 { font-size: 24px; }
        .filter-form { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: 1fr; }
        .form-group { min-width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="wrap">
    @php
        $dashboardMarkLogoPath = '/root/Marcep/netbil/app/Modules/PettyCash/skybrix_pettycash_logo.png';
        $dashboardMarkLogo = is_file($dashboardMarkLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($dashboardMarkLogoPath)) : null;
    @endphp

    <!-- Header  -->
    <div class="header">
        <div class="header-brand">
            @if($dashboardMarkLogo)
                <div class="header-mark">
                    <img src="{{ $dashboardMarkLogo }}" alt="PettyCash">
                </div>
            @endif
            <div>
                <div class="header-kicker">Operations dashboard</div>
                <h1>PettyCash Dashboard</h1>
                <div class="header-sub">
                    <span>Fast cash control and field reporting</span>
                    <span>•</span>
                    @if($from || $to)
                        <span>Showing</span>
                        <span class="date-badge">{{ $from ?? '...' }}</span>
                        <span>to</span>
                        <span class="date-badge">{{ $to ?? '...' }}</span>
                    @else
                        <span class="date-badge">All time</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="header-status">
            <span>System</span>
            <strong>{{ $balance < 0 ? 'Needs attention' : 'Running clean' }}</strong>
        </div>
    </div>

    <!-- Compact Filters -->
    @php $hasDashboardFilter = filled(request('from')) || filled(request('to')); @endphp
    <div class="filter-bar">
        <form method="GET" action="{{ route('petty.dashboard') }}" class="filter-form">
            <div class="form-group">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input">
            </div>
            <div class="filter-actions">
                <button class="btn-primary" type="submit">Filter</button>
                <a class="btn-secondary" href="{{ route('petty.dashboard') }}">Reset</a>
            </div>
            <div class="filter-tip">
                {{ $hasDashboardFilter ? 'Custom date range is active.' : 'Use a date range only when you want to narrow the dashboard.' }}
            </div>
        </form>
    </div>

    <!-- Stats Grid -->
    @if($canViewDashboardSummary)
        <div class="stats-grid">
            <div class="stat-card credited">
                <div class="stat-label"><i class="bi bi-arrow-down-circle"></i> Total Credited</div>
                <div class="stat-value green">{{ number_format((float)$totalCredited, 2) }}</div>
                <div class="stat-meta">Money received</div>
            </div>

            <div class="stat-card spent">
                <div class="stat-label"><i class="bi bi-arrow-up-circle"></i> Total Spent</div>
                <div class="stat-value red">{{ number_format((float)$totalSpent, 2) }}</div>
                <div class="stat-meta">Money disbursed</div>
            </div>

            <div class="stat-card balance">
                <div class="stat-label"><i class="bi bi-wallet2"></i> Balance</div>
                <div class="stat-value {{ (float)$balance < 0 ? 'red' : 'green' }}">
                    {{ number_format((float)$balance, 2) }}
                </div>
                <div class="stat-meta">{{ (float)$balance < 0 ? 'Deficit' : 'Available' }}</div>
            </div>
        </div>
    @endif

    @if($canViewDashboardSummary)
        <div class="insight-grid">
            <div class="stat-card top">
                <div class="stat-label"><i class="bi bi-graph-up"></i> Most Spending</div>
                <div class="stat-value">
                    @if($topBucket)
                        {{ strtolower($topBucket['type']) }}
                    @else
                        -
                    @endif
                </div>
                <div class="stat-meta">
                    @if($topBucket)
                        @if(!empty($topBucket['sub_type']))
                            {{ strtoupper($topBucket['sub_type']) }} •
                        @endif
                        {{ number_format((float)$topBucket['total'], 2) }}
                    @else
                        No data
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Tables Row -->
    @if($canViewDashboardCategoryTotals || $canViewDashboardBreakdown)
        <div class="tables-row">
            @if($canViewDashboardCategoryTotals)
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title"><i class="bi bi-pie-chart"></i>  Totals by Category</div>
                        <div class="table-subtitle">Top-level totals (bikes, meals, token, others)</div>
                    </div>

                    <table>
                        <thead>
                        <tr>
                            <th>Category</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($typeTotals as $type => $total)
                            <tr>
                                <td>
                                    <span class="category-badge"><i class="bi bi-tag-fill"></i> {{ strtoupper($type) }}</span>
                                </td>
                                <td style="text-align:right;" class="amount-red">
                                    {{ number_format((float)$total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">
                                    <div class="empty-state">
                                        <div class="empty-icon">—</div>
                                        <div>No spendings yet</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if($canViewDashboardBreakdown)
                <div class="table-card">
                    <div class="table-header">
                        <div class="table-title"><i class="bi bi-list-task"></i> Spend Breakdown (Type / Subtype)</div>
                        <div class="table-subtitle">Shows bikes fuel vs maintenance, meals lunch, etc.</div>
                    </div>

                    <table>
                        <thead>
                        <tr>
                            <th>Bucket</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($byType as $row)
                            <tr>
                                <td>
                                    <span class="category-badge">
                                        <i class="bi bi-folder2-open"></i>
                                        {{ strtoupper($row['type']) }}
                                        @if(!empty($row['sub_type']))
                                            / {{ strtoupper($row['sub_type']) }}
                                        @endif
                                    </span>
                                </td>
                                <td style="text-align:right;" class="amount-red">
                                    {{ number_format((float)$row['total'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">
                                    <div class="empty-state">
                                        <div class="empty-icon">—</div>
                                        <div>No spendings yet</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
