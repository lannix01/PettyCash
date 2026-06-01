@extends('pettycash::layouts.app')

@section('title', 'Hostel Agreement')

@push('styles')
<style>
    .agreement-shell{max-width:none;margin:0}
    .agreement-hero{
        position:relative;
        overflow:hidden;
        border:1px solid #e4e7ec;
        border-radius:18px;
        background:#fff;
        padding:20px;
        box-shadow:0 10px 24px rgba(16,24,40,.05);
    }
    .agreement-hero-top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
    .agreement-kicker{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:7px 12px;
        border-radius:999px;
        background:#eef4ff;
        color:#1849a9;
        font-size:11px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
    }
    .agreement-title{margin:10px 0 4px;font-size:28px;line-height:1.04;letter-spacing:-.04em}
    .agreement-note{max-width:760px;color:#475467;font-size:13px;line-height:1.55}
    .agreement-badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}
    .agreement-badge{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        background:rgba(255,255,255,.9);
        border:1px solid #d7deef;
        color:#344054;
        font-size:12px;
        font-weight:800;
    }
    .agreement-stats{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:12px;
        margin-top:20px;
    }
    .agreement-stat{
        border:1px solid #dbe3f4;
        border-radius:18px;
        background:rgba(255,255,255,.86);
        padding:14px;
    }
    .agreement-stat-label{
        font-size:11px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#667085;
    }
    .agreement-stat-value{
        margin-top:8px;
        font-size:22px;
        font-weight:900;
        color:#101828;
        letter-spacing:-.03em;
    }
    .agreement-workspace{
        display:grid;
        grid-template-columns:minmax(0,1.65fr) minmax(320px,.85fr);
        gap:18px;
        margin-top:18px;
        align-items:start;
    }
    .agreement-family-context{
        display:grid;
        grid-template-columns:minmax(280px,.7fr) minmax(0,1.3fr);
        gap:18px;
        margin-top:18px;
        align-items:start;
    }
    .agreement-form-card{
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:#fff;
        box-shadow:0 10px 24px rgba(16,24,40,.05);
        padding:18px;
    }
    .agreement-context-card{
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:#fff;
        box-shadow:0 10px 24px rgba(16,24,40,.05);
        padding:18px;
    }
    .agreement-context-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
        margin-top:14px;
    }
    .agreement-context-stat{
        border:1px solid #eaecf0;
        border-radius:12px;
        background:#fcfcfd;
        padding:10px 12px;
    }
    .agreement-context-label{
        font-size:10px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#98a2b3;
    }
    .agreement-context-value{
        margin-top:4px;
        font-size:14px;
        font-weight:900;
        color:#101828;
    }
    .agreement-child-table{margin-top:10px}
    .agreement-preview{
        position:sticky;
        top:92px;
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:#fff;
        box-shadow:0 10px 24px rgba(16,24,40,.05);
        padding:18px;
    }
    .flow-block{
        grid-column:1 / -1;
        border:1px solid #eaecf0;
        border-radius:16px;
        background:#fff;
        padding:15px;
    }
    .flow-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px}
    .flow-step{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:28px;
        height:28px;
        border-radius:50%;
        background:#6941c6;
        color:#fff;
        font-size:12px;
        font-weight:900;
    }
    .flow-head-copy h3{margin:0;font-size:18px;letter-spacing:-.02em}
    .flow-head-copy p{margin:4px 0 0;color:#667085;font-size:13px;line-height:1.6}
    .compact-select-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
        align-items:start;
    }
    .channel-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
    }
    .channel-card{
        border:1px solid #eaecf0;
        border-radius:18px;
        background:#fcfcfd;
        padding:14px;
    }
    .channel-card-title{font-size:13px;font-weight:900;color:#101828}
    .channel-card-copy{margin-top:6px;font-size:12px;color:#667085;line-height:1.6}
    .field-stack{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .field-stack .pc-field{grid-column:span 1}
    .field-stack .pc-field.full{grid-column:1 / -1}
    .agreement-field[hidden]{display:none !important}
    .family-search{
        display:flex;
        gap:10px;
        align-items:center;
        flex-wrap:wrap;
    }
    .family-search-shell{
        position:relative;
        flex:1 1 360px;
        min-width:260px;
    }
    .family-search-menu{
        position:absolute;
        top:calc(100% + 8px);
        left:0;
        right:0;
        z-index:40;
        border:1px solid #d0d5dd;
        border-radius:18px;
        background:#fff;
        box-shadow:0 22px 42px rgba(16,24,40,.16);
        max-height:320px;
        overflow:auto;
    }
    .family-search-item{
        width:100%;
        border:none;
        background:#fff;
        text-align:left;
        padding:12px 14px;
        cursor:pointer;
    }
    .family-search-item + .family-search-item{border-top:1px solid #f2f4f7}
    .family-search-item:hover{background:#f8faff}
    .family-search-title{
        display:flex;
        justify-content:space-between;
        gap:10px;
        align-items:center;
        font-size:13px;
        font-weight:900;
        color:#101828;
    }
    .family-search-meta{
        margin-top:6px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        color:#667085;
        font-size:12px;
    }
    .family-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        background:#fff;
        border:1px solid #d0d5dd;
        font-size:12px;
        font-weight:800;
        color:#344054;
    }
    .family-chip button{
        border:none;
        background:transparent;
        padding:0;
        cursor:pointer;
        color:#667085;
        font-weight:900;
    }
    .family-chip.is-existing{
        border-color:#b2ddff;
        background:#eff8ff;
        color:#1849a9;
    }
    .family-chip-stack{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:12px;
    }
    .family-board{
        margin-top:14px;
        border:1px solid #eaecf0;
        border-radius:18px;
        background:#fff;
        overflow:hidden;
    }
    .family-board-head{
        padding:14px 16px;
        border-bottom:1px solid #eaecf0;
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
        background:#f8fafc;
    }
    .family-board-body{padding:8px 12px 12px}
    .family-row{
        display:grid;
        grid-template-columns:minmax(0,1.2fr) repeat(3,minmax(0,.8fr));
        gap:10px;
        align-items:start;
        padding:12px 6px;
        border-bottom:1px solid #f2f4f7;
    }
    .family-row:last-child{border-bottom:none}
    .family-name{font-size:13px;font-weight:900;color:#101828}
    .family-sub{margin-top:4px;font-size:12px;color:#667085;line-height:1.6}
    .family-mini-label{font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#98a2b3}
    .family-mini-value{margin-top:4px;font-size:12px;font-weight:800;color:#344054}
    .type-help{
        margin-top:12px;
        border:1px solid #dbe3f4;
        border-radius:14px;
        background:#f8fbff;
        padding:12px 14px;
    }
    .type-help-title{font-size:13px;font-weight:900;color:#101828}
    .type-help-copy{margin-top:6px;font-size:13px;color:#475467;line-height:1.65}
    .preview-shell{display:grid;gap:14px}
    .preview-card{
        border:1px solid #eaecf0;
        border-radius:14px;
        background:#fcfcfd;
        padding:12px 14px;
    }
    .preview-label{font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#98a2b3}
    .preview-value{margin-top:8px;font-size:18px;font-weight:900;letter-spacing:-.03em;color:#101828}
    .preview-copy{margin-top:6px;font-size:13px;color:#667085;line-height:1.65}
    .preview-list{display:grid;gap:10px;margin-top:10px}
    .preview-line{
        display:flex;
        justify-content:space-between;
        gap:14px;
        align-items:flex-start;
        font-size:13px;
    }
    .preview-line strong{color:#344054}
    .preview-pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:5px 10px;
        border-radius:999px;
        background:#eef4ff;
        color:#1849a9;
        font-size:11px;
        font-weight:900;
        letter-spacing:.05em;
        text-transform:uppercase;
    }
    .ajax-row{
        display:flex;
        align-items:center;
        gap:10px;
        color:#667085;
        font-size:12px;
        min-height:18px;
    }
    .hidden-select{display:none}
    @media(max-width:1080px){
        .agreement-family-context,
        .agreement-workspace{grid-template-columns:1fr}
        .agreement-preview{position:static}
    }
    @media(max-width:900px){
        .agreement-stats,
        .agreement-context-grid,
        .compact-select-grid,
        .channel-grid,
        .field-stack{grid-template-columns:1fr}
        .family-row{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    $familyChildren = collect($familyChildren ?? []);
    $parentRouterCount = (int) ($hostel->no_of_routers ?? 0);
    $childRouterCount = (int) $familyChildren->sum(fn ($child) => (int) ($child->no_of_routers ?? 0));
    $familyRouterTotal = $parentRouterCount + $childRouterCount;
    $selectedType = (string) old('agreement_type', $agreementType ?? 'none');
    $currentAgreementType = strtolower((string) ($agreementType ?? ($hostel->agreement_type ?? 'none')));
    if (!in_array($currentAgreementType, ['token', 'send_money', 'package', 'none'], true)) {
        $currentAgreementType = 'none';
    }
    $agreementConfigured = $currentAgreementType !== 'none'
        || trim((string) ($hostel->agreement_label ?? '')) !== '';
    $isSetupFlow = request()->boolean('setup');
    $focusStep = in_array((string) request()->query('focus_step', ''), ['agreement-type', 'agreement-details', 'agreement-family'], true)
        ? (string) request()->query('focus_step')
        : '';
    $selectedApply = collect(old('apply_to_hostels', $familyChildren->pluck('id')->all()))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values()
        ->all();
    $agreementTerminated = (bool) ($terminationSupported ?? false) && !empty($hostel->agreement_terminated_at);
    $mergePickerState = (array) ($mergePickerState ?? []);
    $mergePickerAvailable = (bool) ($mergePickerState['available'] ?? true);
    $mergePickerMessage = (string) ($mergePickerState['message'] ?? '');
    $agreementSubmitLabel = ($isSetupFlow || !$agreementConfigured || $agreementTerminated) ? 'Save Agreement' : 'Update Agreement';
    $agreementSuccessTitle = $agreementSubmitLabel === 'Update Agreement' ? 'Agreement Updated' : 'Agreement Saved';
@endphp

<div class="agreement-shell">
    <div class="agreement-hero">
        <div class="agreement-hero-top">
            <div>
                <span class="agreement-kicker">Agreement Builder</span>
                <h1 class="agreement-title">{{ $hostel->hostel_name }}</h1>
                <div class="agreement-note">
                    @if($isSetupFlow)
                        Set one billing flow, then choose which original ONT sites should join this parent.
                    @elseif($focusStep === 'agreement-family')
                        Add child hostels under this parent without stepping through the full setup again.
                    @else
                        Update any agreement stage from here. Open only the section you want to change.
                    @endif
                </div>
                <div class="agreement-badges">
                    <span class="agreement-badge">{{ $isSetupFlow ? 'Step 2 of 2' : 'Existing Hostel' }}</span>
                    <span class="agreement-badge">Parent Hostel</span>
                    @if($agreementTerminated)
                        <span class="agreement-badge" style="border-color:#fecdca;background:#fef3f2;color:#b42318">Agreement previously terminated</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Back to Hostel</a>
            </div>
        </div>

        <div class="agreement-stats">
            <div class="agreement-stat">
                <div class="agreement-stat-label">Parent Site</div>
                <div class="agreement-stat-value">{{ $hostel->ont_site_sn ?: '-' }}</div>
            </div>
            <div class="agreement-stat">
                <div class="agreement-stat-label">Total Routers</div>
                <div class="agreement-stat-value">{{ $familyRouterTotal }}</div>
            </div>
            <div class="agreement-stat">
                <div class="agreement-stat-label">Child Hostels</div>
                <div class="agreement-stat-value">{{ $familyChildren->count() }}</div>
            </div>
            <div class="agreement-stat">
                <div class="agreement-stat-label">Parent / Child Routers</div>
                <div class="agreement-stat-value">{{ $parentRouterCount }} / {{ $childRouterCount }}</div>
            </div>
        </div>
    </div>

    @if($familyChildren->isNotEmpty())
        <div class="agreement-family-context">
            <div class="agreement-context-card">
                <span class="preview-pill">Parent Hostel</span>
                <div class="agreement-title" style="font-size:24px;margin-top:12px">{{ $hostel->hostel_name }}</div>
                <div class="agreement-note" style="max-width:none">This hostel owns the agreement. The child hostels below inherit and are managed from here.</div>
                <div class="agreement-context-grid">
                    <div class="agreement-context-stat">
                        <div class="agreement-context-label">Agreement</div>
                        <div class="agreement-context-value">{{ $selectedType === 'none' ? 'No Agreement' : ucwords(str_replace('_', ' ', $selectedType)) }}</div>
                    </div>
                    <div class="agreement-context-stat">
                        <div class="agreement-context-label">Children</div>
                        <div class="agreement-context-value">{{ $familyChildren->count() }}</div>
                    </div>
                    <div class="agreement-context-stat">
                        <div class="agreement-context-label">Billing Cycle</div>
                        <div class="agreement-context-value">{{ strtoupper((string) old('stake', $hostel->stake)) }}</div>
                    </div>
                    <div class="agreement-context-stat">
                        <div class="agreement-context-label">Due Amount</div>
                        <div class="agreement-context-value">{{ number_format((float) old('amount_due', $hostel->amount_due), 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="agreement-context-card">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
                    <div>
                        <span class="preview-pill">Child Hostels</span>
                        <div class="agreement-note" style="margin-top:10px;max-width:none">These child hostels are already attached to this parent and will stay selected below unless you remove them.</div>
                    </div>
                    <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Back to Hostel</a>
                </div>
                <div class="agreement-child-table">
                    <table>
                        <thead>
                        <tr>
                            <th>Hostel</th>
                            <th>Site S.N</th>
                            <th>Meter Number</th>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>Due</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($familyChildren as $childHostel)
                            <tr>
                                <td>{{ $childHostel['hostel_name'] ?? $childHostel->hostel_name ?? '-' }}</td>
                                <td>{{ $childHostel['ont_site_sn'] ?? $childHostel->ont_site_sn ?? '-' }}</td>
                                <td>{{ $childHostel['meter_no'] ?? $childHostel->meter_no ?? '-' }}</td>
                                <td>{{ $childHostel['contact_person'] ?? $childHostel->contact_person ?? '-' }}</td>
                                <td>{{ $childHostel['phone_no'] ?? $childHostel->phone_no ?? '-' }}</td>
                                <td>{{ number_format((float) ($childHostel['amount_due'] ?? $childHostel->amount_due ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="agreement-workspace">
        <div class="agreement-form-card">
            <form
                class="pc-form agreement-flow-form"
                method="POST"
                action="{{ route('petty.tokens.hostels.agreement.update', $hostel->id) }}"
                data-json-submit="1"
                data-success-title="{{ $agreementSuccessTitle }}"
            >
                @csrf
                @method('PUT')

                <div class="err full" data-form-errors @if(!$errors->any()) hidden @endif>
                    @if($errors->any())
                        @foreach($errors->all() as $e)
                            <div>{{ $e }}</div>
                        @endforeach
                    @endif
                </div>

                <div
                    class="pc-workflow"
                    data-workflow
                    @if(!$isSetupFlow) data-workflow-unlock-all="1" data-workflow-open-none="1" @endif
                    @if($focusStep !== '') data-workflow-open-step="{{ $focusStep }}" @endif
                >
                    <section class="flow-block pc-step" data-step="agreement-type" @if($isSetupFlow) data-step-open="1" data-step-unlocked="1" @endif>
                        <button class="pc-step-trigger" type="button" data-step-toggle>
                            <span class="pc-step-trigger-main">
                                <span class="pc-step-index">1</span>
                                <span class="pc-step-copy">
                                    <span class="pc-step-title">Agreement type</span>
                                    <span class="pc-step-text">Choose the flow once. Only the matching inputs stay visible.</span>
                                </span>
                            </span>
                            <span style="display:flex;align-items:center;gap:10px">
                                <span class="pc-step-meta" data-step-meta>Open</span>
                                <span class="pc-step-arrow">⌄</span>
                            </span>
                        </button>
                        <div class="pc-step-body" data-step-body>
                            <div class="pc-step-panel">
                                <div class="compact-select-grid">
                                    <div class="pc-field full">
                                        <label for="agreementType">Agreement Type</label>
                                        <select class="pc-select" name="agreement_type" id="agreementType" required>
                                            <option value="token" @selected($selectedType === 'token')>Token</option>
                                            <option value="send_money" @selected($selectedType === 'send_money')>Send Money</option>
                                            <option value="package" @selected($selectedType === 'package')>Package</option>
                                            <option value="none" @selected($selectedType === 'none')>No Agreement</option>
                                        </select>
                                        <div class="pc-help">Token uses meter number. Send Money uses name and phone number. Package uses package name and phone number.</div>
                                    </div>
                                </div>

                                <div class="type-help" id="agreementTypeHelp">
                                    <div class="type-help-title">Current flow</div>
                                    <div class="type-help-copy">Choose an agreement type to load the right fields.</div>
                                </div>

                                <div class="pc-step-actions">
                                    <div class="pc-step-actions-main">
                                        <button class="btn2" type="button" data-step-next>Continue to Agreement Details</button>
                                    </div>
                                    <div class="pc-step-actions-note">Step 2 only shows the fields needed by the flow you picked here.</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="flow-block pc-step" data-step="agreement-details">
                        <button class="pc-step-trigger" type="button" data-step-toggle>
                            <span class="pc-step-trigger-main">
                                <span class="pc-step-index">2</span>
                                <span class="pc-step-copy">
                                    <span class="pc-step-title">Agreement details</span>
                                    <span class="pc-step-text">Keep it short. Only the selected flow matters here.</span>
                                </span>
                            </span>
                            <span style="display:flex;align-items:center;gap:10px">
                                <span class="pc-step-meta" data-step-meta>Locked</span>
                                <span class="pc-step-arrow">⌄</span>
                            </span>
                        </button>
                        <div class="pc-step-body" data-step-body hidden>
                            <div class="pc-step-panel">
                                <div class="channel-grid">
                                    <div class="channel-card">
                                        <div class="channel-card-title" id="channelCardTitle">Agreement details</div>
                                        <div class="channel-card-copy" id="channelCardCopy">
                                            Select a type above to tailor the form.
                                        </div>
                                    </div>
                                    <div class="channel-card">
                                        <div class="channel-card-title">Billing outcome</div>
                                        <div class="channel-card-copy" id="channelCardOutcome">
                                            The due amount and billing cycle stay attached to the joined hostels under this parent.
                                        </div>
                                    </div>
                                </div>

                                <div class="field-stack" style="margin-top:14px">
                                    <div class="pc-field agreement-field" data-types="token">
                                        <label for="meterNoInput">Meter Number</label>
                                        <input class="pc-input" type="text" name="meter_no" id="meterNoInput" value="{{ old('meter_no', $hostel->meter_no) }}">
                                        <div class="pc-help">Used only for token flow.</div>
                                    </div>

                                    <div class="pc-field agreement-field" data-types="send_money,token,package">
                                        <label for="phoneNoInput" id="phoneNoLabel">Phone Number</label>
                                        <input class="pc-input" type="text" name="phone_no" id="phoneNoInput" value="{{ old('phone_no', $hostel->phone_no) }}">
                                        <div class="pc-help" id="phoneNoHelp">Saved as the default phone number for this agreement.</div>
                                    </div>

                                    <div class="pc-field full agreement-field" data-types="package">
                                        <label for="agreementLabelInput">Package Name</label>
                                        <input class="pc-input" type="text" name="agreement_label" id="agreementLabelInput" value="{{ old('agreement_label', $hostel->agreement_label) }}" placeholder="e.g. Campus package">
                                        <div class="pc-help">Shown on package credits and reports.</div>
                                    </div>

                                    <div class="pc-field agreement-field" data-types="token,send_money,package">
                                        <label for="contactPersonInput" id="contactPersonLabel">Name</label>
                                        <input class="pc-input" type="text" name="contact_person" id="contactPersonInput" value="{{ old('contact_person', $hostel->contact_person) }}">
                                        <div class="pc-help" id="contactPersonHelp">Saved as the default name on this agreement.</div>
                                    </div>

                                    <div class="pc-field agreement-field" data-types="token,send_money,package,none">
                                        <label for="stakeInput">Billing Cycle</label>
                                        <select class="pc-select" name="stake" id="stakeInput" required>
                                            <option value="monthly" @selected(old('stake', $hostel->stake) === 'monthly')>Monthly</option>
                                            <option value="semester" @selected(old('stake', $hostel->stake) === 'semester')>Semester</option>
                                        </select>
                                        <div class="pc-help">Used for due date reminders and rollovers.</div>
                                    </div>

                                    <div class="pc-field agreement-field" data-types="token,send_money,package,none">
                                        <label for="amountDueInput" id="amountDueLabel">Due Amount</label>
                                        <input class="pc-input" type="number" step="0.01" min="0" name="amount_due" id="amountDueInput" required value="{{ old('amount_due', $hostel->amount_due) }}">
                                        <div class="pc-help" id="amountDueHelp">Managed from the parent hostel.</div>
                                    </div>
                                </div>

                                <div class="pc-step-actions">
                                    <div class="pc-step-actions-main">
                                        <button class="btn2" type="button" data-step-next>Continue to Child Hostels</button>
                                        <button class="btn" type="submit" data-submit-label="{{ $agreementSubmitLabel }}" data-loading-label="{{ $agreementSubmitLabel === 'Update Agreement' ? 'Updating Agreement...' : 'Saving Agreement...' }}" data-loading-copy="Applying the agreement details to this hostel family.">{{ $agreementSubmitLabel }}</button>
                                    </div>
                                    <div class="pc-step-actions-note">Step 3 is optional. Save here when this agreement is only for the parent hostel.</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="flow-block pc-step" data-step="agreement-family">
                        <button class="pc-step-trigger" type="button" data-step-toggle>
                                <span class="pc-step-trigger-main">
                                    <span class="pc-step-index">3</span>
                                    <span class="pc-step-copy">
                                    <span class="pc-step-title">Manage Child Hostels (optional)</span>
                                    <span class="pc-step-text">Add or remove child hostels when this parent agreement should manage more than one hostel.</span>
                                </span>
                            </span>
                            <span style="display:flex;align-items:center;gap:10px">
                                <span class="pc-step-meta" data-step-meta>Locked</span>
                                <span class="pc-step-arrow">⌄</span>
                            </span>
                        </button>
                        <div class="pc-step-body" data-step-body hidden>
                            <div class="pc-step-panel">
                                @unless($mergePickerAvailable)
                                    <div class="type-help" style="margin-top:0;border-color:#fecdca;background:#fef3f2">
                                        <div class="type-help-title" style="color:#b42318">ONT directory unavailable</div>
                                        <div class="type-help-copy" style="color:#b42318">{{ $mergePickerMessage !== '' ? $mergePickerMessage : 'Joined hostels cannot be searched right now.' }}</div>
                                    </div>
                                @endunless

                                <div class="family-search">
                                    <div class="family-search-shell" id="agreementHostelPicker" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'ont_catalog'], false) }}" data-exclude="{{ $hostel->id }}">
                                        <input class="pc-input" type="text" id="agreementHostelInput" placeholder="Search original ONT site or hostel name" @disabled(!$mergePickerAvailable)>
                                        <div class="family-search-menu" id="agreementHostelMenu" hidden></div>
                                    </div>
                                    <div class="ajax-row" id="agreementHostelStatus" aria-live="polite"></div>
                                </div>

                                <div class="family-chip-stack" id="agreementHostelChips"></div>

                                <div class="family-board" id="agreementHostelSummary" hidden>
                                    <div class="family-board-head">
                                        <div>
                                            <div style="font-size:14px;font-weight:900;color:#101828">Joined under this parent</div>
                                            <div class="muted">Shown in the main hostel view.</div>
                                        </div>
                                        <span class="preview-pill" id="agreementSummaryCount">0 joined hostels</span>
                                    </div>
                                    <div class="family-board-body" id="agreementHostelSummaryBody"></div>
                                </div>

                                <div class="pc-step-actions">
                                    <div class="pc-step-actions-main">
                                        <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Back to Hostel</a>
                                        <button class="btn" type="submit" data-submit-label="{{ $agreementSubmitLabel }}" data-loading-label="{{ $agreementSubmitLabel === 'Update Agreement' ? 'Updating Agreement...' : 'Saving Agreement...' }}" data-loading-copy="Saving the parent agreement and joined hostels.">{{ $agreementSubmitLabel }}</button>
                                    </div>
                                    <div class="pc-step-actions-note">Save once the parent and any optional child hostels look right.</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </form>
        </div>

        <aside class="agreement-preview">
            <div class="preview-shell">
                <div class="preview-card">
                    <div class="preview-label">Flow</div>
                    <div class="preview-value" id="previewTypeLabel">No Agreement</div>
                    <div class="preview-copy" id="previewTypeCopy">Choose the billing flow for this parent hostel.</div>
                </div>

                <div class="preview-card">
                    <div class="preview-label">Summary</div>
                    <div class="preview-list">
                        <div class="preview-line">
                            <strong>Parent hostel</strong>
                            <span>{{ $hostel->hostel_name }}</span>
                        </div>
                        <div class="preview-line">
                            <strong>Joined hostels</strong>
                            <span id="previewChildCount">{{ count($selectedApply) }}</span>
                        </div>
                        <div class="preview-line">
                            <strong>Billing cycle</strong>
                            <span id="previewStake">{{ strtoupper((string) old('stake', $hostel->stake)) }}</span>
                        </div>
                        <div class="preview-line">
                            <strong>Amount</strong>
                            <span id="previewAmount">{{ number_format((float) old('amount_due', $hostel->amount_due), 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="preview-card">
                    <div class="preview-label">Rule</div>
                    <div class="preview-copy">
                        Remove a hostel to detach it. Add a hostel to manage it from this parent page.
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.querySelector('.agreement-flow-form');
    const typeInput = document.getElementById('agreementType');
    const typeHelp = document.getElementById('agreementTypeHelp');
    const channelTitle = document.getElementById('channelCardTitle');
    const channelCopy = document.getElementById('channelCardCopy');
    const channelOutcome = document.getElementById('channelCardOutcome');
    const meterInput = document.getElementById('meterNoInput');
    const phoneInput = document.getElementById('phoneNoInput');
    const agreementLabelInput = document.getElementById('agreementLabelInput');
    const contactInput = document.getElementById('contactPersonInput');
    const contactLabel = document.getElementById('contactPersonLabel');
    const contactHelp = document.getElementById('contactPersonHelp');
    const phoneLabel = document.getElementById('phoneNoLabel');
    const phoneHelp = document.getElementById('phoneNoHelp');
    const amountLabel = document.getElementById('amountDueLabel');
    const amountHelp = document.getElementById('amountDueHelp');
    const agreementFields = Array.from(document.querySelectorAll('.agreement-field'));
    const previewTypeLabel = document.getElementById('previewTypeLabel');
    const previewTypeCopy = document.getElementById('previewTypeCopy');
    const previewChildCount = document.getElementById('previewChildCount');
    const previewStake = document.getElementById('previewStake');
    const previewAmount = document.getElementById('previewAmount');
    const stakeInput = document.getElementById('stakeInput');
    const amountInput = document.getElementById('amountDueInput');
    const picker = document.getElementById('agreementHostelPicker');
    const searchInput = document.getElementById('agreementHostelInput');
    const searchMenu = document.getElementById('agreementHostelMenu');
    const searchStatus = document.getElementById('agreementHostelStatus');
    const chips = document.getElementById('agreementHostelChips');
    const summary = document.getElementById('agreementHostelSummary');
    const summaryBody = document.getElementById('agreementHostelSummaryBody');
    const summaryCount = document.getElementById('agreementSummaryCount');
    const formErrors = form ? form.querySelector('[data-form-errors]') : null;
    const preselectedIds = @json($selectedApply);
    const existingChildren = @json($familyChildren->values());

    if (!form || !typeInput) return;

    const typeConfig = {
        token: {
            title: 'Token',
            help: 'Token flow uses meter number and due amount. Phone number is optional.',
            channelTitle: 'Token flow',
            channelCopy: 'Meter number is the main key.',
            outcome: 'Payments deduct petty balance using the parent agreement.',
            preview: 'Meter-based agreement.',
            contactLabel: 'Name',
            contactHelp: 'Optional default name.',
            phoneLabel: 'Phone Number',
            phoneHelp: 'Optional phone number for token flow.',
            amountLabel: 'Due amount',
            amountHelp: 'Used by the whole joined family.',
            requires: { meter: true, phone: false, label: false, contact: false },
        },
        send_money: {
            title: 'Send Money',
            help: 'Send Money uses a saved name and phone number. Meter stays hidden.',
            channelTitle: 'Send Money flow',
            channelCopy: 'Name and phone number are the active fields.',
            outcome: 'Payments use the parent name and phone number for this family.',
            preview: 'Recipient-based agreement.',
            contactLabel: 'Name',
            contactHelp: 'Required for Send Money.',
            phoneLabel: 'Phone Number',
            phoneHelp: 'Required for Send Money.',
            amountLabel: 'Due amount',
            amountHelp: 'Used by the whole joined family.',
            requires: { meter: false, phone: true, label: false, contact: true },
        },
        package: {
            title: 'Package',
            help: 'Package uses package name, phone number, billing cycle, and amount.',
            channelTitle: 'Package flow',
            channelCopy: 'No meter here. Save the phone number that receives the package credit.',
            outcome: 'Entries post as package credits without deduction.',
            preview: 'Package credit agreement.',
            contactLabel: 'Name',
            contactHelp: 'Optional default name.',
            phoneLabel: 'Phone Number',
            phoneHelp: 'Saved as the credit phone number for package entries.',
            amountLabel: 'Package amount',
            amountHelp: 'Used by the whole joined family.',
            requires: { meter: false, phone: false, label: true, contact: false },
        },
        none: {
            title: 'No Agreement',
            help: 'Keep reminders and amount without locking a billing path.',
            channelTitle: 'Tracking only',
            channelCopy: 'Only cycle and amount stay active.',
            outcome: 'The hostel stays active without a live collection path.',
            preview: 'No live billing flow.',
            contactLabel: 'Name',
            contactHelp: 'Hidden until a billing path needs it.',
            phoneLabel: 'Phone Number',
            phoneHelp: 'Hidden until a billing path needs it.',
            amountLabel: 'Tracked amount',
            amountHelp: 'Optional target amount.',
            requires: { meter: false, phone: false, label: false, contact: false },
        },
    };

    const selectedMap = new Map();
    let searchTimer = null;

    function toast(type, title, message) {
        if (window.pettyCreateToast) {
            window.pettyCreateToast({ type, title, message });
            return;
        }
        window.alert(message);
    }

    function setStatus(text, loading) {
        if (!searchStatus) return;
        if (!text) {
            searchStatus.innerHTML = '';
            return;
        }
        searchStatus.innerHTML = loading
            ? '<span class="spinner" aria-hidden="true"></span><span>' + text + '</span>'
            : '<span>' + text + '</span>';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function selectedTypeConfig() {
        return typeConfig[String(typeInput.value || 'none').toLowerCase()] || typeConfig.none;
    }

    function syncAgreementType() {
        const type = String(typeInput.value || 'none').toLowerCase();
        const config = selectedTypeConfig();

        agreementFields.forEach((field) => {
            const types = String(field.dataset.types || '')
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);
            field.hidden = !types.includes(type);
        });

        if (typeHelp) {
            typeHelp.innerHTML =
                '<div class="type-help-title">' + escapeHtml(config.title) + ' flow</div>' +
                '<div class="type-help-copy">' + escapeHtml(config.help) + '</div>';
        }
        if (channelTitle) channelTitle.textContent = config.channelTitle;
        if (channelCopy) channelCopy.textContent = config.channelCopy;
        if (channelOutcome) channelOutcome.textContent = config.outcome;
        if (previewTypeLabel) previewTypeLabel.textContent = config.title;
        if (previewTypeCopy) previewTypeCopy.textContent = config.preview;
        if (contactLabel) contactLabel.textContent = config.contactLabel;
        if (contactHelp) contactHelp.textContent = config.contactHelp;
        if (phoneLabel) phoneLabel.textContent = config.phoneLabel;
        if (phoneHelp) phoneHelp.textContent = config.phoneHelp;
        if (amountLabel) amountLabel.textContent = config.amountLabel;
        if (amountHelp) amountHelp.textContent = config.amountHelp;

        if (meterInput) meterInput.required = !!config.requires.meter;
        if (phoneInput) phoneInput.required = !!config.requires.phone;
        if (agreementLabelInput) agreementLabelInput.required = !!config.requires.label;
        if (contactInput) contactInput.required = !!config.requires.contact;
    }

    typeInput.addEventListener('change', syncAgreementType);

    function updatePreviewMetrics() {
        if (previewChildCount) {
            previewChildCount.textContent = String(selectedMap.size);
        }
        if (previewStake && stakeInput) {
            previewStake.textContent = String(stakeInput.value || '').toUpperCase();
        }
        if (previewAmount && amountInput) {
            const value = Number(amountInput.value || 0);
            previewAmount.textContent = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value);
        }
    }

    function itemLookupToken(item) {
        const siteId = String(item.ont_site_id || item.site_id || '').trim();
        if (siteId !== '') return 'site:' + siteId.toUpperCase();

        const siteSn = String(item.ont_site_sn || item.site_sn || '').trim().toUpperCase();
        if (siteSn !== '') return 'sn:' + siteSn;

        const itemId = Number(item.id || 0);
        if (itemId > 0) return 'hostel:' + itemId;

        const ontKey = String(item.key || '').trim();
        if (ontKey !== '') return 'ont:' + ontKey;

        const hostelName = String(item.hostel_name || item.site_name || '').trim().toUpperCase();
        return hostelName !== '' ? ('name:' + hostelName) : ('unknown:' + Math.random());
    }

    function normalizeSelectedItem(item, isExisting) {
        const itemId = Number(item.id || 0);
        return {
            ...item,
            id: itemId > 0 ? itemId : null,
            key: String(item.key || '').trim() || null,
            hostel_name: String(item.hostel_name || item.site_name || '').trim() || 'Hostel',
            ont_site_id: String(item.ont_site_id || item.site_id || '').trim() || null,
            ont_site_sn: String(item.ont_site_sn || item.site_sn || '').trim() || null,
            meter_no: String(item.meter_no || '').trim() || null,
            phone_no: String(item.phone_no || '').trim() || null,
            stake: String(item.stake || '').trim() || null,
            amount_due: Number(item.amount_due || 0),
            managed_child_count: Number(item.managed_child_count || 0),
            payments_count: Number(item.payments_count || 0),
            merge_status: String(item.merge_status || '').trim() || null,
            merge_status_label: String(item.merge_status_label || '').trim() || null,
            search_match_label: String(item.search_match_label || '').trim() || null,
            search_match_name: String(item.search_match_name || '').trim() || null,
            selection_source: isExisting || itemId > 0 ? 'existing' : 'ont_catalog',
            is_existing_child: !!isExisting || !!item.is_existing_child,
            lookup_token: itemLookupToken(item),
        };
    }

    function resolvedOntSubmissionValue(item) {
        const hostelName = String(item.hostel_name || item.site_name || '').trim();
        if (hostelName !== '') {
            return hostelName;
        }

        const siteId = String(item.ont_site_id || item.site_id || '').trim();
        if (siteId !== '') {
            return 'site:' + siteId;
        }

        return String(item.key || '').trim();
    }

    function renderFamilyBoard() {
        const items = Array.from(selectedMap.values()).sort((a, b) => {
            return String(a.hostel_name || '').localeCompare(String(b.hostel_name || ''));
        });

        chips.innerHTML = '';

        if (items.length === 0) {
            if (summary) summary.hidden = true;
            updatePreviewMetrics();
            return;
        }

        items.forEach((item) => {
            const chip = document.createElement('div');
            chip.className = 'family-chip' + (item.is_existing_child ? ' is-existing' : '');
            chip.dataset.lookupToken = String(item.lookup_token || '');

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            if (item.selection_source === 'ont_catalog' && item.key) {
                hidden.name = 'apply_to_ont_keys[]';
                hidden.value = resolvedOntSubmissionValue(item);
            } else if (item.id) {
                hidden.name = 'apply_to_hostels[]';
                hidden.value = String(item.id);
            }

            const roleText = item.is_existing_child
                ? 'Already joined'
                : (item.selection_source === 'ont_catalog'
                    ? ((item.merge_status_label || 'Will be added from ONT'))
                    : (Number(item.managed_child_count || 0) > 0 ? ('Family root +' + Number(item.managed_child_count || 0)) : 'Joined hostel'));

            chip.innerHTML =
                '<span>' + escapeHtml(item.hostel_name || 'Hostel') + '</span>' +
                '<span style="color:#98a2b3">•</span>' +
                '<span>' + escapeHtml(roleText) + '</span>';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selectedMap.delete(String(item.lookup_token || ''));
                renderFamilyBoard();
            });

            chip.appendChild(remove);
            if (hidden.name) {
                chip.appendChild(hidden);
            }
            chips.appendChild(chip);
        });

        if (!summary || !summaryBody || !summaryCount) {
            updatePreviewMetrics();
            return;
        }

        summaryBody.innerHTML = items.map((item) => {
            const roleText = item.is_existing_child
                ? 'Already linked to this parent'
                : (item.selection_source === 'ont_catalog'
                    ? (item.merge_status === 'merged' ? 'Found in ONT and ready to join.' : 'Will be created from ONT and joined to this parent.')
                    : (Number(item.managed_child_count || 0) > 0 ? ('Will bring ' + Number(item.managed_child_count || 0) + ' joined hostel(s)') : 'Will join this parent'));
            const phone = item.phone_no ? String(item.phone_no) : '-';
            const meter = item.meter_no ? String(item.meter_no) : '-';
            const site = item.ont_site_sn ? String(item.ont_site_sn) : '-';
            const cycle = String((stakeInput && stakeInput.value) || item.stake || 'monthly').toUpperCase();
            const due = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number((amountInput && amountInput.value) || item.amount_due || 0));
            const matchLabel = item.search_match_label ? String(item.search_match_label) : (item.merge_status_label ? String(item.merge_status_label) : '');
            const matchName = item.search_match_name ? String(item.search_match_name) : '';
            return '' +
                '<div class="family-row">' +
                    '<div>' +
                        '<div class="family-name">' + escapeHtml(item.hostel_name || 'Hostel') + '</div>' +
                        '<div class="family-sub">' + escapeHtml(roleText) + (matchLabel ? ' • ' + escapeHtml(matchLabel) : '') + (matchName ? ' • ' + escapeHtml(matchName) : '') + '</div>' +
                    '</div>' +
                    '<div>' +
                        '<div class="family-mini-label">Site / Meter</div>' +
                        '<div class="family-mini-value">' + escapeHtml(site) + ' / ' + escapeHtml(meter) + '</div>' +
                    '</div>' +
                    '<div>' +
                        '<div class="family-mini-label">Phone Number</div>' +
                        '<div class="family-mini-value">' + escapeHtml(phone) + '</div>' +
                    '</div>' +
                    '<div>' +
                        '<div class="family-mini-label">Cycle / Due</div>' +
                        '<div class="family-mini-value">' + escapeHtml(cycle) + ' / ' + escapeHtml(due) + '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        summary.hidden = false;
        summaryCount.textContent = items.length + ' joined hostel' + (items.length === 1 ? '' : 's');
        updatePreviewMetrics();
    }

    function addSelected(item, isExisting) {
        if (!item) return;

        const normalized = normalizeSelectedItem(item, isExisting);
        const existing = selectedMap.get(normalized.lookup_token);

        if (!existing) {
            selectedMap.set(normalized.lookup_token, normalized);
            renderFamilyBoard();
            return;
        }

        const merged = {
            ...existing,
            ...normalized,
            is_existing_child: !!existing.is_existing_child || !!normalized.is_existing_child,
        };

        if (merged.is_existing_child && (existing.id || normalized.id)) {
            merged.selection_source = 'existing';
            merged.id = existing.id || normalized.id;
        }

        selectedMap.set(normalized.lookup_token, merged);
        renderFamilyBoard();
    }

    existingChildren.forEach((item) => addSelected(item, true));

    function renderMenu(items) {
        if (!searchMenu) return;

        if (!Array.isArray(items) || items.length === 0) {
            searchMenu.innerHTML = '<div class="family-search-item" style="cursor:default;color:#667085">No ONT site found</div>';
            searchMenu.hidden = false;
            return;
        }

        searchMenu.innerHTML = '';
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'family-search-item';

            const familyMeta = item.merge_status === 'merged'
                ? 'Already in PettyCash'
                : 'New from ONT';
            const matchLabel = item.search_match_label
                ? String(item.search_match_label)
                : (item.merge_status_label ? String(item.merge_status_label) : 'Original ONT site');
            const matchName = item.search_match_name ? (' • ' + String(item.search_match_name)) : '';

            button.innerHTML = '' +
                '<div class="family-search-title">' +
                    '<span>' + escapeHtml(item.hostel_name || item.site_name || 'Hostel') + '</span>' +
                    '<span class="preview-pill">' + escapeHtml(familyMeta) + '</span>' +
                '</div>' +
                '<div class="family-search-meta">' +
                    '<span>' + escapeHtml(matchLabel + matchName) + '</span>' +
                    '<span>Site: ' + escapeHtml(item.site_id || item.ont_site_id || '-') + '</span>' +
                    '<span>S.N: ' + escapeHtml(item.site_sn || item.ont_site_sn || '-') + '</span>' +
                    '<span>Status: ' + escapeHtml(item.merge_status_label || 'ONT') + '</span>' +
                '</div>';

            button.addEventListener('click', () => {
                addSelected(item, false);
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                searchMenu.hidden = true;
                setStatus('ONT site added under this parent.', false);
            });

            searchMenu.appendChild(button);
        });

        searchMenu.hidden = false;
    }

    async function fetchHostels(url) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(String(payload.message || 'Search failed.'));
        }
        return Array.isArray(payload.hostels) ? payload.hostels : [];
    }

    async function hydratePreselected() {
        const missingIds = preselectedIds.filter((id) => !selectedMap.has(Number(id)));
        if (missingIds.length === 0 || !picker) return;

        try {
            const url = new URL(String(picker.dataset.searchUrl || ''), window.location.origin);
            url.searchParams.set('ids', missingIds.join(','));
            const items = await fetchHostels(url.toString());
            items.forEach((item) => addSelected(item, true));
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Unable to load selected hostels.', false);
        }
    }

    async function runSearch() {
        if (!picker || !searchInput || !searchMenu) return;
        const query = String(searchInput.value || '').trim();
        if (query.length < 2) {
            searchMenu.hidden = true;
            setStatus(query === '' ? '' : 'Type at least 2 characters.', false);
            return;
        }

        setStatus('Searching original ONT sites...', true);
        const url = new URL(String(picker.dataset.searchUrl || ''), window.location.origin);
        url.searchParams.set('q', query);
        url.searchParams.set('exclude', String(picker.dataset.exclude || ''));
        url.searchParams.set('limit', '20');

        try {
            const items = await fetchHostels(url.toString());
            renderMenu(items);
            setStatus(items.length > 0 ? 'Select ONT site to join.' : 'No ONT site found.', false);
        } catch (error) {
            searchMenu.hidden = true;
            setStatus(error && error.message ? error.message : 'Search failed.', false);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (searchTimer) window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(runSearch, 180);
        });

        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim().length >= 2) {
                runSearch();
            }
        });
    }

    document.addEventListener('click', (event) => {
        if (!picker || !searchMenu) return;
        if (!picker.contains(event.target)) {
            searchMenu.hidden = true;
        }
    });

    function renderErrors(errors) {
        if (!formErrors) return;
        formErrors.innerHTML = '';

        const messages = [];
        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach((key) => {
                const value = errors[key];
                if (Array.isArray(value)) {
                    value.forEach((message) => messages.push(String(message)));
                }
            });
        }

        if (messages.length === 0) {
            formErrors.hidden = true;
            return;
        }

        messages.forEach((message) => {
            const line = document.createElement('div');
            line.textContent = message;
            formErrors.appendChild(line);
        });

        formErrors.hidden = false;
    }

    async function submitJsonForm(event) {
        event.preventDefault();
        renderErrors(null);

        const submitButton = event.submitter || form.querySelector('[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                if (window.pettySetButtonLoadingState) {
                    window.pettySetButtonLoadingState(
                        submitButton,
                        window.pettyResolveLoadingLabel
                            ? window.pettyResolveLoadingLabel(submitButton, 'Saving Agreement...')
                            : (submitButton.getAttribute('data-loading-label') || 'Saving Agreement...')
                    );
                } else {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Saving...</span>';
                }
            }

        let saved = false;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));
            if (response.status === 422) {
                renderErrors(payload.errors || {});
                toast('error', 'Fix These Fields', payload.message || 'Some fields still need attention.');
                return;
            }

            if (!response.ok) {
                toast('error', 'Save Failed', payload.message || 'Unable to save the agreement right now.');
                return;
            }

            toast('success', form.dataset.successTitle || 'Saved', payload.message || 'Agreement saved.');
            saved = true;
            if (submitButton) {
                if (window.pettySetButtonSavedState) {
                    window.pettySetButtonSavedState(submitButton, 'Saved');
                } else {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saved';
                }
            }
            if (payload.redirect) {
                window.setTimeout(() => {
                    window.location.assign(payload.redirect);
                }, 220);
            }
        } catch (error) {
            toast('error', 'Network Error', error && error.message ? error.message : 'Unable to save right now.');
        } finally {
            if (submitButton && !saved) {
                if (window.pettyRestoreButtonState) {
                    window.pettyRestoreButtonState(submitButton);
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel || @json($agreementSubmitLabel);
                }
            }
            delete form.dataset.submitPending;
        }
    }

    form.addEventListener('submit', submitJsonForm);

    if (stakeInput) {
        stakeInput.addEventListener('change', updatePreviewMetrics);
    }
    if (amountInput) {
        amountInput.addEventListener('input', updatePreviewMetrics);
    }

    syncAgreementType();
    hydratePreselected();
    renderFamilyBoard();
})();
</script>
@endpush
