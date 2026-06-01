@extends('pettycash::layouts.app')

@section('title', 'Gateway Token Request')

@push('styles')
<style>
    .gateway-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .gateway-grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .gateway-card{border:1px solid #e4e7ec;border-radius:16px;background:#fff;padding:16px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
    .gateway-card.soft{background:linear-gradient(180deg,#fcfcfd 0%,#f8fafc 100%)}
    .gateway-title{margin:0 0 10px;font-size:15px;font-weight:900;color:#101828}
    .gateway-subtitle{margin:4px 0 0;color:#667085;font-size:13px}
    .gateway-status{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .status-tile{border:1px solid #eaecf0;border-radius:14px;padding:14px;background:#fff}
    .status-kicker{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#667085}
    .status-value{margin-top:8px;font-size:18px;font-weight:900;color:#101828}
    .status-meta{margin-top:6px;color:#667085;font-size:12px;line-height:1.45}
    .status-ok{border-color:#abefc6;background:#ecfdf3}
    .status-warn{border-color:#fedf89;background:#fffaeb}
    .status-muted{background:#f8fafc}
    .status-danger{border-color:#fecdca;background:#fef3f2}
    .facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .fact{border:1px solid #eaecf0;border-radius:12px;padding:12px;background:#fcfcfd}
    .fact .label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#667085}
    .fact .value{margin-top:6px;font-size:14px;color:#101828;font-weight:700}
    .gateway-inline-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .action-strip{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .step-list{display:grid;gap:10px}
    .step{display:flex;gap:12px;align-items:flex-start;border:1px solid #eaecf0;border-radius:12px;padding:12px;background:#fff}
    .step-num{width:30px;height:30px;border-radius:999px;background:#101828;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:12px;flex:0 0 auto}
    .step-body{min-width:0}
    .step-body strong{display:block;color:#101828}
    .step-body span{display:block;margin-top:4px;color:#667085;font-size:13px;line-height:1.45}
    .sms-panel{border:1px solid #eaecf0;border-radius:14px;padding:14px;background:#fff}
    .sms-label-row{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:8px}
    .sms-meta{display:flex;gap:8px;flex-wrap:wrap}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;font-weight:900;color:#344054}
    .pill.ok{background:#ecfdf3;color:#027a48}
    .pill.warn{background:#fffaeb;color:#b54708}
    .pill.muted{background:#f2f4f7;color:#475467}
    .pill.danger{background:#fef3f2;color:#b42318}
    .sms-preview{margin-top:10px;padding:12px;border-radius:12px;background:#f8fafc;border:1px solid #eaecf0;white-space:pre-line;color:#101828;font-size:13px;line-height:1.5}
    .btn-spinner{display:none;width:14px;height:14px;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:999px;animation:spin .8s linear infinite}
    .btn2 .btn-spinner{border-color:rgba(16,24,40,.2);border-top-color:#101828}
    .is-loading .btn-spinner{display:inline-block}
    .is-loading [data-idle-label]{display:none}
    .loading-note{display:none;color:#667085;font-size:12px;font-weight:700;margin-top:8px}
    .is-loading + .loading-note{display:block}
    .inline-loading{display:none;align-items:center;gap:8px;color:#475467;font-size:12px;font-weight:700}
    .inline-loading .dot{width:12px;height:12px;border-radius:999px;border:2px solid rgba(16,24,40,.15);border-top-color:#101828;animation:spin .8s linear infinite}
    .is-loading .inline-loading{display:inline-flex}
    .table-wrap.gateway-table table td{vertical-align:top}
    .sms-type{font-weight:900;color:#101828}
    .muted-copy{color:#667085;font-size:13px;line-height:1.5}
    .empty-state{border:1px dashed #d0d5dd;border-radius:14px;padding:16px;background:#fcfcfd;color:#667085}
    .composer-preview{padding:12px;border-radius:12px;border:1px solid #d0d5dd;background:#f8fafc;white-space:pre-line;color:#101828;line-height:1.5}
    @keyframes spin{to{transform:rotate(360deg)}}
    @media(max-width:1100px){
        .gateway-status,.gateway-grid-3{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media(max-width:980px){
        .gateway-grid,.gateway-grid-3,.gateway-status,.facts{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    $requestStatus = $statuses[$requestRecord->status] ?? ucfirst(str_replace('_', ' ', $requestRecord->status));
    $gatewayDevice = $requestRecord->gatewayDevice;
    $latestOutbox = $requestRecord->outboxItems->first();
    $paymentCommand = $requestRecord->outboxItems->firstWhere('command_type', 'payment_request');
    $customerSmsCommand = $requestRecord->outboxItems->firstWhere('command_type', 'send_customer_sms');
    $mpesaMatched = (bool) $requestRecord->matchedMpesaSms;
    $kplcMatched = (bool) $requestRecord->matchedKplcSms;
    $customerSmsSent = (bool) $requestRecord->token_sent_at;
    $readyToConfirm = $mpesaMatched && $kplcMatched;
    $hasOutgoingDraft = trim((string) $requestRecord->outgoing_sms_body) !== '';
    $canShowComposer = in_array($requestRecord->status, ['ready_for_confirmation', 'confirmed', 'token_sent'], true) || $readyToConfirm || $hasOutgoingDraft;
    $needsConfirmation = !$customerSmsSent && $canShowComposer;
    $deviceSeen = $gatewayDevice?->last_seen_at;
    $fetchStateClass = $readyToConfirm ? 'status-ok' : (($mpesaMatched || $kplcMatched) ? 'status-warn' : 'status-muted');
    $fetchStateLabel = $readyToConfirm ? 'Ready To Confirm' : (($mpesaMatched || $kplcMatched) ? 'Partially Fetched' : 'Waiting For SMS');
    $deliveryClass = $customerSmsSent ? 'status-ok' : (($customerSmsCommand && $customerSmsCommand->status === 'failed') ? 'status-danger' : 'status-muted');
    $deliveryLabel = $customerSmsSent ? 'Customer SMS Sent' : (($customerSmsCommand && $customerSmsCommand->status === 'delivered') ? 'Queued On Phone' : (($customerSmsCommand && $customerSmsCommand->status === 'failed') ? 'Send Failed' : 'Not Sent Yet'));
    $statusTone = match ($requestRecord->status) {
        'token_sent' => 'ok',
        'failed' => 'danger',
        'cancelled' => 'danger',
        'confirmed', 'ready_for_confirmation', 'mpesa_sms_received', 'token_sms_received', 'awaiting_payment', 'sent_to_phone' => 'warn',
        default => 'muted',
    };
@endphp
<div class="wrap">
    <div class="card">
        <div class="form-header">
            <div>
                <h2>Gateway Request Review</h2>
                <div class="form-subtitle">UUID {{ $requestRecord->uuid }}</div>
            </div>
            <div class="gateway-inline-actions">
                <a class="btn2" href="{{ route('petty.tokens.gateway.index') }}">Back to Queue</a>
                <form method="POST" action="{{ route('petty.tokens.gateway.refresh_check', $requestRecord) }}" style="margin:0" data-loading-form>
                    @csrf
                    <button class="btn2" type="submit">
                        <span class="btn-spinner" aria-hidden="true"></span>
                        <span data-idle-label>Refresh / Auto Check</span>
                    </button>
                    <div class="loading-note">Checking recent gateway SMS, refreshing matches, and updating fetched status...</div>
                </form>
                @if($requestRecord->status === 'draft')
                    <form method="POST" action="{{ route('petty.tokens.gateway.send', $requestRecord) }}" style="margin:0" data-loading-form>
                        @csrf
                        <button class="btn" type="submit">
                            <span class="btn-spinner" aria-hidden="true"></span>
                            <span data-idle-label>Send To Gateway Phone</span>
                        </button>
                        <div class="loading-note">Sending this request to the gateway phone now...</div>
                    </form>
                @endif
            </div>
        </div>

        <div class="gateway-status" style="margin-top:14px">
            <div class="status-tile status-{{ $statusTone }}">
                <div class="status-kicker">Request Status</div>
                <div class="status-value">{{ $requestStatus }}</div>
                <div class="status-meta">
                    {{ strtoupper($requestRecord->payment_type) }} via {{ $requestRecord->paybill_number }}<br>
                    Meter {{ $requestRecord->meter_number }}
                </div>
            </div>
            <div class="status-tile {{ $fetchStateClass }}">
                <div class="status-kicker">Fetched SMS</div>
                <div class="status-value">{{ $fetchStateLabel }}</div>
                <div class="status-meta">
                    M-PESA: {{ $mpesaMatched ? 'fetched and linked' : 'waiting' }}<br>
                    KPLC: {{ $kplcMatched ? 'fetched and linked' : 'waiting' }}
                </div>
            </div>
            <div class="status-tile {{ $deliveryClass }}">
                <div class="status-kicker">Customer Delivery</div>
                <div class="status-value">{{ $deliveryLabel }}</div>
                <div class="status-meta">
                    @if($requestRecord->token_sent_at)
                        Sent {{ $requestRecord->token_sent_at->format('Y-m-d H:i') }}
                    @elseif($customerSmsCommand?->status === 'delivered')
                        Waiting for gateway send callback
                    @elseif($customerSmsCommand?->failure_reason)
                        {{ $customerSmsCommand->failure_reason }}
                    @else
                        No completed customer SMS delivery yet
                    @endif
                </div>
            </div>
            <div class="status-tile {{ $deviceSeen ? 'status-ok' : 'status-danger' }}">
                <div class="status-kicker">Gateway Device</div>
                <div class="status-value">{{ $gatewayDevice?->name ?? 'Unassigned' }}</div>
                <div class="status-meta">
                    @if($deviceSeen)
                        Last seen {{ $deviceSeen->format('Y-m-d H:i') }}
                    @else
                        Gateway device has not checked in yet
                    @endif
                    <br>{{ $gatewayDevice?->phone_number ?: 'No gateway phone recorded' }}
                </div>
            </div>
        </div>

        <div class="gateway-grid" style="margin-top:14px">
            <div class="gateway-card soft">
                <h4 class="gateway-title">Request Snapshot</h4>
                <div class="facts">
                    <div class="fact">
                        <div class="label">Customer</div>
                        <div class="value">{{ $requestRecord->customer_name ?: ($requestRecord->hostel?->hostel_name ?? '-') }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Receiver Phone</div>
                        <div class="value">{{ $requestRecord->receiver_phone ?: '-' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Amount</div>
                        <div class="value">KES {{ number_format((float) $requestRecord->amount, 2) }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Operator Initials</div>
                        <div class="value">{{ $requestRecord->operator_initials ?: '-' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Created</div>
                        <div class="value">{{ $requestRecord->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Hostel Link</div>
                        <div class="value">{{ $requestRecord->hostel?->hostel_name ? $requestRecord->hostel->hostel_name : 'No hostel linked' }}</div>
                    </div>
                </div>
                @if($requestRecord->notes)
                    <div class="sms-preview" style="margin-top:12px">{{ $requestRecord->notes }}</div>
                @endif
            </div>

            <div class="gateway-card soft">
                <h4 class="gateway-title">Operator Flow</h4>
                <div class="step-list">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div class="step-body">
                            <strong>Send and start payment on the gateway phone.</strong>
                            <span>After the request pops up on the phone, the operator starts the payment and pays manually on M-PESA.</span>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div class="step-body">
                            <strong>Refresh / Auto Check after payment completes.</strong>
                            <span>The system scans fetched gateway SMS, labels M-PESA and KPLC candidates, and links safe matches automatically.</span>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div class="step-body">
                            <strong>Confirm, send, and let the hostel dates update.</strong>
                            <span>Once both messages match and customer SMS is delivered, the hostel payment history is posted and due dates can move forward.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="gateway-grid-3" style="margin-top:14px">
            <div class="sms-panel">
                <div class="sms-label-row">
                    <h4 class="gateway-title" style="margin:0">M-PESA Fetch</h4>
                    <span class="pill {{ $mpesaMatched ? 'ok' : 'warn' }}">{{ $mpesaMatched ? 'Fetched' : 'Waiting' }}</span>
                </div>
                <div class="muted-copy">
                    {{ $mpesaMatched ? 'Matched to this request and ready for confirmation.' : 'No linked M-PESA confirmation yet. Refresh after the payment SMS reaches the phone.' }}
                </div>
                @if($requestRecord->matchedMpesaSms)
                    <div class="sms-meta" style="margin-top:10px">
                        <span class="pill muted">Ref {{ $requestRecord->matchedMpesaSms->parsed_reference ?: '-' }}</span>
                        <span class="pill muted">KES {{ number_format((float) ($requestRecord->matchedMpesaSms->parsed_amount ?? 0), 2) }}</span>
                        <span class="pill muted">{{ $requestRecord->matchedMpesaSms->sms_received_at?->format('Y-m-d H:i') ?: 'Time pending' }}</span>
                    </div>
                    <div class="sms-preview">{{ $requestRecord->matchedMpesaSms->sms_body }}</div>
                @endif
            </div>

            <div class="sms-panel">
                <div class="sms-label-row">
                    <h4 class="gateway-title" style="margin:0">KPLC Token Fetch</h4>
                    <span class="pill {{ $kplcMatched ? 'ok' : 'warn' }}">{{ $kplcMatched ? 'Fetched' : 'Waiting' }}</span>
                </div>
                <div class="muted-copy">
                    {{ $kplcMatched ? 'Token details are linked and available for customer SMS generation.' : 'No linked token SMS yet. Refresh again after the KPLC token reaches the phone.' }}
                </div>
                @if($requestRecord->matchedKplcSms)
                    <div class="sms-meta" style="margin-top:10px">
                        <span class="pill muted">Token {{ $requestRecord->matchedKplcSms->parsed_token ?: '-' }}</span>
                        <span class="pill muted">Units {{ $requestRecord->matchedKplcSms->parsed_units !== null ? rtrim(rtrim(number_format((float) $requestRecord->matchedKplcSms->parsed_units, 2, '.', ''), '0'), '.') : '-' }}</span>
                        <span class="pill muted">{{ $requestRecord->matchedKplcSms->sms_received_at?->format('Y-m-d H:i') ?: 'Time pending' }}</span>
                    </div>
                    <div class="sms-preview">{{ $requestRecord->matchedKplcSms->sms_body }}</div>
                @endif
            </div>

            <div class="sms-panel">
                <div class="sms-label-row">
                    <h4 class="gateway-title" style="margin:0">Customer SMS Delivery</h4>
                    <span class="pill {{ $customerSmsSent ? 'ok' : ($customerSmsCommand?->status === 'failed' ? 'danger' : 'muted') }}">{{ $customerSmsSent ? 'Sent' : ($customerSmsCommand?->status === 'failed' ? 'Failed' : 'Pending') }}</span>
                </div>
                <div class="muted-copy">
                    @if($customerSmsSent)
                        The customer SMS has been confirmed by the gateway app, and the hostel payment history has been posted.
                    @elseif($customerSmsCommand?->status === 'failed')
                        The last send attempt failed. Use resend once the phone is ready.
                    @elseif($customerSmsCommand)
                        The SMS has been queued to the gateway phone and is waiting for delivery confirmation.
                    @else
                        No customer SMS has been queued yet.
                    @endif
                </div>
                <div class="sms-meta" style="margin-top:10px">
                    <span class="pill muted">Receiver {{ $requestRecord->receiver_phone ?: '-' }}</span>
                    <span class="pill muted">Initials {{ $requestRecord->operator_initials ?: '-' }}</span>
                </div>
                @if($requestRecord->outgoing_sms_body)
                    <div class="sms-preview">{{ $requestRecord->outgoing_sms_body }}</div>
                @endif
            </div>
        </div>

        <div class="gateway-card" style="margin-top:14px">
            <div class="sms-label-row">
                <div>
                    <h4 class="gateway-title" style="margin:0">Fetched Messages</h4>
                    <div class="gateway-subtitle">Recent gateway messages for this meter and phone. Safe links are kept here for review and resend decisions.</div>
                </div>
                <div class="inline-loading" data-page-loading-note>
                    <span class="dot" aria-hidden="true"></span>
                    <span>Working on the selected action...</span>
                </div>
            </div>
            <div class="table-wrap gateway-table">
                <table>
                    <thead>
                    <tr>
                        <th>Fetched Message</th>
                        <th>Received</th>
                        <th>Meter / Amount</th>
                        <th>Match Status</th>
                        <th>Preview</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($candidateLogs as $log)
                        @php
                            $linkedHere = (int) ($log->payment_request_id ?? 0) === (int) $requestRecord->id;
                            $kindLabel = match ($log->sms_kind) {
                                'mpesa' => 'M-PESA Confirmation',
                                'kplc' => 'KPLC Token',
                                'customer' => 'Customer SMS',
                                default => strtoupper((string) $log->sms_kind),
                            };
                            $matchLabel = $linkedHere ? 'Linked To This Request' : ($log->payment_request_id ? 'Linked Elsewhere' : 'Unlinked Candidate');
                            $matchTone = $linkedHere ? 'ok' : ($log->payment_request_id ? 'danger' : 'warn');
                        @endphp
                        <tr>
                            <td>
                                <div class="sms-type">{{ $kindLabel }}</div>
                                <div class="muted-copy">
                                    @if($log->parsed_reference)
                                        Ref {{ $log->parsed_reference }}
                                    @elseif($log->parsed_token)
                                        Token {{ $log->parsed_token }}
                                    @else
                                        {{ strtoupper($log->sms_box) }} SMS
                                    @endif
                                </div>
                            </td>
                            <td>{{ $log->sms_received_at?->format('Y-m-d H:i') ?: $log->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <div>{{ $log->parsed_meter_number ?: '-' }}</div>
                                <div class="muted-copy">KES {{ $log->parsed_amount !== null ? number_format((float) $log->parsed_amount, 2) : '—' }}</div>
                            </td>
                            <td>
                                <span class="pill {{ $matchTone }}">{{ $matchLabel }}</span>
                                @if($log->matched_confidence !== null)
                                    <div class="muted-copy" style="margin-top:6px">Confidence {{ number_format((float) $log->matched_confidence, 0) }}%</div>
                                @endif
                            </td>
                            <td style="max-width:460px">
                                <div class="muted-copy" style="white-space:pre-line">{{ $log->sms_body }}</div>
                            </td>
                            <td>
                                @if((int) ($log->payment_request_id ?? 0) !== (int) $requestRecord->id && in_array($log->sms_kind, ['mpesa', 'kplc'], true))
                                    <form method="POST" action="{{ route('petty.tokens.gateway.manual_link', $requestRecord) }}" style="margin:0" data-loading-form>
                                        @csrf
                                        <input type="hidden" name="sms_log_id" value="{{ $log->id }}">
                                        <button class="btn2" type="submit">
                                            <span class="btn-spinner" aria-hidden="true"></span>
                                            <span data-idle-label>Link</span>
                                        </button>
                                        <div class="loading-note">Linking this fetched message to the request...</div>
                                    </form>
                                @elseif($linkedHere)
                                    <span class="pill ok">Linked</span>
                                @else
                                    <span class="muted-copy">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">No fetched SMS candidates are available yet. Complete the gateway payment on the phone, wait for the M-PESA and KPLC SMS to arrive, then use <strong>Refresh / Auto Check</strong>.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="gateway-grid" style="margin-top:14px">
            <div class="gateway-card">
                <h4 class="gateway-title">Confirm Match And Build Customer SMS</h4>
                <div class="gateway-subtitle">This stays focused on confirmation only. Once delivery succeeds, resend stays in the action area and the raw picker fields disappear.</div>
                @if($customerSmsSent)
                    <div class="sms-panel" style="margin-top:12px">
                        <div class="sms-label-row">
                            <div>
                                <h5 class="gateway-title" style="margin:0">Confirmation Locked</h5>
                                <div class="gateway-subtitle">This request has already completed customer delivery and posted to hostel payment history.</div>
                            </div>
                            <span class="pill ok">Completed</span>
                        </div>
                        <div class="sms-preview">{{ $requestRecord->outgoing_sms_body }}</div>
                    </div>
                @elseif(!$readyToConfirm)
                    <div class="empty-state" style="margin-top:12px">Both a linked M-PESA confirmation and a linked KPLC token SMS are required before confirmation can proceed.</div>
                @else
                    <form method="POST" action="{{ route('petty.tokens.gateway.confirm', $requestRecord) }}" class="pc-form" data-loading-form style="margin-top:12px">
                        @csrf
                        <div class="pc-field">
                            <label>Operator Initials</label>
                            <input class="pc-input" name="operator_initials" value="{{ old('operator_initials', $requestRecord->operator_initials) }}">
                        </div>
                        <div class="pc-field full">
                            <label>Generated Customer SMS</label>
                            <textarea class="pc-textarea" name="outgoing_sms_body" rows="10">{{ old('outgoing_sms_body', $requestRecord->outgoing_sms_body ?: $generatedSms) }}</textarea>
                            <div class="pc-help">The generated message stays editable before it is queued to the gateway phone.</div>
                        </div>
                        <div class="pc-actions">
                            <button class="btn" type="submit">
                                <span class="btn-spinner" aria-hidden="true"></span>
                                <span data-idle-label>Confirm Match And Save SMS</span>
                            </button>
                        </div>
                        <div class="loading-note">Saving the linked match and updating the outgoing SMS draft...</div>
                    </form>
                @endif
            </div>

            <div class="gateway-card">
                <h4 class="gateway-title">Customer Send Actions</h4>
                <div class="gateway-subtitle">Queue, resend, retry, and cancel actions stay here. The lower picker clutter is removed once the SMS is already sent.</div>

                @if($canShowComposer && !$customerSmsSent)
                    <form method="POST" action="{{ route('petty.tokens.gateway.queue_customer_sms', $requestRecord) }}" class="pc-form" data-loading-form style="margin-top:12px">
                        @csrf
                        <div class="pc-field">
                            <label>Initials</label>
                            <input class="pc-input" name="operator_initials" value="{{ old('operator_initials_send', $requestRecord->operator_initials) }}">
                        </div>
                        <div class="pc-field full">
                            <label>Outgoing Customer SMS</label>
                            <textarea class="pc-textarea" name="outgoing_sms_body" rows="8" required>{{ old('outgoing_sms_body_send', $requestRecord->outgoing_sms_body ?: $generatedSms) }}</textarea>
                        </div>
                        <div class="pc-field full">
                            <label>Preview</label>
                            <div class="composer-preview">{{ old('outgoing_sms_body_send', $requestRecord->outgoing_sms_body ?: $generatedSms) }}</div>
                            <div class="pc-help">Customer send now prefers the current active gateway configuration, so older failed requests can be resent through the newly selected device and SMSGate settings.</div>
                        </div>
                        <div class="pc-actions">
                            <button class="btn" type="submit">
                                <span class="btn-spinner" aria-hidden="true"></span>
                                <span data-idle-label>{{ $customerSmsCommand ? 'Queue Customer SMS Again' : 'Queue Customer SMS To Gateway' }}</span>
                            </button>
                        </div>
                        <div class="loading-note">Queuing the customer SMS for SIM 2 sending on the gateway phone...</div>
                    </form>
                @elseif($customerSmsSent)
                    <div class="sms-panel" style="margin-top:12px">
                        <div class="sms-label-row">
                            <div>
                                <h5 class="gateway-title" style="margin:0">Sent Customer SMS</h5>
                                <div class="gateway-subtitle">{{ $requestRecord->token_sent_at?->format('Y-m-d H:i') ?: 'Send time pending' }}</div>
                            </div>
                            <span class="pill ok">Delivered</span>
                        </div>
                        <div class="sms-preview">{{ $requestRecord->outgoing_sms_body }}</div>
                        <div class="action-strip" style="margin-top:12px">
                            <form method="POST" action="{{ route('petty.tokens.gateway.queue_customer_sms', $requestRecord) }}" style="margin:0" data-loading-form>
                                @csrf
                                <input type="hidden" name="operator_initials" value="{{ $requestRecord->operator_initials }}">
                                <input type="hidden" name="outgoing_sms_body" value="{{ $requestRecord->outgoing_sms_body }}">
                                <button class="btn2" type="submit">
                                    <span class="btn-spinner" aria-hidden="true"></span>
                                    <span data-idle-label>Resend Same SMS</span>
                                </button>
                                <div class="loading-note">Queuing the same customer SMS again to the gateway phone...</div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="empty-state" style="margin-top:12px">Confirm the match first to unlock a clean customer SMS send action.</div>
                @endif

                <div class="action-strip" style="margin-top:14px">
                    @if($requestRecord->status === 'failed')
                        <form method="POST" action="{{ route('petty.tokens.gateway.retry', $requestRecord) }}" style="margin:0" data-loading-form>
                            @csrf
                            <button class="btn2" type="submit">
                                <span class="btn-spinner" aria-hidden="true"></span>
                                <span data-idle-label>Retry Request</span>
                            </button>
                            <div class="loading-note">Retrying the gateway request...</div>
                        </form>
                    @endif

                    @if(!in_array($requestRecord->status, ['cancelled', 'token_sent'], true))
                        <form method="POST" action="{{ route('petty.tokens.gateway.cancel', $requestRecord) }}" style="margin:0" data-loading-form>
                            @csrf
                            <input type="hidden" name="reason" value="Cancelled by operator">
                            <button class="btn2" type="submit">
                                <span class="btn-spinner" aria-hidden="true"></span>
                                <span data-idle-label>Cancel Request</span>
                            </button>
                            <div class="loading-note">Cancelling this gateway request...</div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="gateway-grid-3" style="margin-top:14px">
            <div class="gateway-card">
                <h4 class="gateway-title">Request Timeline</h4>
                <div class="facts" style="grid-template-columns:1fr">
                    <div class="fact"><div class="label">Created</div><div class="value">{{ $requestRecord->created_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                    <div class="fact"><div class="label">Sent To Phone</div><div class="value">{{ $requestRecord->sent_to_phone_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                    <div class="fact"><div class="label">Payment Started</div><div class="value">{{ $requestRecord->payment_started_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                    <div class="fact"><div class="label">Match Confirmed</div><div class="value">{{ $requestRecord->confirmed_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                    <div class="fact"><div class="label">Customer SMS Sent</div><div class="value">{{ $requestRecord->token_sent_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                </div>
            </div>
            <div class="gateway-card">
                <h4 class="gateway-title">Gateway Command State</h4>
                <div class="facts" style="grid-template-columns:1fr">
                    <div class="fact">
                        <div class="label">Payment Command</div>
                        <div class="value">{{ $paymentCommand?->status ? ucfirst($paymentCommand->status) : 'Not queued' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Customer SMS Command</div>
                        <div class="value">{{ $customerSmsCommand?->status ? ucfirst($customerSmsCommand->status) : 'Not queued' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Latest Command Update</div>
                        <div class="value">{{ $latestOutbox?->updated_at?->format('Y-m-d H:i') ?: '-' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Failure Note</div>
                        <div class="value">{{ $requestRecord->failure_reason ?: ($latestOutbox?->failure_reason ?: '-') }}</div>
                    </div>
                </div>
            </div>
            <div class="gateway-card">
                <h4 class="gateway-title">Posting Outcome</h4>
                <div class="muted-copy">
                    Once the gateway reports the customer SMS as sent, this request now posts a real hostel token payment entry automatically. That is what allows hostel due dates and payment history to move forward instead of remaining pending.
                </div>
                <div class="facts" style="grid-template-columns:1fr;margin-top:12px">
                    <div class="fact">
                        <div class="label">Posted To Hostel History</div>
                        <div class="value">{{ $customerSmsSent && $requestRecord->hostel_id ? 'Yes' : 'Pending confirmation' }}</div>
                    </div>
                    <div class="fact">
                        <div class="label">Reference</div>
                        <div class="value">GW-{{ $requestRecord->uuid }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var pageLoadingNote = document.querySelector('[data-page-loading-note]');
        document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.dataset.submitted === '1') return;
                form.dataset.submitted = '1';
                form.classList.add('is-loading');
                if (pageLoadingNote) {
                    pageLoadingNote.style.display = 'inline-flex';
                }
                form.querySelectorAll('button').forEach(function (button) {
                    button.disabled = true;
                });
            });
        });
    }());
</script>
@endpush
