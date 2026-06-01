@extends('pettycash::layouts.app')
@section('title','Edit Meal Payment')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Edit Meal Payment</h2>
            <div class="form-subtitle">Update the saved payment directly. This edit path does not run balance blocking checks.</div>
        </div>
        <a class="btn2" href="{{ route('petty.meals.daily.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.meals.daily.payments.update', $payment->id) }}">
            @csrf
            @method('PUT')

            <div class="pc-workflow" data-workflow data-workflow-unlock-all="1">
                <section class="pc-step" data-step="meal-payment-edit-main" data-step-open="1" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Payment details</span>
                                <span class="pc-step-text">Change the payment figures, batch, and details, then keep the linked spending row aligned.</span>
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
                                <label>Batch</label>
                                <select class="pc-select" name="batch_id" required>
                                    @foreach($batches as $b)
                                        <option value="{{ $b->id }}" @selected((string) old('batch_id', $payment->batch_id) === (string) $b->id)>
                                            {{ $b->batch_no }} (Balance: {{ number_format((float) $b->available_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pc-field">
                                <label>Payment Date</label>
                                <input class="pc-input" type="date" name="date" required value="{{ old('date', optional($payment->date)->format('Y-m-d')) }}">
                            </div>

                            <div class="pc-field">
                                <label>Reference</label>
                                <input class="pc-input" name="reference" required value="{{ old('reference', $payment->reference) }}">
                            </div>

                            <div class="pc-field">
                                <label>Amount</label>
                                <input class="pc-input" type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $payment->amount) }}">
                            </div>

                            <div class="pc-field">
                                <label>Transaction Cost</label>
                                <input class="pc-input" type="number" step="0.01" min="0" name="transaction_cost" required value="{{ old('transaction_cost', $payment->transaction_cost ?? 0) }}">
                            </div>

                            <div class="pc-field">
                                <label>Receiver Name</label>
                                <input class="pc-input" name="receiver_name" required value="{{ old('receiver_name', $payment->receiver_name) }}">
                            </div>

                            <div class="pc-field">
                                <label>Receiver Phone</label>
                                <input class="pc-input" name="receiver_phone" required value="{{ old('receiver_phone', $payment->receiver_phone) }}">
                            </div>

                            <div class="pc-field full">
                                <label>Description Mode</label>
                                <select class="pc-select" name="description_mode" id="mealPaymentEditDescriptionMode" required>
                                    <option value="auto" @selected(old('description_mode', 'auto') === 'auto')>Auto description</option>
                                    <option value="manual" @selected(old('description_mode') === 'manual')>Manual description</option>
                                </select>
                            </div>

                            <div class="pc-field full">
                                <label>Description</label>
                                <input class="pc-input" name="description" id="mealPaymentEditDescriptionInput" value="{{ old('description', $payment->spending?->description) }}">
                                <div class="pc-help" id="mealPaymentEditDescriptionHelp">Auto mode builds the fixed description from the selected bill range and people.</div>
                            </div>

                            <div class="pc-field full">
                                <label>Notes</label>
                                <input class="pc-input" name="notes" required value="{{ old('notes', $payment->notes) }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit">Update Meal Payment</button>
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
(function () {
    const mode = document.getElementById('mealPaymentEditDescriptionMode');
    const input = document.getElementById('mealPaymentEditDescriptionInput');
    const help = document.getElementById('mealPaymentEditDescriptionHelp');
    const autoDescription = @json($descriptionPreview);

    function sync() {
        if (!mode || !input) return;
        const isManual = mode.value === 'manual';
        input.required = isManual;
        input.readOnly = !isManual;
        if (help) {
            help.textContent = isManual
                ? 'Manual mode requires a typed description before saving.'
                : 'Auto mode builds the fixed description from the selected bill range and people.';
        }
        if (!isManual) {
            input.value = autoDescription;
        } else if (input.value === autoDescription) {
            input.value = '';
        }
    }

    if (mode) {
        mode.addEventListener('change', sync);
    }

    sync();
})();
</script>
@endpush
