@extends('pettycash::layouts.app')

@section('title','New Lunch')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>New Lunch Spending</h2>
            <div class="form-subtitle">Create a single payment or a mass disbursement over a date range.</div>
        </div>
        <a class="btn2" href="{{ route('petty.meals.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.meals.store') }}">
            @csrf

            <div class="pc-workflow" data-workflow>
                <section class="pc-step" data-step="meal-source" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Funding source</span>
                                <span class="pc-step-text">Pick the balance source before filling the meal payment details.</span>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px">
                            <span class="pc-step-meta" data-step-meta>Open</span>
                            <span class="pc-step-arrow">⌄</span>
                        </span>
                    </button>
                    <div class="pc-step-body" data-step-body>
                        <div class="pc-step-panel">
                            <div class="pc-field">
                                <label>Funding</label>
                                <select class="pc-select" name="funding" id="funding" required>
                                    <option value="auto" @selected(old('funding','auto')==='auto')>
                                        Auto (Use TOTAL balance )
                                    </option>
                                    <option value="single" @selected(old('funding')==='single')>
                                        Single Batch
                                    </option>
                                </select>
                                <div class="pc-help">
                                    Total available (net): <strong>{{ number_format((float)$totalBalance, 2) }}</strong>
                                </div>
                            </div>

                            <div class="pc-field" id="batchWrap" style="display:none;">
                                <label>Batch</label>
                                <select class="pc-select" name="batch_id" id="batch_id">
                                    <option value="">Select batch</option>
                                    @foreach($batches as $b)
                                        <option value="{{ $b->id }}" @selected((string)old('batch_id', $prefBatchId) === (string)$b->id)>
                                            {{ $b->batch_no }} (Balance: {{ number_format((float)$b->available_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn2" type="button" data-step-next>Proceed to Meal Type</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="meal-schedule">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">2</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Payment mode and dates</span>
                                <span class="pc-step-text">Choose one date or a date range so the system can derive the day count and total outflow.</span>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px">
                            <span class="pc-step-meta" data-step-meta>Locked</span>
                            <span class="pc-step-arrow">⌄</span>
                        </span>
                    </button>
                    <div class="pc-step-body" data-step-body hidden>
                        <div class="pc-step-panel">
                            <div class="pc-field full">
                                <label class="pc-check">
                                    <input type="checkbox" name="mass" value="1" id="mass" {{ old('mass') ? 'checked' : '' }}>
                                    Mass disbursement (choose date range)
                                </label>
                            </div>

                            <div class="pc-field" id="singleDate">
                                <label>Date Paid (single)</label>
                                <input class="pc-input" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}">
                            </div>

                            <div id="rangeDates" class="pc-inline-grid" style="display:none;">
                                <div class="pc-field">
                                    <label>Range From</label>
                                    <input class="pc-input" type="date" name="range_from" value="{{ old('range_from') }}">
                                </div>
                                <div class="pc-field">
                                    <label>Range To</label>
                                    <input class="pc-input" type="date" name="range_to" value="{{ old('range_to') }}">
                                </div>
                            </div>

                            <div class="pc-field full">
                                <label>Calculated schedule</label>
                                <input class="pc-input" type="text" id="mealScheduleSummary" value="1 day selected" readonly>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn2" type="button" data-step-next>Proceed to Payment Details</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="meal-payment">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">3</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Payment details</span>
                                <span class="pc-step-text">Finish with the reference, amount per day, fee, and required details.</span>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px">
                            <span class="pc-step-meta" data-step-meta>Locked</span>
                            <span class="pc-step-arrow">⌄</span>
                        </span>
                    </button>
                    <div class="pc-step-body" data-step-body hidden>
                        <div class="pc-step-panel">
                            <div class="pc-field">
                                <label>Mpesa REF</label>
                                <input class="pc-input" name="reference" required value="{{ old('reference') }}">
                            </div>

                            <div class="pc-field">
                                <label>Amount (per day if mass)</label>
                                <input class="pc-input" type="number" step="0.01" name="amount" required value="{{ old('amount') }}">
                            </div>

                            <div class="pc-field">
                                <label>Transaction Cost</label>
                                <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', 0) }}">
                            </div>

                            <div class="pc-field">
                                <label>Details</label>
                                <input class="pc-input" name="description" required value="{{ old('description') }}" placeholder="Why this meal spending was made">
                            </div>

                            <div class="pc-field full">
                                <label>Calculated total</label>
                                <input class="pc-input" type="text" id="mealAmountSummary" value="0.00" readonly>
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit">Save Lunch</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
const funding = document.getElementById('funding');
const batchWrap = document.getElementById('batchWrap');
const batchId = document.getElementById('batch_id');

function syncFunding(){
  const isSingle = funding.value === 'single';
  batchWrap.style.display = isSingle ? 'block' : 'none';
  if (!isSingle && batchId) batchId.value = '';
}
funding.addEventListener('change', syncFunding);
syncFunding();

    const mass = document.getElementById('mass');
    const singleDate = document.getElementById('singleDate');
    const rangeDates = document.getElementById('rangeDates');
    const rangeFromInput = document.querySelector('input[name="range_from"]');
    const rangeToInput = document.querySelector('input[name="range_to"]');
    const dateInput = document.querySelector('input[name="date"]');
    const amountInput = document.querySelector('input[name="amount"]');
    const feeInput = document.querySelector('input[name="transaction_cost"]');
    const mealScheduleSummary = document.getElementById('mealScheduleSummary');
    const mealAmountSummary = document.getElementById('mealAmountSummary');

    function parseDate(raw){
        if (!raw) return null;
        const parts = String(raw).split('-').map(Number);
        if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function countSelectedDays(){
        if (mass && mass.checked) {
            const start = parseDate(rangeFromInput ? rangeFromInput.value : '');
            const end = parseDate(rangeToInput ? rangeToInput.value : '');
            if (!start || !end) return 0;
            const from = start <= end ? start : end;
            const to = start <= end ? end : start;
            return Math.floor((to - from) / 86400000) + 1;
        }

        return (dateInput && dateInput.value) ? 1 : 0;
    }

    function syncTotals(){
        const dayCount = countSelectedDays();
        const amount = Number(amountInput ? amountInput.value || 0 : 0);
        const fee = Number(feeInput ? feeInput.value || 0 : 0);
        const total = dayCount > 0 ? ((amount + fee) * dayCount) : 0;

        if (mealScheduleSummary) {
            mealScheduleSummary.value = dayCount > 0
                ? (dayCount + ' day' + (dayCount === 1 ? '' : 's') + ' selected')
                : 'Select valid date(s)';
        }

        if (mealAmountSummary) {
            mealAmountSummary.value = total.toFixed(2);
        }
    }

    function sync(){
        if (mass && mass.checked){
            singleDate.style.display = 'none';
            rangeDates.style.display = 'grid';
        } else {
            singleDate.style.display = 'block';
            rangeDates.style.display = 'none';
        }

        syncTotals();
    }

    if (mass) mass.addEventListener('change', sync);
    [rangeFromInput, rangeToInput, dateInput, amountInput, feeInput].forEach(function (node) {
        if (node) node.addEventListener('input', syncTotals);
        if (node) node.addEventListener('change', syncTotals);
    });
    sync();
})();
</script>
@endpush
