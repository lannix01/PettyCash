@extends('pettycash::layouts.app')

@section('title', 'Meal Daily Bills')

@php
    $pettyUser = auth('petty')->user();
    $canCreateDaily = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'meals_daily.create');
    $canEditDaily = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'meals_daily.edit_bill');
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'meals_daily.record_payment');
    $canEditMealPayment = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'meals_daily.edit_payment');
    $canDeleteDaily = \App\Modules\PettyCash\Support\PettyAccess::isAdmin($pettyUser);
    $canDeleteMealPayment = \App\Modules\PettyCash\Support\PettyAccess::isAdmin($pettyUser);
    $openDailyBillModal = old('spending_date') !== null
        || old('range_from') !== null
        || old('range_to') !== null
        || old('day_entries') !== null;

    $oldDayEntries = old('day_entries', []);
    if (!is_array($oldDayEntries)) {
        $oldDayEntries = [];
    }
    $respondentOptions = $respondents->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'phone' => $r->phone])->values();
@endphp

@push('styles')
<style>
    .wrap{max-width:1260px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
    .top-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .bulk-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;background:#f8fafc;margin-bottom:10px}
    .bulk-actions[hidden]{display:none !important}
    .muted{color:#667085;font-size:12px}
    .pill{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .field{display:flex;flex-direction:column;gap:6px}
    .field label{font-size:12px;color:#475467;font-weight:700}
    .input,.select,select[multiple]{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px;background:#fff}
    select[multiple]{min-height:120px}

    .summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:10px}
    @media(max-width:760px){.summary-grid{grid-template-columns:1fr}}
    .summary-card{border:1px solid #eaecf0;border-radius:12px;padding:10px;background:#fcfcfd}
    .summary-k{font-size:11px;color:#667085;text-transform:uppercase;letter-spacing:.04em;font-weight:800}
    .summary-v{margin-top:4px;font-size:18px;font-weight:900;color:#101828}

    .ok{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px}
    .err{background:#fef3f2;border:1px solid #fecdca;color:#b42318;padding:10px;border-radius:10px}
    .hint{border:1px dashed #d0d5dd;background:#f9fafb;color:#344054;border-radius:10px;padding:10px;font-size:12px}

    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;text-align:left;vertical-align:top}
    th{font-size:12px;color:#475467;white-space:nowrap}
    .num{text-align:right;white-space:nowrap}

    .status-paid{display:inline-block;padding:3px 8px;border-radius:999px;background:#ecfdf3;color:#027a48;border:1px solid #abefc6;font-size:11px;font-weight:800}
    .status-open{display:inline-block;padding:3px 8px;border-radius:999px;background:#fffaeb;color:#b54708;border:1px solid #fedf89;font-size:11px;font-weight:800}

    .pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.58);display:none;align-items:center;justify-content:center;padding:18px}
    .pc-modal.show{display:flex}
    .pc-modal-panel{width:min(860px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #e7e9f2;box-shadow:0 22px 50px rgba(16,24,40,.25)}
    .pc-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #eaecf0}
    .pc-modal-body{padding:14px 16px}
    .pc-close{border:1px solid #d0d5dd;background:#fff;border-radius:10px;padding:6px 10px;font-weight:700;cursor:pointer}
    body.pc-modal-open{overflow:hidden}
    .form-grid-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    @media(max-width:760px){.form-grid-two{grid-template-columns:1fr}}
    .field-help{font-size:11px;color:#667085;line-height:1.35}
    .daily-people{min-height:160px}
    .daily-form-actions{margin-top:14px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}

    .pay-select-col{width:40px}
</style>
@endpush

@section('content')
<div class="wrap pc-list-shell" data-pc-list-root="meals-daily-index">
    <div class="pc-inline-refresh"><span class="spinner"></span><span>Refreshing records...</span></div>
    @if(session('success'))
        <div class="ok card" style="margin-top:0">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="err card" style="margin-top:0">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="top">
        <div>
            <h2 style="margin:0">Meal Daily Bills</h2>
            <div class="muted">Record daily bills first. Money is deducted only when payment is recorded.</div>
        </div>
        <div class="top-actions">
            @if($canCreateDaily)
                <button class="btn" type="button" id="openDailyBillModalBtn">Create Bill</button>
            @endif
            <a class="btn2" href="{{ route('petty.meals.index') }}">Lunch Spendings</a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-k">Logged Bills</div>
            <div class="summary-v">{{ number_format($totalLogged, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-k">Unpaid Bills</div>
            <div class="summary-v">{{ number_format($totalUnpaid, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-k">Actual Balance</div>
            <div class="summary-v">{{ number_format((float) $actualBalance, 2) }}</div>
            <div class="muted" style="margin-top:4px">
                Allocator net: {{ number_format((float) $totalBalance, 2) }}
                @if((float) $serviceSpentNet > 0)
                    • Services deducted: {{ number_format((float) $serviceSpentNet, 2) }}
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="pc-filter-dock">
            <details class="pc-filter-panel" open data-filter-pinned="1">
                <summary>
                    <span class="pc-filter-title">Filters</span>
                    <span class="pc-filter-state">live</span>
                </summary>
                <div class="pc-filter-body">
                    <form method="GET" class="row pc-filter-row" action="{{ route('petty.meals.daily.index') }}" data-pc-auto-filter="1" data-pc-list-root-id="meals-daily-index">
                        <div class="pc-filter-grow">
                            <div class="muted">Search</div>
                            <input type="search" name="q" value="{{ $q }}" placeholder="People, notes, payment ref, receiver">
                        </div>
                        <div>
                            <div class="muted">Involved Person</div>
                            <select name="respondent_id">
                                <option value="">All</option>
                                @foreach($respondents as $r)
                                    <option value="{{ $r->id }}" @selected((string) $respondentId === (string) $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div class="muted">Status</div>
                            <select name="status">
                                <option value="all" @selected(($status ?? 'all') === 'all')>All</option>
                                <option value="unpaid" @selected(($status ?? 'all') === 'unpaid')>Unpaid</option>
                                <option value="paid" @selected(($status ?? 'all') === 'paid')>Paid</option>
                            </select>
                        </div>
                        <div>
                            <div class="muted">From</div>
                            <input type="date" name="from" value="{{ $from }}">
                        </div>
                        <div>
                            <div class="muted">To</div>
                            <input type="date" name="to" value="{{ $to }}">
                        </div>
                        <div>
                            <div class="muted">Sort</div>
                            <select name="sort">
                                <option value="date_desc" @selected($sort === 'date_desc')>Newest First</option>
                                <option value="date_asc" @selected($sort === 'date_asc')>Oldest First</option>
                                <option value="amount_desc" @selected($sort === 'amount_desc')>Amount High-Low</option>
                                <option value="amount_asc" @selected($sort === 'amount_asc')>Amount Low-High</option>
                            </select>
                        </div>
                        <div class="pc-filter-actions">
                            <button class="btn" type="submit">Apply</button>
                            <a class="btn2" href="{{ route('petty.meals.daily.index') }}">Reset</a>
                        </div>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <div class="card">
        <div class="top" style="margin-bottom:8px">
            <h3 style="margin:0">Daily Bills</h3>
            <div class="muted">Unpaid records can be paid singly or in bulk.</div>
        </div>
        @if($canRecordPayment)
            <div class="bulk-actions" id="bulkActionBar" hidden>
                <div class="muted"><strong id="bulkSelectedCount">0</strong> selected on this page</div>
                <div class="top-actions">
                    <button class="btn" type="button" id="paySelectedBtn">Pay Selected</button>
                    <button class="btn2" type="button" id="clearSelectedBtn">Clear Selection</button>
                </div>
            </div>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    @if($canRecordPayment)
                        <th class="pay-select-col">
                            <input type="checkbox" id="selectAllUnpaid" title="Select all unpaid on current page">
                        </th>
                    @endif
                    <th>Date</th>
                    <th class="num">Amount</th>
                    <th>People Involved</th>
                    <th>Notes</th>
                    <th>Status</th>
                    @if($canRecordPayment)
                        <th>Actions</th>
                    @elseif($canEditDaily || $canDeleteDaily)
                        <th>Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($dailySpendings as $row)
                    @php
                        $peopleMap = [];
                        if (!empty($row->respondent?->name)) {
                            $peopleMap[$row->respondent->name] = $row->respondent->name;
                        }
                        foreach ($row->respondents as $person) {
                            if (!empty($person->name)) {
                                $peopleMap[$person->name] = $person->name;
                            }
                        }
                        $people = implode(', ', array_values($peopleMap));
                    @endphp
                    <tr>
                        @if($canRecordPayment)
                            <td>
                                @if(!$row->meal_payment_id)
                                    <input type="checkbox" class="daily-select" value="{{ $row->id }}">
                                @endif
                            </td>
                        @endif
                        <td>{{ $row->spending_date?->format('Y-m-d') }}</td>
                        <td class="num">{{ number_format((float) $row->amount, 2) }}</td>
                        <td>{{ $people !== '' ? $people : '-' }}</td>
                        <td>{{ $row->notes ?: '-' }}</td>
                        <td>
                            @if($row->meal_payment_id)
                                <span class="status-paid">Paid</span>
                                <div class="muted">
                                    {{ $row->payment?->date?->format('Y-m-d') ?? '-' }}
                                    @if($row->payment?->reference)
                                        • {{ $row->payment->reference }}
                                    @endif
                                </div>
                            @else
                                <span class="status-open">Unpaid</span>
                            @endif
                        </td>
                        @if($canRecordPayment)
                            <td>
                                @if(!$row->meal_payment_id)
                                    <button class="btn2 pay-single-btn" type="button" data-meal-id="{{ $row->id }}">Pay</button>
                                    @if($canEditDaily)
                                        <a class="btn2" href="{{ route('petty.meals.daily.edit', $row->id) }}">Edit</a>
                                    @endif
                                    @if($canDeleteDaily)
                                        <form method="POST" action="{{ route('petty.meals.daily.destroy', $row->id) }}" style="display:inline-block;margin-left:6px" data-confirm="Delete this daily meal bill?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn2" type="submit">Delete</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        @elseif($canEditDaily || $canDeleteDaily)
                            <td>
                                @if(!$row->meal_payment_id)
                                    @if($canEditDaily)
                                        <a class="btn2" href="{{ route('petty.meals.daily.edit', $row->id) }}">Edit</a>
                                    @endif
                                    @if($canDeleteDaily)
                                        <form method="POST" action="{{ route('petty.meals.daily.destroy', $row->id) }}" style="display:inline-block;margin-left:6px" data-confirm="Delete this daily meal bill?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn2" type="submit">Delete</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="muted">Paid bill</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($canRecordPayment || $canEditDaily || $canDeleteDaily) ? 7 : 5 }}" class="muted">No daily meal bills found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px">{{ $dailySpendings->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
    </div>

    <div class="card">
        <h3 style="margin:0 0 8px">Recent Payments</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Range</th>
                    <th class="num">Entries</th>
                    <th class="num">Days</th>
                    <th class="num">Amount</th>
                    <th class="num">Fee</th>
                    <th class="num">Total</th>
                    <th>People</th>
                    <th>Reference</th>
                    <th>Batch</th>
                    @if($canEditMealPayment || $canDeleteMealPayment)
                        <th>Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($payments as $p)
                    @php
                        $amount = (float) $p->amount;
                        $fee = (float) ($p->transaction_cost ?? 0);

                        $peopleMap = [];
                        if (!empty($p->respondent?->name)) {
                            $peopleMap[$p->respondent->name] = $p->respondent->name;
                        }

                        foreach ($p->dailySpendings as $dailyRow) {
                            if (!empty($dailyRow->respondent?->name)) {
                                $peopleMap[$dailyRow->respondent->name] = $dailyRow->respondent->name;
                            }
                            foreach ($dailyRow->respondents as $person) {
                                if (!empty($person->name)) {
                                    $peopleMap[$person->name] = $person->name;
                                }
                            }
                        }

                        $people = implode(', ', array_values($peopleMap));
                    @endphp
                    <tr>
                        <td>{{ $p->date?->format('Y-m-d') }}</td>
                        <td>{{ $p->range_from?->format('Y-m-d') }} → {{ $p->range_to?->format('Y-m-d') }}</td>
                        <td class="num">{{ (int) $p->dailySpendings->count() }}</td>
                        <td class="num">{{ (int) $p->days_count }}</td>
                        <td class="num">{{ number_format($amount, 2) }}</td>
                        <td class="num">{{ number_format($fee, 2) }}</td>
                        <td class="num"><strong>{{ number_format($amount + $fee, 2) }}</strong></td>
                        <td>{{ $people !== '' ? $people : '-' }}</td>
                        <td>
                            {{ $p->reference ?? '-' }}
                            @if($p->receiver_name)
                                <div class="muted">{{ $p->receiver_name }} @if($p->receiver_phone) ({{ $p->receiver_phone }}) @endif</div>
                            @endif
                        </td>
                        <td>{{ $p->batch?->batch_no ?? '-' }}</td>
                        @if($canEditMealPayment || $canDeleteMealPayment)
                            <td>
                                @if($canEditMealPayment)
                                    <a href="{{ route('petty.meals.daily.payments.edit', $p->id) }}">Edit</a>
                                @endif
                                @if($canDeleteMealPayment)
                                    <form method="POST" action="{{ route('petty.meals.daily.payments.destroy', $p->id) }}" style="display:inline-block;margin-left:8px" data-confirm="Delete this meal payment?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="border:none;background:none;color:#b42318;cursor:pointer;padding:0">Delete</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ ($canEditMealPayment || $canDeleteMealPayment) ? 11 : 10 }}" class="muted">No meal payments recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canCreateDaily)
    <div class="pc-modal" id="dailyBillModal" aria-hidden="true">
        <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Record daily meal bill">
            <div class="pc-modal-head">
                <div>
                    <h3 style="margin:0">Record Daily Bill</h3>
                    <div class="muted">Build the date range into daily rows, then enter the amount, people, and details for each day.</div>
                </div>
                <button type="button" class="pc-close" data-daily-close>Close</button>
            </div>
            <div class="pc-modal-body">
                <form method="POST" action="{{ route('petty.meals.daily.store') }}" id="dailyBillForm">
                    @csrf

                    <div class="form-grid-two">
                        <div class="field">
                            <label>Range From</label>
                            <input class="input" type="date" name="range_from" required value="{{ old('range_from', now()->toDateString()) }}">
                            <div class="field-help">Start day for this meal bill run.</div>
                        </div>
                        <div class="field">
                            <label>Range To</label>
                            <input class="input" type="date" name="range_to" required value="{{ old('range_to', now()->toDateString()) }}">
                            <div class="field-help">End day for this meal bill run.</div>
                        </div>
                    </div>

                    <div class="hint" style="margin-top:10px">
                        Build one row per day so the amount eaten, respondents, and notes are all auditable.
                    </div>

                    <div class="daily-form-actions" style="justify-content:space-between">
                        <div class="muted" id="dailyBillEntriesMeta">0 days prepared</div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <button class="btn2" type="button" id="buildDailyRowsBtn">Build Daily Rows</button>
                            <button class="btn2" type="button" id="clearDailyRowsBtn">Clear Rows</button>
                        </div>
                    </div>

                    <div class="field" style="margin-top:10px">
                        <label>Total Preview</label>
                        <input class="input" type="text" id="dailyBillTotalPreview" value="0.00" readonly>
                        <div class="field-help">Calculated from the row amounts below.</div>
                    </div>

                    <div id="dailyBillEntriesWrap" style="margin-top:12px;display:grid;gap:12px"></div>

                    <div class="daily-form-actions">
                        <button class="btn2" type="button" data-daily-close>Cancel</button>
                        <button class="btn" type="submit">Save Daily Bill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canRecordPayment)
    <div class="pc-modal" id="mealPayModal" aria-hidden="true">
        <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Record meal payment">
            <div class="pc-modal-head">
                <h3 style="margin:0">Record Meal Payment</h3>
                <button type="button" class="pc-close" data-pay-close>Close</button>
            </div>
            <div class="pc-modal-body">
                <form method="POST" action="{{ route('petty.meals.daily.payments.store') }}" id="mealPayForm">
                    @csrf

                    <div id="selectedDailyIds"></div>

                    <div class="summary-grid" style="margin-top:0">
                        <div class="summary-card">
                            <div class="summary-k">Selected Entries</div>
                            <div class="summary-v" id="payEntries">0</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-k">Unique Days</div>
                            <div class="summary-v" id="payDays">0</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-k">Amount</div>
                            <div class="summary-v" id="payAmount">0.00</div>
                        </div>
                    </div>

                    <div class="hint" style="margin-top:10px">
                        <div>Date range: <strong id="payRange">-</strong></div>
                        <div style="margin-top:4px">People: <span id="payPeople">-</span></div>
                    </div>

                    <div class="row" style="margin-top:12px">
                        <div class="field" style="flex:1 1 180px">
                            <label>Funding</label>
                            <select class="select" name="funding" id="payFunding" required>
                                <option value="auto" @selected(old('funding', 'auto') === 'auto')>Auto (use available balance)</option>
                                <option value="single" @selected(old('funding') === 'single')>Single batch</option>
                            </select>
                        </div>
                        <div class="field" id="payBatchWrap" style="flex:1 1 180px;display:none;">
                            <label>Batch</label>
                            <select class="select" name="batch_id" id="payBatchId">
                                <option value="">Select batch</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}" @selected((string) old('batch_id') === (string) $b->id)>
                                        {{ $b->batch_no }} (Balance: {{ number_format((float) $b->available_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row" style="margin-top:10px">
                        <div class="field" style="flex:1 1 180px">
                            <label>Payment Date</label>
                            <input class="input" type="date" name="date" required value="{{ old('date', now()->toDateString()) }}">
                        </div>
                        <div class="field" style="flex:1 1 180px">
                            <label>Transaction Cost</label>
                            <input class="input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', 0) }}">
                        </div>
                    </div>

                    <div class="field" style="margin-top:10px">
                        <label>MPESA Ref</label>
                        <input class="input" name="reference" required value="{{ old('reference') }}">
                    </div>

                    <div class="row" style="margin-top:10px">
                        <div class="field" style="flex:1 1 180px">
                            <label>Receiver Name</label>
                            <input class="input" name="receiver_name" required value="{{ old('receiver_name') }}">
                        </div>
                        <div class="field" style="flex:1 1 180px">
                            <label>Receiver Phone</label>
                            <input class="input" name="receiver_phone" required value="{{ old('receiver_phone') }}">
                        </div>
                    </div>

                    <div class="field" style="margin-top:10px">
                        <label>Description Mode</label>
                        <select class="select" name="description_mode" id="mealPaymentDescriptionMode" required>
                            <option value="auto" @selected(old('description_mode', 'auto') === 'auto')>Auto description</option>
                            <option value="manual" @selected(old('description_mode') === 'manual')>Manual description</option>
                        </select>
                    </div>

                    <div class="field" style="margin-top:10px">
                        <label>Description</label>
                        <input class="input" name="description" id="mealPaymentDescriptionInput" value="{{ old('description') }}" placeholder="Type your own description when manual mode is selected">
                        <div class="field-help" id="mealPaymentDescriptionHelp">Auto mode builds a fixed description from the selected people, date range, days, and total.</div>
                    </div>

                    <div class="field" style="margin-top:10px">
                        <label>Notes</label>
                        <input class="input" name="notes" required value="{{ old('notes') }}">
                    </div>

                    <div class="row" style="margin-top:14px;justify-content:flex-end">
                        <button class="btn2" type="button" data-pay-close>Cancel</button>
                        <button class="btn" type="submit">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const canCreateDaily = @json($canCreateDaily);
    const canRecordPayment = @json($canRecordPayment);
    const openDailyBillModal = @json($openDailyBillModal);

    function lockBodyScroll() {
        document.body.classList.add('pc-modal-open');
    }

    function unlockBodyScrollIfNoModal() {
        if (!document.querySelector('.pc-modal.show')) {
            document.body.classList.remove('pc-modal-open');
        }
    }

    const dailyBillModal = document.getElementById('dailyBillModal');
    const openDailyBillModalBtn = document.getElementById('openDailyBillModalBtn');
    const dailyBillForm = document.getElementById('dailyBillForm');
    const dailyBillRangeFrom = dailyBillForm ? dailyBillForm.querySelector('input[name="range_from"]') : null;
    const dailyBillRangeTo = dailyBillForm ? dailyBillForm.querySelector('input[name="range_to"]') : null;
    const dailyBillTotalPreview = document.getElementById('dailyBillTotalPreview');
    const dailyBillEntriesWrap = document.getElementById('dailyBillEntriesWrap');
    const dailyBillEntriesMeta = document.getElementById('dailyBillEntriesMeta');
    const buildDailyRowsBtn = document.getElementById('buildDailyRowsBtn');
    const clearDailyRowsBtn = document.getElementById('clearDailyRowsBtn');
    const respondentOptions = @json($respondentOptions);
    const oldDayEntries = @json($oldDayEntries);

    function parseDate(raw) {
        if (!raw) return null;
        const parts = String(raw).split('-').map(Number);
        if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncDailyBillPreview() {
        if (!dailyBillTotalPreview) return;
        let total = 0;
        if (dailyBillEntriesWrap) {
            dailyBillEntriesWrap.querySelectorAll('input[data-daily-amount]').forEach((input) => {
                total += Number(input.value || 0);
            });
        }
        dailyBillTotalPreview.value = formatMoney(total);
        if (dailyBillEntriesMeta) {
            const count = dailyBillEntriesWrap ? dailyBillEntriesWrap.querySelectorAll('[data-daily-entry]').length : 0;
            dailyBillEntriesMeta.textContent = count + ' day' + (count === 1 ? '' : 's') + ' prepared';
        }
    }

    function dateToString(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function buildRespondentSelect(index, selectedIds) {
        return `
            <select class="daily-people" name="day_entries[${index}][respondent_ids][]" multiple required>
                ${respondentOptions.map((person) => {
                    const label = person.phone ? `${person.name} (${person.phone})` : person.name;
                    const selected = selectedIds.includes(String(person.id)) ? ' selected' : '';
                    return `<option value="${person.id}"${selected}>${escapeHtml(label)}</option>`;
                }).join('')}
            </select>
        `;
    }

    function buildEntryCard(entry, index) {
        const respondentIds = Array.isArray(entry.respondent_ids) ? entry.respondent_ids.map(String) : [];
        return `
            <div class="summary-card" data-daily-entry>
                <div class="summary-k">Day ${index + 1}</div>
                <div class="form-grid-two" style="margin-top:8px">
                    <div class="field">
                        <label>Date</label>
                        <input class="input" type="date" name="day_entries[${index}][date]" value="${escapeHtml(entry.date || '')}" required readonly>
                    </div>
                    <div class="field">
                        <label>Amount Eaten</label>
                        <input class="input" type="number" step="0.01" min="0.01" name="day_entries[${index}][amount]" value="${escapeHtml(entry.amount || '')}" data-daily-amount required>
                    </div>
                </div>
                <div class="field" style="margin-top:10px">
                    <label>Respondents For This Day</label>
                    ${buildRespondentSelect(index, respondentIds)}
                </div>
                <div class="field" style="margin-top:10px">
                    <label>Daily Details</label>
                    <input class="input" name="day_entries[${index}][notes]" value="${escapeHtml(entry.notes || '')}" placeholder="What was consumed or why this amount was used?" required>
                </div>
            </div>
        `;
    }

    function renderDailyEntries(entries) {
        if (!dailyBillEntriesWrap) return;
        dailyBillEntriesWrap.innerHTML = entries.map((entry, index) => buildEntryCard(entry, index)).join('');
        dailyBillEntriesWrap.querySelectorAll('input[data-daily-amount]').forEach((input) => {
            input.addEventListener('input', syncDailyBillPreview);
            input.addEventListener('change', syncDailyBillPreview);
        });
        syncDailyBillPreview();
    }

    function buildEntriesFromRange() {
        const from = parseDate(dailyBillRangeFrom ? dailyBillRangeFrom.value : '');
        const to = parseDate(dailyBillRangeTo ? dailyBillRangeTo.value : '');
        if (!from || !to) return;

        const start = from <= to ? from : to;
        const end = from <= to ? to : from;
        const entries = [];
        const cursor = new Date(start);

        while (cursor <= end) {
            entries.push({
                date: dateToString(cursor),
                amount: '',
                notes: '',
                respondent_ids: [],
            });
            cursor.setDate(cursor.getDate() + 1);
        }

        renderDailyEntries(entries);
    }

    function openDailyModal() {
        if (!dailyBillModal) return;
        dailyBillModal.classList.add('show');
        dailyBillModal.setAttribute('aria-hidden', 'false');
        lockBodyScroll();
    }

    function closeDailyModal() {
        if (!dailyBillModal) return;
        dailyBillModal.classList.remove('show');
        dailyBillModal.setAttribute('aria-hidden', 'true');
        unlockBodyScrollIfNoModal();
    }

    if (canCreateDaily && dailyBillModal) {
        if (openDailyBillModalBtn) {
            openDailyBillModalBtn.addEventListener('click', openDailyModal);
        }

        document.querySelectorAll('[data-daily-close]').forEach(el => {
            el.addEventListener('click', closeDailyModal);
        });

        dailyBillModal.addEventListener('click', function (e) {
            if (e.target === dailyBillModal) {
                closeDailyModal();
            }
        });

        if (openDailyBillModal) {
            openDailyModal();
        }

        if (buildDailyRowsBtn) {
            buildDailyRowsBtn.addEventListener('click', buildEntriesFromRange);
        }
        if (clearDailyRowsBtn) {
            clearDailyRowsBtn.addEventListener('click', function () {
                renderDailyEntries([]);
            });
        }

        if (Array.isArray(oldDayEntries) && oldDayEntries.length > 0) {
            renderDailyEntries(oldDayEntries);
        } else {
            syncDailyBillPreview();
        }
    }

    if (!canRecordPayment) return;

    const modal = document.getElementById('mealPayModal');
    const selectedIdsWrap = document.getElementById('selectedDailyIds');
    const paySelectedBtn = document.getElementById('paySelectedBtn');
    const clearSelectedBtn = document.getElementById('clearSelectedBtn');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const bulkSelectedCount = document.getElementById('bulkSelectedCount');
    const selectAll = document.getElementById('selectAllUnpaid');
    const dailyChecks = Array.from(document.querySelectorAll('.daily-select'));
    const paySingleBtns = Array.from(document.querySelectorAll('.pay-single-btn'));

    const payEntries = document.getElementById('payEntries');
    const payDays = document.getElementById('payDays');
    const payAmount = document.getElementById('payAmount');
    const payRange = document.getElementById('payRange');
    const payPeople = document.getElementById('payPeople');
    const descriptionMode = document.getElementById('mealPaymentDescriptionMode');
    const descriptionInput = document.getElementById('mealPaymentDescriptionInput');
    const descriptionHelp = document.getElementById('mealPaymentDescriptionHelp');

    const funding = document.getElementById('payFunding');
    const batchWrap = document.getElementById('payBatchWrap');
    const batchId = document.getElementById('payBatchId');

    const calcEndpoint = @json(route('petty.meals.daily.calculate', [], false));

    function syncFunding() {
        if (!funding || !batchWrap) return;
        const isSingle = funding.value === 'single';
        batchWrap.style.display = isSingle ? 'block' : 'none';
        if (!isSingle && batchId) batchId.value = '';
    }

    function getSelectedIds() {
        return dailyChecks.filter(c => c.checked).map(c => Number(c.value)).filter(v => v > 0);
    }

    function syncBulkActions() {
        const selectedCount = getSelectedIds().length;
        if (bulkActionBar) {
            bulkActionBar.hidden = selectedCount === 0;
        }
        if (bulkSelectedCount) {
            bulkSelectedCount.textContent = String(selectedCount);
        }
    }

    function syncSelectAllState() {
        if (!selectAll) return;
        const selected = getSelectedIds();
        selectAll.checked = dailyChecks.length > 0 && selected.length === dailyChecks.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < dailyChecks.length;
    }

    function setSummaryFallback() {
        if (payEntries) payEntries.textContent = '0';
        if (payDays) payDays.textContent = '0';
        if (payAmount) payAmount.textContent = '0.00';
        if (payRange) payRange.textContent = '-';
        if (payPeople) payPeople.textContent = '-';
        syncDescriptionMode();
    }

    function currentAutoDescription() {
        const people = String(payPeople ? payPeople.textContent || '-' : '-').trim();
        const range = String(payRange ? payRange.textContent || '-' : '-').trim();
        const days = String(payDays ? payDays.textContent || '0' : '0').trim();
        const total = String(payAmount ? payAmount.textContent || '0.00' : '0.00').trim();
        return 'Meal bill for ' + people + ', for ' + range + ', ' + days + ' day(s), total ' + total;
    }

    function syncDescriptionMode() {
        if (!descriptionMode || !descriptionInput) return;
        const isManual = descriptionMode.value === 'manual';
        descriptionInput.required = isManual;
        descriptionInput.readOnly = !isManual;
        if (descriptionHelp) {
            descriptionHelp.textContent = isManual
                ? 'Manual mode requires a typed description before saving.'
                : 'Auto mode builds a fixed description from the selected people, date range, days, and total.';
        }
        if (!isManual) {
            descriptionInput.value = currentAutoDescription();
        } else if (descriptionInput.value === currentAutoDescription()) {
            descriptionInput.value = '';
        }
    }

    function setHiddenIds(ids) {
        if (!selectedIdsWrap) return;
        selectedIdsWrap.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'daily_ids[]';
            input.value = String(id);
            selectedIdsWrap.appendChild(input);
        });
    }

    function openModal() {
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        lockBodyScroll();
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        unlockBodyScrollIfNoModal();
    }

    function calculateAndOpen(ids) {
        if (!ids.length) return;

        setHiddenIds(ids);
        setSummaryFallback();

        const params = new URLSearchParams();
        ids.forEach(id => params.append('daily_ids[]', String(id)));

        fetch(calcEndpoint + '?' + params.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Could not load payment details. Please refresh and try again.');
                }
                return res.json();
            })
            .then(json => {
                if (!json.ok || !json.has_rows) {
                    throw new Error(json.message || 'No unpaid rows found for selected records.');
                }

                if (payEntries) payEntries.textContent = String(json.entries_count || 0);
                if (payDays) payDays.textContent = String(json.days_count || 0);
                if (payAmount) payAmount.textContent = Number(json.amount || 0).toFixed(2);
                if (payRange) {
                    if (json.range_from && json.range_to) payRange.textContent = json.range_from + ' to ' + json.range_to;
                    else payRange.textContent = '-';
                }
                if (payPeople) {
                    const people = Array.isArray(json.people) ? json.people : [];
                    payPeople.textContent = people.length ? people.join(', ') : '-';
                }

                syncDescriptionMode();
                openModal();
            })
            .catch(err => {
                alert(err.message || 'Unable to calculate selected records.');
            });
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const checked = Boolean(this.checked);
            dailyChecks.forEach(c => {
                c.checked = checked;
            });
            syncBulkActions();
            syncSelectAllState();
        });
    }

    dailyChecks.forEach(c => {
        c.addEventListener('change', function () {
            syncBulkActions();
            syncSelectAllState();
        });
    });

    if (paySelectedBtn) {
        paySelectedBtn.addEventListener('click', function () {
            calculateAndOpen(getSelectedIds());
        });
    }

    if (clearSelectedBtn) {
        clearSelectedBtn.addEventListener('click', function () {
            dailyChecks.forEach(c => {
                c.checked = false;
            });
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            syncBulkActions();
        });
    }

    paySingleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const id = Number(this.dataset.mealId || 0);
            if (id > 0) {
                calculateAndOpen([id]);
            }
        });
    });

    document.querySelectorAll('[data-pay-close]').forEach(el => {
        el.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    if (funding) {
        funding.addEventListener('change', syncFunding);
    }
    if (descriptionMode) {
        descriptionMode.addEventListener('change', syncDescriptionMode);
    }

    syncFunding();
    syncDescriptionMode();
    syncBulkActions();
    syncSelectAllState();

    const oldSelectedIds = @json(array_values($selectedDailyIds ?? []));
    if (Array.isArray(oldSelectedIds) && oldSelectedIds.length) {
        calculateAndOpen(oldSelectedIds.map(v => Number(v)).filter(v => v > 0));
    }
})();
</script>
@endpush
