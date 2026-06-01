@extends('pettycash::layouts.app')
@section('title','Spendings Ledger')

@push('styles')
<style>
    .wrap{max-width:1200px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:800}
    input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top}
    th{background:#fafafa;text-align:left;font-size:12px;color:#475467}
    .badge{display:inline-block;padding:4px 8px;border-radius:6px;background:#f2f4f7;font-size:12px}
    .num{text-align:right;white-space:nowrap}
    .small{font-size:12px;color:#667085}

    .topbar{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap}
    .actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .hero-copy{display:grid;gap:8px}
    .period-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#eef4ff;border:1px solid #c7d7fe;color:#3538cd;font-size:12px;font-weight:800}
    .period-chip b{color:#1d2939}
    .period-modal-copy{margin:4px 0 0;color:#667085;font-size:12px;line-height:1.5}
    .period-select-wrap{display:grid;gap:12px;margin-top:14px}
    .period-helper{font-size:12px;color:#667085;line-height:1.5}
    .custom-period-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}
    .custom-period-grid[hidden]{display:none !important}
    .modal-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:16px}
    .modal-actions .muted{max-width:380px}
    .legend-label{display:block;font-size:12px;color:#475467;font-weight:800;margin-bottom:6px}
    .pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.46);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px);display:none;align-items:center;justify-content:center;padding:18px}
    .pc-modal.show{display:flex}
    .pc-modal-panel{width:min(940px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:18px;border:1px solid #e7e9f2;box-shadow:0 22px 50px rgba(16,24,40,.25)}
    .pc-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid #eaecf0}
    .pc-modal-body{padding:16px 18px 18px}
    .pc-close{border:1px solid #d0d5dd;background:#fff;border-radius:10px;padding:7px 11px;font-weight:700;cursor:pointer}
    body.pc-modal-open{overflow:hidden}

    /* non-blocking status */
    .statusdot{display:inline-flex;align-items:center;gap:8px}
    .dot{width:8px;height:8px;border-radius:999px;background:#d0d5dd}
    .dot.live{background:#7f56d9}

    /* calc toggle */
    .check{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e4e7ec;border-radius:10px;background:#fff}
    .check input{margin:0}
    @media(max-width:900px){
        .custom-period-grid{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="topbar">
        <div class="hero-copy">
            <h2 style="margin:0">Unified Spendings Ledger</h2>
            <div class="muted">Includes: petty_spendings + petty_bike_services (service costs).</div>
            <div class="period-chip">Reporting Period <b>{{ $periodLabel }}</b></div>
        </div>

        <div class="actions">
            <div class="statusdot">
                <span id="liveDot" class="dot"></span>
                <span class="muted" id="liveText">Idle</span>
                <span class="muted">•</span>
                <span class="muted">Results: <strong id="resultCount">{{ $spendings instanceof \Illuminate\Pagination\LengthAwarePaginator ? $spendings->total() : $spendings->count() }}</strong></span>
            </div>

            <label class="check" title="If checked, PDF shows TOTALs and category summaries (bikes/meals/tokens/fuel).">
                <input id="calcToggle" type="checkbox" {{ !empty($calc) ? 'checked' : '' }}>
                <span class="muted" style="font-weight:800;color:#344054">Include calculations in PDF</span>
            </label>

            <button class="btn" type="button" id="openPeriodModalBtn">Generate Report</button>

            <a class="btn2" href="{{ route('petty.ledger.spendings') }}">Reset</a>
        </div>
    </div>

    <div class="card">
        <div class="pc-filter-dock">
            <details class="pc-filter-panel" open data-filter-pinned="1">
                <summary>
                    <span class="pc-filter-title">Filters</span>
                    <span class="pc-filter-state">ready</span>
                </summary>
                <div class="pc-filter-body">
                    <form id="ledgerForm" method="GET" class="row pc-filter-row" action="{{ route('petty.ledger.spendings') }}">
                        <input type="hidden" name="period" id="periodInput" value="{{ $period }}">
                        <div>
                            <div class="muted">From</div>
                            <input type="date" name="from" value="{{ $from }}">
                        </div>

                        <div>
                            <div class="muted">To</div>
                            <input type="date" name="to" value="{{ $to }}">
                        </div>

                        <div>
                            <div class="muted">Batch</div>
                            <select name="batch_id">
                                <option value="">All batches</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}" @selected((string)$batchId === (string)$b->id)>{{ $b->batch_no }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <div class="muted">Category</div>
                            <select name="type">
                                <option value="">All</option>
                                <option value="bike" @selected($type==='bike')>Bike</option>
                                <option value="fuel" @selected($type==='fuel')>Fuel</option>
                                <option value="meal" @selected($type==='meal')>Meal</option>
                                <option value="token" @selected($type==='token')>Token</option>
                                <option value="other" @selected($type==='other')>Other</option>
                            </select>
                        </div>

                        <div class="pc-filter-grow">
                            <input id="qInput" type="text" name="q" value="{{ $q }}"
                                   placeholder="Search MPESA ref" autocomplete="off">
                        </div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <button class="btn" type="button" id="applyLedgerFiltersBtn">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </details>
        </div>

        <div id="ledgerTableWrap">
            @include('pettycash::ledger._table', ['rows' => ($rows ?? $spendings ?? collect())])
        </div>
    </div>
</div>

<div class="pc-modal" id="periodModal" aria-hidden="true">
    <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Choose report period">
        <div class="pc-modal-head">
            <div>
                <h3 style="margin:0">Choose Report Period</h3>
                <div class="period-modal-copy">Pick the month or custom dates first. After that, you can continue with the normal ledger filters like batch, category, and search.</div>
            </div>
            <button type="button" class="pc-close" id="closePeriodModalBtn">Close</button>
        </div>
        <div class="pc-modal-body">
            <div class="period-select-wrap">
                <div>
                    <label class="legend-label" for="periodChoiceSelect">Report Period</label>
                    <select id="periodChoiceSelect">
                        <option value="this_month" @selected($period === 'this_month' || $period === '')>{{ $periodOptions['this_month']['label'] }}</option>
                        <option value="last_month" @selected($period === 'last_month')>{{ $periodOptions['last_month']['label'] }}</option>
                        <option value="custom" @selected($period === 'custom')>{{ $periodOptions['custom']['label'] }}</option>
                    </select>
                </div>
                <div class="period-helper" id="periodChoiceHelp">
                    Choose the month you want, or switch to custom dates if you want your own range.
                </div>
                <div>
                    <label class="legend-label" for="reportFormatSelect">Report Format</label>
                    <select id="reportFormatSelect">
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
            </div>

            <div class="custom-period-grid" id="customPeriodFields" @if($period !== 'custom') hidden @endif>
                <div>
                    <label class="legend-label" for="modalFromDate">Custom From</label>
                    <input id="modalFromDate" type="date" value="{{ $from }}">
                </div>
                <div>
                    <label class="legend-label" for="modalToDate">Custom To</label>
                    <input id="modalToDate" type="date" value="{{ $to }}">
                </div>
            </div>

            <div class="modal-actions">
                <div class="muted">Choose the period and format here, then download the report immediately without going back through the ledger filters.</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button class="btn2" type="button" id="cancelPeriodSelectionBtn">Cancel</button>
                    <button class="btn" type="button" id="applyPeriodSelectionBtn">Download Report</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const form = document.getElementById('ledgerForm');
    const wrap = document.getElementById('ledgerTableWrap');
    const dot = document.getElementById('liveDot');
    const liveText = document.getElementById('liveText');
    const resultCount = document.getElementById('resultCount');

    const calcToggle = document.getElementById('calcToggle');
    const periodInput = document.getElementById('periodInput');
    const applyLedgerFiltersBtn = document.getElementById('applyLedgerFiltersBtn');
    const periodModal = document.getElementById('periodModal');
    const openPeriodModalBtn = document.getElementById('openPeriodModalBtn');
    const closePeriodModalBtn = document.getElementById('closePeriodModalBtn');
    const cancelPeriodSelectionBtn = document.getElementById('cancelPeriodSelectionBtn');
    const applyPeriodSelectionBtn = document.getElementById('applyPeriodSelectionBtn');
    const customPeriodFields = document.getElementById('customPeriodFields');
    const periodChoiceSelect = document.getElementById('periodChoiceSelect');
    const periodChoiceHelp = document.getElementById('periodChoiceHelp');
    const reportFormatSelect = document.getElementById('reportFormatSelect');
    const modalFromDate = document.getElementById('modalFromDate');
    const modalToDate = document.getElementById('modalToDate');
    const periodOptions = @json($periodOptions);

    let activeController = null;

    function setBusy(isBusy){
        dot.classList.toggle('live', isBusy);
        liveText.textContent = isBusy ? 'Searching…' : 'Idle';
    }

    function debounce(fn, wait){
        let t;
        return function(...args){
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        }
    }

    function buildUrl(extra = {}){
        const fd = new FormData(form);
        const params = new URLSearchParams(fd);
        Object.keys(extra).forEach(k => params.set(k, extra[k]));
        return form.action + '?' + params.toString();
    }

    function lockBodyScroll() {
        document.body.classList.add('pc-modal-open');
    }

    function unlockBodyScrollIfNoModal() {
        if (!document.querySelector('.pc-modal.show')) {
            document.body.classList.remove('pc-modal-open');
        }
    }

    function openPeriodModal() {
        if (!periodModal) return;
        periodModal.classList.add('show');
        periodModal.setAttribute('aria-hidden', 'false');
        lockBodyScroll();
    }

    function closePeriodModal() {
        if (!periodModal) return;
        periodModal.classList.remove('show');
        periodModal.setAttribute('aria-hidden', 'true');
        unlockBodyScrollIfNoModal();
    }

    function selectedPeriodChoice() {
        return periodChoiceSelect ? periodChoiceSelect.value : 'this_month';
    }

    function syncPeriodCards() {
        const choice = selectedPeriodChoice();
        if (customPeriodFields) {
            customPeriodFields.hidden = choice !== 'custom';
        }
        if (periodChoiceHelp) {
            if (choice === 'this_month') {
                periodChoiceHelp.textContent = 'This will use the full current month automatically.';
            } else if (choice === 'last_month') {
                periodChoiceHelp.textContent = 'This will use the full previous month automatically.';
            } else {
                periodChoiceHelp.textContent = 'Choose your own start and end dates below.';
            }
        }
    }

    function setFormDates(from, to) {
        const fromInput = form.querySelector('input[name="from"]');
        const toInput = form.querySelector('input[name="to"]');
        if (fromInput) fromInput.value = from || '';
        if (toInput) toInput.value = to || '';
    }

    function buildReportDownloadUrl(choice, from, to) {
        const format = reportFormatSelect ? reportFormatSelect.value : 'pdf';
        const params = new URLSearchParams();
        params.set('period', choice);
        params.set('export', format);
        params.set('calc', calcToggle && calcToggle.checked ? '1' : '0');

        if (from) params.set('from', from);
        if (to) params.set('to', to);

        return form.action + '?' + params.toString();
    }

    function submitLedgerFilters() {
        doSearch();
        const filterPanel = document.querySelector('.pc-filter-panel');
        if (filterPanel) {
            filterPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    const doSearch = debounce(async () => {
        if (activeController) activeController.abort();
        activeController = new AbortController();

        const url = buildUrl();
        window.history.replaceState({}, '', url);
        setBusy(true);

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: activeController.signal
            });

            if (!res.ok) throw new Error('Request failed: ' + res.status);

            const data = await res.json();
            if (typeof data.html === 'string') wrap.innerHTML = data.html;
            if (typeof data.count !== 'undefined') resultCount.textContent = data.count;

        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            setBusy(false);
        }
    }, 250);

    if (periodChoiceSelect) periodChoiceSelect.addEventListener('change', syncPeriodCards);

    if (openPeriodModalBtn) openPeriodModalBtn.addEventListener('click', openPeriodModal);
    if (closePeriodModalBtn) closePeriodModalBtn.addEventListener('click', closePeriodModal);
    if (cancelPeriodSelectionBtn) cancelPeriodSelectionBtn.addEventListener('click', closePeriodModal);
    if (applyLedgerFiltersBtn) applyLedgerFiltersBtn.addEventListener('click', submitLedgerFilters);

    if (applyPeriodSelectionBtn) {
        applyPeriodSelectionBtn.addEventListener('click', function () {
            const choice = selectedPeriodChoice();
            if (periodInput) {
                periodInput.value = choice;
            }

            if (choice === 'custom') {
                const customFrom = modalFromDate ? modalFromDate.value : '';
                const customTo = modalToDate ? modalToDate.value : '';
                if (!customFrom || !customTo) {
                    window.pettyCreateToast?.({
                        type: 'warning',
                        title: 'Dates Needed',
                        message: 'Choose both custom start and end dates before continuing.',
                        dismissMs: 4200
                    });
                    return;
                }
                closePeriodModal();
                window.location.href = buildReportDownloadUrl(choice, customFrom, customTo);
                return;
            } else {
                const option = periodOptions[choice] || {};
                closePeriodModal();
                window.location.href = buildReportDownloadUrl(choice, option.from || '', option.to || '');
                return;
            }
        });
    }

    if (periodModal) {
        periodModal.addEventListener('click', function (event) {
            if (event.target === periodModal) {
                closePeriodModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && periodModal && periodModal.classList.contains('show')) {
            closePeriodModal();
        }
    });

    // initial
    syncPeriodCards();

    wrap.addEventListener('click', async function (event) {
        const link = event.target.closest('.petty-pager a');
        if (!(link instanceof HTMLAnchorElement)) return;

        event.preventDefault();

        if (activeController) activeController.abort();
        activeController = new AbortController();
        setBusy(true);

        try {
            const res = await fetch(link.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: activeController.signal
            });

            if (!res.ok) throw new Error('Request failed: ' + res.status);

            const data = await res.json();
            if (typeof data.html === 'string') wrap.innerHTML = data.html;
            if (typeof data.count !== 'undefined') resultCount.textContent = data.count;
            window.history.replaceState({}, '', link.href);
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            setBusy(false);
        }
    });
})();
</script>
@endsection
