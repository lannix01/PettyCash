@extends('pettycash::layouts.app')

@section('title', 'Gateway Token Queue')

@push('styles')
<style>
    .gateway-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap;padding:18px;border-radius:22px;background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 55%,#60a5fa 100%);color:#fff}
    .gateway-hero h2{color:#fff;margin:0}
    .gateway-hero .form-subtitle{color:rgba(255,255,255,.82)}
    .gateway-hero-copy{max-width:720px}
    .gateway-hero-notes{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .gateway-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);font-size:12px;font-weight:800}
    .gateway-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
    .gateway-stat{border:1px solid #dbe3ff;border-radius:18px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
    .gateway-stat-label{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#667085}
    .gateway-stat-value{font-size:28px;font-weight:900;line-height:1.1;margin-top:8px}
    .gateway-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:14px;margin-top:14px}
    .gateway-stack{display:grid;gap:14px}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;font-weight:900}
    .pill-ok{background:#ecfdf3;color:#027a48}
    .pill-off{background:#fef3f2;color:#b42318}
    .gateway-toolbar{display:flex;gap:10px;flex-wrap:wrap}
    .gateway-section{border:1px solid #e4e7ec;border-radius:20px;background:#fff;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.04)}
    .gateway-section-head{padding:16px 16px 0}
    .gateway-section-body{padding:16px}
    .gateway-active-summary{border:1px solid #dbeafe;border-radius:18px;background:linear-gradient(180deg,#eff6ff 0%,#ffffff 100%);padding:16px}
    .gateway-active-summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:12px}
    .gateway-key{padding:12px;border-radius:14px;background:#fff;border:1px solid #e5e7eb}
    .gateway-key-label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#64748b}
    .gateway-key-value{margin-top:6px;font-size:14px;font-weight:800;color:#0f172a;word-break:break-word}
    .gateway-actions-note{margin-top:10px;color:#475467;line-height:1.6}
    .gateway-filter-toggle{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 16px;border:1px solid #eaecf0;border-radius:14px;background:#f8fafc;cursor:pointer}
    .gateway-filter-box[open] .gateway-filter-toggle{border-bottom-left-radius:0;border-bottom-right-radius:0}
    .gateway-filter-body{padding:16px;border:1px solid #eaecf0;border-top:0;border-bottom-left-radius:14px;border-bottom-right-radius:14px;background:#fff}
    .gateway-filter-box summary{list-style:none}
    .gateway-filter-box summary::-webkit-details-marker{display:none}
    .gateway-table-actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn-danger-soft{border:1px solid #fecaca;background:#fff1f2;color:#b42318;padding:10px 14px;border-radius:12px;font-weight:800}
    .btn-danger-soft:hover{background:#ffe4e6}
    .gateway-modal{position:fixed;inset:0;background:rgba(15,23,42,.58);display:none;align-items:center;justify-content:center;padding:24px;z-index:60}
    .gateway-modal.is-open{display:flex}
    .gateway-modal-card{width:min(1180px,100%);max-height:min(90vh,960px);overflow:auto;border-radius:24px;background:#fff;box-shadow:0 24px 80px rgba(15,23,42,.22)}
    .gateway-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px 22px;border-bottom:1px solid #eaecf0;background:linear-gradient(180deg,#f8fbff 0%,#fff 100%)}
    .gateway-modal-head h3{margin:0;font-size:22px}
    .gateway-modal-sub{margin-top:6px;color:#667085;font-size:13px;line-height:1.55}
    .gateway-modal-close{border:0;background:#eef2ff;color:#1d4ed8;width:38px;height:38px;border-radius:999px;font-size:20px;font-weight:900;cursor:pointer}
    .gateway-modal-body{padding:22px}
    .gateway-step-band{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}
    .gateway-step-card{padding:14px;border-radius:16px;border:1px solid #dbeafe;background:#f8fbff}
    .gateway-step-card strong{display:block;color:#0f172a}
    .gateway-step-card span{display:block;margin-top:6px;font-size:13px;color:#667085;line-height:1.5}
    .gateway-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .gateway-form-grid .pc-field,.gateway-form-stack .pc-field{margin:0;padding:14px;border:1px solid #e5e7eb;border-radius:16px;background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%)}
    .gateway-form-grid .pc-field label,.gateway-form-stack .pc-field label{display:block;margin-bottom:8px;font-size:12px;font-weight:900;letter-spacing:.02em;color:#344054}
    .gateway-form-grid .pc-field.full,.gateway-form-stack .pc-field.full{grid-column:1/-1}
    .gateway-form-stack{display:grid;gap:14px}
    .gateway-form-section{padding:18px;border:1px solid #dbeafe;border-radius:20px;background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%)}
    .gateway-form-section + .gateway-form-section{margin-top:2px}
    .gateway-form-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}
    .gateway-form-section-title{margin:0;font-size:16px;font-weight:900;color:#0f172a}
    .gateway-form-section-text{margin-top:4px;color:#64748b;font-size:13px;line-height:1.55}
    .gateway-span-2{grid-column:span 2}
    .gateway-span-3{grid-column:1/-1}
    .gateway-health-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
    .gateway-banner{padding:12px 14px;border-radius:14px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;font-size:13px;line-height:1.6}
    .pc-step{border-top:1px solid #eaecf0}
    .pc-step:first-of-type{border-top:0}
    .pc-step-trigger{width:100%;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:16px;border:0;background:#fff;text-align:left;cursor:pointer}
    .pc-step-trigger-main{display:flex;gap:12px;align-items:flex-start}
    .pc-step-index{width:28px;height:28px;border-radius:999px;background:#111827;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;flex:0 0 auto}
    .pc-step-title{display:block;font-size:15px;font-weight:900}
    .pc-step-text{display:block;color:#667085;font-size:13px;margin-top:2px}
    .pc-step-meta{font-size:12px;font-weight:800;color:#667085}
    .pc-step-body[hidden]{display:none}
    .pc-step-panel{padding:0 16px 16px}
    .grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .pager{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    @media(max-width:1100px){.gateway-grid,.gateway-stats,.grid2,.gateway-step-band,.gateway-active-summary-grid,.gateway-form-grid{grid-template-columns:1fr}.gateway-span-2,.gateway-span-3{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="card">
        <div class="gateway-hero">
            <div class="gateway-hero-copy">
                <h2>Gateway Token Queue</h2>
                <div class="form-subtitle">Control the automated token workflow from one place: active gateway selection, background-helper pairing, SMSGate delivery mode, and the live request queue.</div>
                <div class="gateway-hero-notes">
                    <span class="gateway-chip">Active gateway decides where new jobs go</span>
                    <span class="gateway-chip">Helper app keeps fetching jobs and inbound SMS</span>
                    <span class="gateway-chip">SMSGate cloud can handle customer send separately</span>
                </div>
            </div>
            <div class="gateway-toolbar">
                <button class="btn" type="button" data-modal-open="gatewayRequestModal">New Gateway Request</button>
                <button class="btn2" type="button" data-modal-open="gatewayDeviceCreateModal">Add Gateway Device</button>
            </div>
        </div>

        <div class="gateway-stats">
            <div class="gateway-stat">
                <div class="gateway-stat-label">Open Queue</div>
                <div class="gateway-stat-value">{{ number_format((int) ($counts['open'] ?? 0)) }}</div>
            </div>
            <div class="gateway-stat">
                <div class="gateway-stat-label">Awaiting Payment</div>
                <div class="gateway-stat-value">{{ number_format((int) ($counts['awaiting'] ?? 0)) }}</div>
            </div>
            <div class="gateway-stat">
                <div class="gateway-stat-label">Ready / Confirmed</div>
                <div class="gateway-stat-value">{{ number_format((int) ($counts['ready'] ?? 0)) }}</div>
            </div>
            <div class="gateway-stat">
                <div class="gateway-stat-label">Devices</div>
                <div class="gateway-stat-value">{{ number_format((int) ($counts['devices'] ?? 0)) }}</div>
            </div>
        </div>

        <div class="gateway-grid">
            <div class="gateway-stack">
                <section class="gateway-section">
                    <div class="gateway-section-head">
                        <div class="form-header">
                            <div>
                                <h2>Payment Queue</h2>
                                <div class="form-subtitle">New jobs, started jobs, matched SMS, and final sends all stay visible here.</div>
                            </div>
                        </div>
                    </div>
                    <div class="gateway-section-body">
                        <details class="gateway-filter-box">
                            <summary class="gateway-filter-toggle">
                                <div>
                                    <strong>Queue Search And Advanced Filters</strong>
                                    <div class="muted">Keep these hidden until you need to narrow down the queue.</div>
                                </div>
                                <span class="pill">Optional</span>
                            </summary>
                            <div class="gateway-filter-body">
                                <form method="GET" class="pc-form">
                                    <div class="pc-field">
                                        <label>Search</label>
                                        <input class="pc-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Meter, phone, customer, UUID">
                                    </div>
                                    <div class="pc-field">
                                        <label>Status</label>
                                        <select class="pc-select" name="status">
                                            <option value="">All statuses</option>
                                            @foreach($statuses as $key => $label)
                                                <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="pc-field">
                                        <label>Payment Type</label>
                                        <select class="pc-select" name="payment_type">
                                            <option value="">All types</option>
                                            <option value="prepaid" @selected(($filters['paymentType'] ?? '') === 'prepaid')>Prepaid</option>
                                            <option value="postpaid" @selected(($filters['paymentType'] ?? '') === 'postpaid')>Postpaid</option>
                                        </select>
                                    </div>
                                    <div class="pc-field">
                                        <label>From Date</label>
                                        <input class="pc-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                                    </div>
                                    <div class="pc-field">
                                        <label>To Date</label>
                                        <input class="pc-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                                    </div>
                                    <div class="pc-actions">
                                        <button class="btn2" type="submit">Apply</button>
                                        <a class="btn2" href="{{ route('petty.tokens.gateway.index') }}">Reset</a>
                                    </div>
                                </form>
                            </div>
                        </details>

                        <div class="table-wrap" style="margin-top:14px">
                            <table>
                                <thead>
                                <tr>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Meter</th>
                                    <th>Amount</th>
                                    <th>Receiver</th>
                                    <th>Gateway</th>
                                    <th>Match</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->created_at?->format('Y-m-d H:i') }}</strong>
                                            <div class="muted">{{ $item->uuid }}</div>
                                        </td>
                                        <td><span class="pill">{{ $statuses[$item->status] ?? $item->status }}</span></td>
                                        <td>
                                            <strong>{{ strtoupper($item->payment_type) }}</strong>
                                            <div class="muted">Paybill {{ $item->paybill_number }}</div>
                                        </td>
                                        <td>
                                            <strong>{{ $item->meter_number }}</strong>
                                            <div class="muted">{{ $item->hostel?->hostel_name ?? $item->customer_name ?? '-' }}</div>
                                        </td>
                                        <td>KES {{ number_format((float) $item->amount, 2) }}</td>
                                        <td>{{ $item->receiver_phone ?: '-' }}</td>
                                        <td>{{ $item->gatewayDevice?->name ?? 'Unassigned' }}</td>
                                        <td>
                                            <div class="muted">M-PESA: {{ $item->matchedMpesaSms?->parsed_reference ?? '—' }}</div>
                                            <div class="muted">KPLC: {{ $item->matchedKplcSms?->parsed_token ?? '—' }}</div>
                                        </td>
                                        <td><a class="btn2" href="{{ route('petty.tokens.gateway.show', $item) }}">Open</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="muted">No gateway payment requests yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="pager" style="margin-top:14px">
                            <div class="muted">Page {{ $items->currentPage() }} of {{ max(1, $items->lastPage()) }}</div>
                            <div>{{ $items->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="gateway-stack">
                <section class="gateway-section">
                    <div class="gateway-section-head">
                        <div class="form-header">
                            <div>
                                <h2>Gateway Devices</h2>
                                <div class="form-subtitle">Single dedicated phone for now. Keep one active and monitored.</div>
                            </div>
                        </div>
                    </div>
                    <div class="gateway-section-body">
                        @if($activeDevice)
                            <div class="gateway-active-summary" style="margin-bottom:14px">
                                <div class="form-header" style="margin:0">
                                    <div>
                                        <h3 style="margin:0">Active Gateway: {{ $activeDevice->name }}</h3>
                                        <div class="form-subtitle">This is where new queue jobs go automatically. Change it here when you switch helper phones.</div>
                                    </div>
                                </div>
                                <div class="gateway-active-summary-grid">
                                    <div class="gateway-key">
                                        <div class="gateway-key-label">Phone</div>
                                        <div class="gateway-key-value">{{ $activeDevice->phone_number ?: 'Not set' }}</div>
                                    </div>
                                    <div class="gateway-key">
                                        <div class="gateway-key-label">SMSGate Mode</div>
                                        <div class="gateway-key-value">{{ strtoupper($activeDevice->smsgate_mode ?: 'helper') }}</div>
                                    </div>
                                    <div class="gateway-key">
                                        <div class="gateway-key-label">Last Seen</div>
                                        <div class="gateway-key-value">{{ $activeDevice->last_seen_at?->format('Y-m-d H:i') ?: 'Never' }}</div>
                                    </div>
                                </div>
                                <div class="gateway-health-row">
                                    <span class="pill {{ $activeDevice->smsgate_enabled ? 'pill-ok' : 'pill-off' }}">{{ $activeDevice->smsgate_enabled ? 'SMSGate Enabled' : 'Helper Only' }}</span>
                                    <span class="pill">{{ $activeDevice->smsgate_last_tested_at ? 'Last test ' . $activeDevice->smsgate_last_tested_at->format('Y-m-d H:i') : 'No SMSGate test yet' }}</span>
                                    @if($activeDevice->smsgate_last_error)
                                        <span class="pill pill-off">Last error saved</span>
                                    @endif
                                </div>
                                <div class="gateway-actions-note">
                                    If your SMSGate setup only gives an API URL and not webhook management, that is still usable for cloud sending. The helper app can continue handling inbound token/M-PESA reads while SMSGate cloud handles customer-send delivery.
                                </div>
                                @if($activeDevice->smsgate_last_error)
                                    <div class="gateway-banner" style="margin-top:12px">
                                        <strong>Latest SMSGate issue:</strong> {{ $activeDevice->smsgate_last_error }}
                                    </div>
                                @endif
                            </div>
                            <div class="gateway-card soft" style="margin-bottom:14px">
                                <h4 class="gateway-title">Optional SMSGate Webhook Setup</h4>
                                <div class="gateway-subtitle">Use these only if your SMSGate account or app allows webhook registration. They are not required for cloud API sending itself.</div>
                                <div class="facts" style="margin-top:12px">
                                    <div class="fact">
                                        <div class="label">Incoming SMS Webhook</div>
                                        <div class="value" style="font-size:12px;word-break:break-all">{{ $webhookExamples['incoming']($activeDevice) }}</div>
                                    </div>
                                    <div class="fact">
                                        <div class="label">Status Webhook</div>
                                        <div class="value" style="font-size:12px;word-break:break-all">{{ $webhookExamples['status']($activeDevice) }}</div>
                                    </div>
                                </div>
                                <div class="muted" style="margin-top:10px;line-height:1.6">
                                    Use <code>sms:received</code> for the Incoming SMS Webhook.<br>
                                    Use <code>sms:sent</code>, <code>sms:delivered</code>, and <code>sms:failed</code> for the Status Webhook.<br>
                                    The device UUID and token in these URLs are already scoped to <strong>{{ $activeDevice->name }}</strong>.
                                </div>
                            </div>
                        @endif
                        <div class="table-wrap">
                            <table>
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>UUID</th>
                                    <th>SMSGate</th>
                                    <th>Status</th>
                                    <th>Last Seen</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($devices as $device)
                                    <tr>
                                        <td>
                                            <strong>{{ $device->name }}</strong>
                                            @if($device->app_version)
                                                <div class="muted">{{ $device->app_version }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $device->phone_number ?: '-' }}</td>
                                        <td><span class="muted">{{ $device->device_uuid }}</span></td>
                                        <td>
                                            @if($device->smsgate_enabled)
                                                <div class="pill pill-ok">{{ strtoupper($device->smsgate_mode ?: 'both') }}</div>
                                                <div class="muted" style="margin-top:6px">{{ $device->smsgate_device_id ?: 'No device id' }}</div>
                                                <div class="muted">{{ $device->smsgate_last_error ?: 'Ready' }}</div>
                                            @else
                                                <div class="pill">Helper App Only</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pill {{ $device->is_active ? 'pill-ok' : 'pill-off' }}">
                                                {{ $device->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $device->last_seen_at?->format('Y-m-d H:i') ?: 'Never' }}</td>
                                        <td>
                                            <div class="gateway-table-actions">
                                                @if(!$device->is_active)
                                                    <form method="POST" action="{{ route('petty.tokens.gateway.devices.activate', $device) }}" style="margin:0">
                                                        @csrf
                                                        <button class="btn2" type="submit">Use This Gateway</button>
                                                    </form>
                                                @else
                                                    <span class="pill pill-ok">Current</span>
                                                @endif
                                                <button class="btn2" type="button" data-modal-open="gatewayDeviceEditModal-{{ $device->id }}">Edit</button>
                                                <form method="POST" action="{{ route('petty.tokens.gateway.devices.destroy', $device) }}" style="margin:0" onsubmit="return confirm('Delete gateway device {{ addslashes($device->name) }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn-danger-soft" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="muted">No gateway devices yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="gateway-section">
                    <div class="gateway-section-head">
                        <div class="form-header">
                            <div>
                                <h2>Automation Setup</h2>
                                <div class="form-subtitle">Use the focused modal forms for onboarding and new requests. The queue stays visible as the primary control surface.</div>
                            </div>
                        </div>
                    </div>
                    <div class="gateway-section-body">
                        <div class="gateway-step-band">
                            <div class="gateway-step-card">
                                <strong>1. Configure one helper phone properly</strong>
                                <span>Add the device, set it active, then pair the helper app once. After that the helper should keep receiving jobs and inbound SMS automatically.</span>
                            </div>
                            <div class="gateway-step-card">
                                <strong>2. Choose your customer-send route</strong>
                                <span>Use SMSGate cloud for direct send if available. If not, keep the helper-app fallback path ready.</span>
                            </div>
                            <div class="gateway-step-card">
                                <strong>3. Work from the queue, not the helper app</strong>
                                <span>Initiate from hostel or queue, then let the active gateway and background helper do the repetitive syncing work.</span>
                            </div>
                        </div>
                        <div class="gateway-toolbar">
                            <button class="btn" type="button" data-modal-open="gatewayRequestModal">Create New Request</button>
                            <button class="btn2" type="button" data-modal-open="gatewayDeviceCreateModal">Register / Configure Helper</button>
                            <button class="btn2" type="button" data-modal-open="gatewayTestSmsModal">Test SMSGate Delivery</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="gateway-modal @if($showRequestForm) is-open @endif" id="gatewayRequestModal" aria-hidden="{{ $showRequestForm ? 'false' : 'true' }}">
    <div class="gateway-modal-card">
        <div class="gateway-modal-head">
            <div>
                <h3>New Gateway Payment Request</h3>
                <div class="gateway-modal-sub">Build the request once, queue it immediately, and let the active helper app fetch it in the background.</div>
            </div>
            <button class="gateway-modal-close" type="button" data-modal-close>&times;</button>
        </div>
        <div class="gateway-modal-body">
            <div class="gateway-step-band">
                <div class="gateway-step-card"><strong>Pick source</strong><span>Select a hostel to prefill meter, phone, and amount, or fill manually.</span></div>
                <div class="gateway-step-card"><strong>Choose active gateway</strong><span>The active helper device receives the job automatically after save.</span></div>
                <div class="gateway-step-card"><strong>Queue instantly</strong><span>Keep “send now” on so the request goes to the helper without extra steps.</span></div>
            </div>
            <form class="pc-form gateway-form-stack" method="POST" action="{{ route('petty.tokens.gateway.store') }}">
                @csrf
                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">Source And Routing</h4>
                            <div class="gateway-form-section-text">Start from a hostel if available, then confirm which active helper should receive this job immediately.</div>
                        </div>
                        <span class="pill">Step 1</span>
                    </div>
                    <div class="gateway-form-grid">
                        <div class="pc-field gateway-span-2">
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
                            <label>Gateway Device</label>
                            <select class="pc-select" name="gateway_device_id" required>
                                <option value="">Select gateway device</option>
                                @foreach($devices->where('is_active', true) as $device)
                                    <option value="{{ $device->id }}" @selected((int) old('gateway_device_id', $activeDevice?->id) === (int) $device->id)>
                                        {{ $device->name }} @if($device->phone_number) ({{ $device->phone_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">Payment Details</h4>
                            <div class="gateway-form-section-text">These are the values the helper app uses to build the operator payment job and keep it matched correctly.</div>
                        </div>
                        <span class="pill">Step 2</span>
                    </div>
                    <div class="gateway-form-grid">
                        <div class="pc-field">
                            <label>Payment Type</label>
                            <select class="pc-select" name="payment_type" id="paymentTypeSelect">
                                <option value="prepaid" @selected(old('payment_type') === 'prepaid')>Prepaid</option>
                                <option value="postpaid" @selected(old('payment_type') === 'postpaid')>Postpaid</option>
                            </select>
                            <div class="pc-help">Prepaid uses paybill 888880, postpaid uses 888888.</div>
                        </div>
                        <div class="pc-field">
                            <label>Meter Number</label>
                            <input class="pc-input" name="meter_number" id="meterInput" value="{{ old('meter_number', $hostel?->meter_no) }}" required>
                        </div>
                        <div class="pc-field">
                            <label>Amount</label>
                            <input class="pc-input" type="number" step="0.01" min="1" name="amount" id="amountInput" value="{{ old('amount', number_format((float) ($hostel?->amount_due ?? 0), 2, '.', '')) }}" required>
                        </div>
                    </div>
                </section>

                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">Customer And Queue Behavior</h4>
                            <div class="gateway-form-section-text">Finish the delivery context and decide whether this should go to the helper queue immediately after saving.</div>
                        </div>
                        <span class="pill">Step 3</span>
                    </div>
                    <div class="gateway-form-grid">
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
                    </div>
                </section>
                <div class="pc-actions">
                    <button class="btn" type="submit">Save And Queue Request</button>
                    <button class="btn2" type="button" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="gateway-modal @if($showDeviceForm) is-open @endif" id="gatewayDeviceCreateModal" aria-hidden="{{ $showDeviceForm ? 'false' : 'true' }}">
    <div class="gateway-modal-card">
        <div class="gateway-modal-head">
            <div>
                <h3>Register Gateway Helper</h3>
                <div class="gateway-modal-sub">Set the phone identity, helper pairing token, and SMSGate delivery mode in one place.</div>
            </div>
            <button class="gateway-modal-close" type="button" data-modal-close>&times;</button>
        </div>
        <div class="gateway-modal-body">
            <div class="gateway-step-band">
                <div class="gateway-step-card"><strong>Helper identity</strong><span>Name the device clearly so switching active helpers later is easy.</span></div>
                <div class="gateway-step-card"><strong>SMSGate mode</strong><span>Use cloud if that is the stable delivery path. Keep other modes only as fallback.</span></div>
                <div class="gateway-step-card"><strong>Activate immediately</strong><span>Make the new helper active if it should start receiving jobs right away.</span></div>
            </div>
            <form class="pc-form gateway-form-stack" method="POST" action="{{ route('petty.tokens.gateway.devices.store') }}">
                @csrf
                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">Helper Identity</h4>
                            <div class="gateway-form-section-text">These values identify the phone in Laravel and let the helper app pair once, then run in the background.</div>
                        </div>
                        <span class="pill">Step 1</span>
                    </div>
                    <div class="gateway-form-grid">
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
                        <div class="pc-field gateway-span-2">
                            <label>API Token</label>
                            <input class="pc-input" name="api_token" placeholder="internal secure token">
                        </div>
                    </div>
                </section>

                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">SMSGate Delivery Setup</h4>
                            <div class="gateway-form-section-text">Choose the delivery route and keep cloud credentials front and center. Local and public stay available as fallback only if you want them.</div>
                        </div>
                        <span class="pill">Step 2</span>
                    </div>
                    <div class="gateway-form-grid">
                        <div class="pc-field">
                            <label>SMSGate Mode</label>
                            <select class="pc-select" name="smsgate_mode">
                                <option value="cloud" selected>Cloud only</option>
                                <option value="auto">Cloud first, then public, then local</option>
                                <option value="both">Local first, public fallback</option>
                                <option value="local">Local only</option>
                                <option value="public">Public only</option>
                            </select>
                        </div>
                        <div class="pc-field gateway-span-2">
                            <label>SMSGate Public URL</label>
                            <input class="pc-input" name="smsgate_public_url" value="https://api.sms-gate.app:443" placeholder="https://api.sms-gate.app:443">
                        </div>
                        <div class="pc-field">
                            <label>SMSGate Username</label>
                            <input class="pc-input" name="smsgate_username" value="SNR0Q0" placeholder="SMSGate username">
                        </div>
                        <div class="pc-field">
                            <label>SMSGate Password</label>
                            <input class="pc-input" name="smsgate_password" value="mlohop4zoboa5d" placeholder="SMSGate password">
                        </div>
                        <div class="pc-field">
                            <label>SMSGate Device ID</label>
                            <input class="pc-input" name="smsgate_device_id" value="tDH9asJlEyt2rlVZH1xEY" placeholder="SMSGate device id">
                        </div>
                        <div class="pc-field gateway-span-2">
                            <label>SMSGate Local URL</label>
                            <input class="pc-input" name="smsgate_local_url" placeholder="Optional local fallback">
                        </div>
                        <div class="pc-field">
                            <label>SMSGate SIM Number</label>
                            <input class="pc-input" type="number" min="1" max="2" name="smsgate_sim_number" placeholder="Optional">
                        </div>
                    </div>
                </section>

                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">Activation</h4>
                            <div class="gateway-form-section-text">Decide whether this helper becomes the live active gateway right away.</div>
                        </div>
                        <span class="pill">Step 3</span>
                    </div>
                    <div class="gateway-form-grid">
                        <div class="pc-field gateway-span-2">
                            <label class="pc-check">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" checked>
                                Active device
                            </label>
                        </div>
                        <div class="pc-field">
                            <label class="pc-check">
                                <input type="hidden" name="smsgate_enabled" value="0">
                                <input type="checkbox" name="smsgate_enabled" value="1" checked>
                                Enable SMSGate sending
                            </label>
                        </div>
                    </div>
                </section>
                <div class="pc-actions">
                    <button class="btn" type="submit">Save Gateway Helper</button>
                    <button class="btn2" type="button" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="gateway-modal" id="gatewayTestSmsModal" aria-hidden="true">
    <div class="gateway-modal-card">
        <div class="gateway-modal-head">
            <div>
                <h3>Test SMSGate Delivery</h3>
                <div class="gateway-modal-sub">Send a custom test SMS to your own number through the selected gateway. If this works, the SMSGate path itself is healthy.</div>
            </div>
            <button class="gateway-modal-close" type="button" data-modal-close>&times;</button>
        </div>
        <div class="gateway-modal-body">
            <div class="gateway-step-band">
                <div class="gateway-step-card"><strong>Pick the gateway</strong><span>Usually the active gateway is enough, but you can target another configured device if needed.</span></div>
                <div class="gateway-step-card"><strong>Send to your number</strong><span>Use a number you can immediately check so you know if cloud delivery is working or not.</span></div>
                <div class="gateway-step-card"><strong>Watch the saved status</strong><span>Success updates last tested time. Failure saves the last SMSGate error on the device.</span></div>
            </div>
            <form class="pc-form gateway-form-stack" method="POST" action="{{ route('petty.tokens.gateway.test_sms') }}">
                @csrf
                <section class="gateway-form-section">
                    <div class="gateway-form-section-head">
                        <div>
                            <h4 class="gateway-form-section-title">Delivery Check Setup</h4>
                            <div class="gateway-form-section-text">Target the live gateway, choose your destination number, then send a custom message through the exact SMSGate route this device is configured to use.</div>
                        </div>
                        <span class="pill">Step 1</span>
                    </div>
                    <div class="gateway-form-grid">
                        <div class="pc-field">
                            <label>Gateway Device</label>
                            <select class="pc-select" name="gateway_device_id">
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" @selected($activeDevice && $activeDevice->id === $device->id)>
                                        {{ $device->name }} @if($device->is_active) (Active) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pc-field gateway-span-2">
                            <label>Destination Phone</label>
                            <input class="pc-input" name="phone_number" value="{{ old('phone_number', $defaultGatewayPhone) }}" placeholder="2547XXXXXXXX" required>
                        </div>
                        <div class="pc-field gateway-span-3">
                            <label>Test Message</label>
                            <textarea class="pc-textarea" name="message" rows="7" required>{{ old('message', 'Skybrix Gateway SMSGate test message. If you received this, the cloud sender is working.') }}</textarea>
                        </div>
                    </div>
                </section>
                <div class="pc-actions">
                    <button class="btn" type="submit">Send Test SMS</button>
                    <button class="btn2" type="button" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($devices as $device)
    <div class="gateway-modal" id="gatewayDeviceEditModal-{{ $device->id }}" aria-hidden="true">
        <div class="gateway-modal-card">
            <div class="gateway-modal-head">
                <div>
                    <h3>Edit {{ $device->name }}</h3>
                    <div class="gateway-modal-sub">Update pairing, SMSGate mode, credentials, or active state for this helper.</div>
                </div>
                <button class="gateway-modal-close" type="button" data-modal-close>&times;</button>
            </div>
            <div class="gateway-modal-body">
                <div class="gateway-step-band">
                    <div class="gateway-step-card"><strong>Core helper identity</strong><span>Keep the phone name, UUID, number, and pairing token obvious so switching devices stays clean.</span></div>
                    <div class="gateway-step-card"><strong>SMSGate routing and credentials</strong><span>Use this section to tune cloud, public, or local delivery without cluttering the queue page.</span></div>
                    <div class="gateway-step-card"><strong>Activation and behavior</strong><span>Decide if this becomes the active helper and whether Laravel should send customer SMS through SMSGate for it.</span></div>
                </div>
                <form class="pc-form gateway-form-stack" method="POST" action="{{ route('petty.tokens.gateway.devices.update', $device) }}">
                    @csrf
                    @method('PUT')
                    <section class="gateway-form-section">
                        <div class="gateway-form-section-head">
                            <div>
                                <h4 class="gateway-form-section-title">Helper Identity</h4>
                                <div class="gateway-form-section-text">This is the pairing surface used by Laravel and the helper app. Keep it readable and stable for the actual phone you are using.</div>
                            </div>
                            <span class="pill">Step 1</span>
                        </div>
                        <div class="gateway-form-grid">
                            <div class="pc-field">
                                <label>Device Name</label>
                                <input class="pc-input" name="name" value="{{ $device->name }}" required>
                            </div>
                            <div class="pc-field">
                                <label>Device UUID</label>
                                <input class="pc-input" name="device_uuid" value="{{ $device->device_uuid }}" required>
                            </div>
                            <div class="pc-field">
                                <label>Phone Number</label>
                                <input class="pc-input" name="phone_number" value="{{ $device->phone_number }}">
                            </div>
                            <div class="pc-field gateway-span-3">
                                <label>API Token</label>
                                <input class="pc-input" name="api_token" value="{{ $device->api_token }}" required>
                            </div>
                        </div>
                    </section>

                    <section class="gateway-form-section">
                        <div class="gateway-form-section-head">
                            <div>
                                <h4 class="gateway-form-section-title">SMSGate Delivery Setup</h4>
                                <div class="gateway-form-section-text">Tune the delivery mode, endpoint order, and provider credentials here. The whole section stays together so it reads like one configuration block instead of loose fields.</div>
                            </div>
                            <span class="pill">Step 2</span>
                        </div>
                        <div class="gateway-form-grid">
                            <div class="pc-field">
                                <label>SMSGate Mode</label>
                                <select class="pc-select" name="smsgate_mode">
                                    @foreach(['cloud' => 'Cloud only', 'auto' => 'Cloud first, then public, then local', 'both' => 'Local first, public fallback', 'local' => 'Local only', 'public' => 'Public only'] as $modeValue => $modeLabel)
                                        <option value="{{ $modeValue }}" @selected(($device->smsgate_mode ?? 'cloud') === $modeValue)>{{ $modeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pc-field gateway-span-2">
                                <label>SMSGate Public URL</label>
                                <input class="pc-input" name="smsgate_public_url" value="{{ $device->smsgate_public_url }}">
                            </div>
                            <div class="pc-field gateway-span-2">
                                <label>SMSGate Local URL</label>
                                <input class="pc-input" name="smsgate_local_url" value="{{ $device->smsgate_local_url }}">
                            </div>
                            <div class="pc-field">
                                <label>SMSGate SIM Number</label>
                                <input class="pc-input" type="number" min="1" max="2" name="smsgate_sim_number" value="{{ $device->smsgate_sim_number }}">
                            </div>
                            <div class="pc-field">
                                <label>SMSGate Username</label>
                                <input class="pc-input" name="smsgate_username" value="{{ $device->smsgate_username }}">
                            </div>
                            <div class="pc-field">
                                <label>SMSGate Password</label>
                                <input class="pc-input" name="smsgate_password" value="{{ $device->smsgate_password }}">
                            </div>
                            <div class="pc-field gateway-span-3">
                                <label>SMSGate Device ID</label>
                                <input class="pc-input" name="smsgate_device_id" value="{{ $device->smsgate_device_id }}">
                            </div>
                        </div>
                    </section>

                    <section class="gateway-form-section">
                        <div class="gateway-form-section-head">
                            <div>
                                <h4 class="gateway-form-section-title">Activation And Behavior</h4>
                                <div class="gateway-form-section-text">Choose if this device should take over as the active helper now and whether Laravel should route customer sends through SMSGate for it.</div>
                            </div>
                            <span class="pill">Step 3</span>
                        </div>
                        <div class="gateway-form-grid">
                            <div class="pc-field gateway-span-2">
                                <label class="pc-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($device->is_active)>
                                    Active device
                                </label>
                            </div>
                            <div class="pc-field">
                                <label class="pc-check">
                                    <input type="hidden" name="smsgate_enabled" value="0">
                                    <input type="checkbox" name="smsgate_enabled" value="1" @checked($device->smsgate_enabled)>
                                    Send customer SMS through SMSGate from Laravel
                                </label>
                            </div>
                        </div>
                    </section>
                    <div class="pc-actions">
                        <button class="btn" type="submit">Update Device</button>
                        <button class="btn2" type="button" data-modal-close>Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        const hostelSelect = document.getElementById('hostelSelect');
        const meterInput = document.getElementById('meterInput');
        const receiverPhoneInput = document.getElementById('receiverPhoneInput');
        const customerNameInput = document.getElementById('customerNameInput');
        const amountInput = document.getElementById('amountInput');

        if (hostelSelect) {
            hostelSelect.addEventListener('change', function () {
                const option = hostelSelect.options[hostelSelect.selectedIndex];
                if (!option || !option.value) return;

                if (meterInput && !meterInput.value) meterInput.value = option.dataset.meter || '';
                if (receiverPhoneInput && !receiverPhoneInput.value) receiverPhoneInput.value = option.dataset.phone || '';
                if (customerNameInput && !customerNameInput.value) customerNameInput.value = option.dataset.name || '';
                if (amountInput && (!amountInput.value || Number(amountInput.value) <= 0)) amountInput.value = option.dataset.amount || '';
            });
        }

        function setModalState(modal, open) {
            if (!modal) return;
            modal.classList.toggle('is-open', open);
            modal.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.style.overflow = open ? 'hidden' : '';
        }

        document.querySelectorAll('[data-modal-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = document.getElementById(button.getAttribute('data-modal-open'));
                setModalState(modal, true);
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = button.closest('.gateway-modal');
                setModalState(modal, false);
            });
        });

        document.querySelectorAll('.gateway-modal').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    setModalState(modal, false);
                }
            });
        });
    }());
</script>
@endpush
