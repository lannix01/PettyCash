@extends('pettycash::layouts.app')

@section('title', 'New Gateway Token Request')

@push('styles')
<style>
    .grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    @media(max-width:980px){.grid2{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="form-wrap">
        <div class="form-card">
            <div class="form-header">
                <div>
                    <h2>Create Gateway Token Request</h2>
                    <div class="form-subtitle">Create a controlled token payment job for the dedicated gateway phone.</div>
                </div>
                <div class="pc-actions" style="padding-top:0">
                    <a class="btn2" href="{{ route('petty.tokens.gateway.index') }}">Back to Queue</a>
                </div>
            </div>

            <form class="pc-form" method="POST" action="{{ route('petty.tokens.gateway.store') }}" style="margin-top:14px">
                @csrf
                <div class="pc-field">
                    <label>Hostel</label>
                    <select class="pc-select" name="hostel_id" id="hostelSelect">
                        <option value="">Manual / not linked</option>
                        @foreach($hostels as $hostelOption)
                            <option
                                value="{{ $hostelOption->id }}"
                                data-meter="{{ $hostelOption->meter_no }}"
                                data-phone="{{ $hostelOption->phone_no }}"
                                data-name="{{ $hostelOption->contact_person ?: $hostelOption->hostel_name }}"
                                data-amount="{{ number_format((float) ($hostelOption->amount_due ?? 0), 2, '.', '') }}"
                                @selected((int) old('hostel_id', $hostel?->id) === (int) $hostelOption->id)
                            >
                                {{ $hostelOption->hostel_name }} @if($hostelOption->meter_no) ({{ $hostelOption->meter_no }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pc-field">
                    <label>Payment Type</label>
                    <select class="pc-select" name="payment_type" id="paymentTypeSelect">
                        <option value="prepaid" @selected(old('payment_type') === 'prepaid')>Prepaid</option>
                        <option value="postpaid" @selected(old('payment_type') === 'postpaid')>Postpaid</option>
                    </select>
                    <div class="pc-help">Prepaid uses paybill 888880, postpaid uses 888888.</div>
                </div>
                <div class="pc-field">
                    <label>Gateway Device</label>
                    <select class="pc-select" name="gateway_device_id" required>
                        <option value="">Select gateway device</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" @selected((int) old('gateway_device_id') === (int) $device->id)>
                                {{ $device->name }} @if($device->phone_number) ({{ $device->phone_number }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="pc-help">Dedicated gateway phone default: {{ $defaultGatewayPhone }}</div>
                </div>
                <div class="pc-field">
                    <label>Meter Number</label>
                    <input class="pc-input" name="meter_number" id="meterInput" value="{{ old('meter_number', $hostel?->meter_no) }}" required>
                </div>
                <div class="pc-field">
                    <label>Amount</label>
                    <input class="pc-input" type="number" step="0.01" min="1" name="amount" id="amountInput" value="{{ old('amount', number_format((float) ($hostel?->amount_due ?? 0), 2, '.', '')) }}" required>
                </div>
                <div class="pc-field">
                    <label>Receiver Phone</label>
                    <input class="pc-input" name="receiver_phone" id="receiverPhoneInput" value="{{ old('receiver_phone', $hostel?->phone_no) }}">
                </div>
                <div class="pc-field">
                    <label>Customer Name</label>
                    <input class="pc-input" name="customer_name" id="customerNameInput" value="{{ old('customer_name', $hostel?->contact_person ?: $hostel?->hostel_name) }}">
                </div>
                <div class="pc-field full">
                    <label>Notes</label>
                    <textarea class="pc-textarea" name="notes" placeholder="Optional operator notes">{{ old('notes') }}</textarea>
                </div>
                <div class="pc-field full">
                    <label class="pc-check">
                        <input type="hidden" name="send_now" value="0">
                        <input type="checkbox" name="send_now" value="1" @checked((string) old('send_now', '1') === '1')>
                        Queue this request to the gateway phone immediately after saving
                    </label>
                </div>
                <div class="pc-actions">
                    <button class="btn" type="submit">Save Request</button>
                </div>
            </form>
        </div>

        <div class="form-card" style="margin-top:14px">
            <div class="form-header">
                <div>
                    <h2>Register Gateway Device</h2>
                    <div class="form-subtitle">Use this only if the dedicated gateway phone has not yet been added.</div>
                </div>
            </div>

            <form class="pc-form" method="POST" action="{{ route('petty.tokens.gateway.devices.store') }}" style="margin-top:14px">
                @csrf
                <div class="pc-field">
                    <label>Device Name</label>
                    <input class="pc-input" name="name" placeholder="Infinix X566 Gateway">
                </div>
                <div class="pc-field">
                    <label>Device UUID</label>
                    <input class="pc-input" name="device_uuid" placeholder="gateway-infinix-x566">
                </div>
                <div class="pc-field">
                    <label>Phone Number</label>
                    <input class="pc-input" name="phone_number" value="{{ $defaultGatewayPhone }}">
                </div>
                <div class="pc-field">
                    <label>API Token</label>
                    <input class="pc-input" name="api_token" placeholder="internal secure token">
                </div>
                <div class="pc-field full">
                    <label class="pc-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active device
                    </label>
                </div>
                <div class="pc-actions">
                    <button class="btn2" type="submit">Save Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const hostelSelect = document.getElementById('hostelSelect');
        const meterInput = document.getElementById('meterInput');
        const receiverPhoneInput = document.getElementById('receiverPhoneInput');
        const customerNameInput = document.getElementById('customerNameInput');
        const amountInput = document.getElementById('amountInput');

        if (!hostelSelect) return;

        hostelSelect.addEventListener('change', function () {
            const option = hostelSelect.options[hostelSelect.selectedIndex];
            if (!option || !option.value) return;

            if (meterInput && !meterInput.value) meterInput.value = option.dataset.meter || '';
            if (receiverPhoneInput && !receiverPhoneInput.value) receiverPhoneInput.value = option.dataset.phone || '';
            if (customerNameInput && !customerNameInput.value) customerNameInput.value = option.dataset.name || '';
            if (amountInput && (!amountInput.value || Number(amountInput.value) <= 0)) amountInput.value = option.dataset.amount || '';
        });
    }());
</script>
@endpush
@endsection
