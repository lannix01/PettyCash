@extends('pettycash::layouts.app')
@section('title','Edit Hostel Payment')

@section('content')
@php
    $agreementType = strtolower(trim((string) ($hostel->agreement_type ?? 'none')));
    $isTokenAgreement = $agreementType === 'token';
    $isSendMoneyAgreement = $agreementType === 'send_money';
    $isPackageAgreement = $agreementType === 'package';
@endphp
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Edit Hostel Payment</h2>
            <div class="form-subtitle">Update the saved payment directly. Editing here can also change figures without balance blocking.</div>
        </div>
        <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.tokens.payments.update', $payment->id) }}">
            @csrf
            @method('PUT')

            <div class="pc-workflow" data-workflow data-workflow-unlock-all="1" data-workflow-open-none="1">
                <section class="pc-step" data-step="payment-context" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">1</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Payment context</span>
                                <span class="pc-step-text">Check the saved amount, batch, and hostel before changing any live details.</span>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px">
                            <span class="pc-step-meta" data-step-meta>Ready</span>
                            <span class="pc-step-arrow">⌄</span>
                        </span>
                    </button>
                    <div class="pc-step-body" data-step-body hidden>
                        <div class="pc-step-panel">
                            <div class="pc-field">
                                <label>Hostel</label>
                                <input class="pc-input" value="{{ $hostel->hostel_name }}" readonly>
                            </div>

                            <div class="pc-field">
                                <label>Batch</label>
                                <select class="pc-select" name="batch_id" @if($payment->spending_id) required @endif>
                                    <option value="">Select batch</option>
                                    @foreach($batches as $b)
                                        <option value="{{ $b->id }}" @selected((string) old('batch_id', $payment->batch_id) === (string) $b->id)>
                                            {{ $b->batch_no }} (Balance: {{ number_format((float) $b->available_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pc-field">
                                <label>Amount</label>
                                <input class="pc-input" type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $payment->amount) }}">
                            </div>

                            <div class="pc-field">
                                <label>Transaction Cost</label>
                                <input class="pc-input" type="number" step="0.01" min="0" name="transaction_cost" required value="{{ old('transaction_cost', $payment->transaction_cost ?? 0) }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-note">Existing payment entries can open either section directly from here.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pc-step" data-step="payment-fields" data-step-unlocked="1">
                    <button class="pc-step-trigger" type="button" data-step-toggle>
                        <span class="pc-step-trigger-main">
                            <span class="pc-step-index">2</span>
                            <span class="pc-step-copy">
                                <span class="pc-step-title">Payment details</span>
                                <span class="pc-step-text">Only the fields that match this agreement stay editable here.</span>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px">
                            <span class="pc-step-meta" data-step-meta>Ready</span>
                            <span class="pc-step-arrow">⌄</span>
                        </span>
                    </button>
                    <div class="pc-step-body" data-step-body hidden>
                        <div class="pc-step-panel">
                            <div class="pc-field">
                                <label>Mpesa REF</label>
                                <input class="pc-input" name="reference" required value="{{ old('reference', $payment->reference) }}">
                            </div>

                            @if($isTokenAgreement)
                                <div class="pc-field">
                                    <label>Meter Number</label>
                                    <input class="pc-input" name="meter_no" required value="{{ old('meter_no', $payment->spending?->meter_no ?? $hostel->meter_no) }}">
                                </div>
                            @else
                                <input type="hidden" name="meter_no" value="">
                            @endif

                            <div class="pc-field">
                                <label>Date</label>
                                <input class="pc-input" type="date" name="date" required value="{{ old('date', optional($payment->date)->format('Y-m-d')) }}">
                            </div>

                            <div class="pc-field">
                                <label>Coverage Value</label>
                                <input class="pc-input" type="number" min="1" step="1" name="coverage_value" required value="{{ old('coverage_value', $coverage['value'] ?? 1) }}">
                            </div>

                            <div class="pc-field">
                                <label>Coverage Unit</label>
                                <select class="pc-select" name="coverage_unit" required>
                                    <option value="day" @selected(old('coverage_unit', $coverage['unit'] ?? 'month') === 'day')>Days</option>
                                    <option value="week" @selected(old('coverage_unit', $coverage['unit'] ?? 'month') === 'week')>Weeks</option>
                                    <option value="month" @selected(old('coverage_unit', $coverage['unit'] ?? 'month') === 'month')>Months</option>
                                </select>
                            </div>

                            <div class="pc-field">
                                <label>Name</label>
                                <input class="pc-input" name="receiver_name" @if($isSendMoneyAgreement) required @endif value="{{ old('receiver_name', $payment->receiver_name) }}">
                                <div class="pc-help">
                                    @if($isSendMoneyAgreement)
                                        Required for Send Money entries.
                                    @elseif($isPackageAgreement)
                                        Optional package credit name.
                                    @else
                                        Optional.
                                    @endif
                                </div>
                            </div>

                            <div class="pc-field">
                                <label>Phone Number</label>
                                <input class="pc-input" name="receiver_phone" @if(!$isPackageAgreement) required @endif value="{{ old('receiver_phone', $payment->receiver_phone) }}">
                                <div class="pc-help">
                                    @if($isPackageAgreement)
                                        Optional package credit phone number.
                                    @else
                                        Required for this payment flow.
                                    @endif
                                </div>
                            </div>

                            <div class="pc-field full">
                                <label>Notes</label>
                                <input class="pc-input" name="notes" required value="{{ old('notes', preg_replace('/\s*\|\s*Coverage:\s*\d+\s*(day|days|week|weeks|month|months)\s*/i', '', (string) $payment->notes)) }}">
                            </div>

                            <div class="pc-step-actions">
                                <div class="pc-step-actions-main">
                                    <button class="btn" type="submit" data-loading-label="Updating Payment..." data-loading-copy="Updating the saved payment entry.">Save Payment</button>
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
