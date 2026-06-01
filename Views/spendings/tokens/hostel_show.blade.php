@extends('pettycash::layouts.app')

@section('title', $hostel->hostel_name . ' Payments')

@push('styles')
<style>
    .wrap{max-width:none;margin:0}
    .top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}
    .headline{display:grid;gap:6px}
    .title-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .action-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 22px rgba(16,24,40,.05);margin-top:12px}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .muted{color:#667085;font-size:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    @media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:700px){.summary-grid{grid-template-columns:1fr}}
    .err{background:#fef3f2;color:#b42318;border:1px solid #fecdca;padding:10px;border-radius:10px;margin-top:12px}
    .ont-smart{position:relative}
    .ont-select-native{display:none}
    .ont-smart-trigger{width:100%;display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;background:#fff;color:#101828;font-size:13px;cursor:pointer;text-align:left}
    .ont-smart-trigger:disabled{background:#f9fafb;color:#98a2b3;cursor:not-allowed}
    .ont-smart-menu{position:absolute;z-index:40;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #d0d5dd;border-radius:12px;box-shadow:0 16px 30px rgba(16,24,40,.12);overflow:hidden}
    .ont-smart-search{width:100%;border:none;border-bottom:1px solid #eaecf0;padding:10px 12px;font-size:13px;outline:none}
    .ont-smart-list{max-height:280px;overflow:auto}
    .ont-smart-item{width:100%;border:none;background:#fff;text-align:left;padding:10px 12px;font-size:13px;color:#101828;cursor:pointer}
    .ont-smart-item:hover,.ont-smart-item.active{background:#eef4ff}
    .ont-smart-empty{padding:10px 12px;color:#667085;font-size:13px}
    .ont-smart-item-title{font-weight:800;color:#101828}
    .ont-smart-item-meta{margin-top:4px;display:grid;grid-template-columns:repeat(3,minmax(0,max-content));align-items:center;gap:8px}
    .ont-smart-item-site{font-size:12px;color:#667085}
    .ont-smart-item-sn{font-size:12px;color:#667085}
    .ont-status-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.02em;border:1px solid transparent}
    .ont-status-chip.success{background:#ecfdf3;border-color:#abefc6;color:#027a48}
    .ont-status-chip.muted{background:#f2f4f7;border-color:#d0d5dd;color:#475467}
    .meta-card{border:1px solid #eaecf0;border-radius:12px;padding:10px;background:#fcfcfd}
    .meta-label{font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#667085}
    .meta-value{margin-top:4px;font-size:14px;font-weight:700;color:#101828}
    .ont-sync-note{margin-top:10px;padding:10px;border-radius:10px;border:1px solid #d0d5dd;background:#f8fafc;color:#344054;font-size:13px}
    .ont-sync-note.error{border-color:#fecdca;background:#fef3f2;color:#b42318}
    .pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:18px}
    .pc-modal.show{display:flex}
    .pc-modal-panel{width:min(900px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #e7e9f2;box-shadow:0 22px 50px rgba(16,24,40,.25)}
    .pc-modal[data-modal="payment-record"] .pc-modal-panel{width:min(1180px,calc(100vw - 32px))}
    .pc-modal[data-modal="payment-record"] .pc-modal-body{padding:18px}
    .pc-modal[data-modal="payment-record"] .pc-form{
        width:100%;
        grid-template-columns:repeat(12,minmax(0,1fr));
        align-items:start;
    }
    .pc-modal[data-modal="payment-record"] .pc-form > .payment-mode-panel,
    .pc-modal[data-modal="payment-record"] .pc-form > .err,
    .pc-modal[data-modal="payment-record"] .pc-form > .pc-field.full{
        grid-column:1 / -1;
        width:100%;
        min-width:0;
    }
    .pc-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #eaecf0}
    .pc-modal-body{padding:14px 16px}
    .pc-close{border:1px solid #d0d5dd;background:#fff;border-radius:10px;padding:6px 10px;font-weight:700;cursor:pointer}
    body.pc-modal-open{overflow:hidden}
    .action-menu{position:relative;display:inline-block}
    .action-menu > summary{list-style:none;cursor:pointer;user-select:none}
    .action-menu > summary::-webkit-details-marker{display:none}
    .action-menu-list{
        position:absolute;
        right:0;
        top:calc(100% + 6px);
        z-index:35;
        min-width:220px;
        background:#fff;
        border:1px solid #d0d5dd;
        border-radius:12px;
        box-shadow:0 14px 28px rgba(16,24,40,.14);
        padding:6px;
        display:grid;
        gap:4px;
    }
    .action-menu-item{
        width:100%;
        display:block;
        text-align:left;
        padding:8px 10px;
        border-radius:8px;
        border:none;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:13px;
        font-weight:700;
        cursor:pointer;
    }
    .action-menu-item:hover{background:#f2f4f7}
    .action-menu-item.is-disabled{color:#98a2b3;background:#f9fafb;cursor:not-allowed}
    .quick-actions-card{
        border:1px solid #e7e9f2;
        border-radius:14px;
        background:#fff;
        padding:16px;
        box-shadow:0 8px 22px rgba(16,24,40,.05);
        margin-top:12px;
    }
    .quick-actions-card h3{
        margin:0 0 10px;
        font-size:15px;
    }
    .quick-actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-top:10px;}
    .quick-action-btn{
        width:100%;
        min-height:45px;
        border-radius:12px;
        padding:12px 14px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        font-weight:700;
        font-size:13px;
        text-align:center;
        cursor:pointer;
        transition:.16s ease;
    }
    .quick-action-btn:hover{background:#f9fafb}
    .quick-action-btn.primary{background:#7f56d9;border-color:#7f56d9;color:#fff;}
    .quick-action-btn.danger{background:#f8f0f3;border-color:#f0d9e6;color:#a2205b;}
    .quick-action-btn.disabled{background:#f9fafb;color:#98a2b3;border-color:#eaecf0;cursor:not-allowed;}
    .quick-action-form{margin:0;}
    .pending-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
    .pending-form .pc-field{margin:0}
    .pending-form .pc-field.full{grid-column:1 / -1}
    .pending-status{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius:999px;
        padding:3px 9px;
        font-size:11px;
        font-weight:800;
        border:1px solid transparent;
    }
    .pending-status.pending{background:#fffaeb;border-color:#fedf89;color:#b54708}
    .pending-status.sorted{background:#ecfdf3;border-color:#abefc6;color:#027a48}
    .family-banner{
        margin-top:8px;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
    }
    .family-tag{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:5px 10px;
        border-radius:999px;
        border:1px solid #dbe3f4;
        background:#f8faff;
        color:#1849a9;
        font-size:11px;
        font-weight:900;
        letter-spacing:.04em;
        text-transform:uppercase;
    }
    .family-shell{
        display:grid;
        grid-template-columns:minmax(300px,.8fr) minmax(0,1.6fr);
        gap:14px;
        align-items:start;
    }
    .family-parent-panel{
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:linear-gradient(180deg,#fcfdff 0%,#f8fbff 100%);
        padding:16px;
        display:grid;
        gap:12px;
    }
    .family-parent-title{font-size:22px;font-weight:900;letter-spacing:-.03em;color:#101828}
    .family-parent-copy{font-size:13px;line-height:1.6;color:#667085}
    .family-parent-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }
    .family-parent-stat{
        border:1px solid #dbe3f4;
        border-radius:12px;
        background:#fff;
        padding:10px 12px;
    }
    .family-parent-label{
        font-size:10px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#98a2b3;
    }
    .family-parent-value{
        margin-top:4px;
        font-size:14px;
        font-weight:900;
        color:#101828;
    }
    .family-workspace{
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:#fff;
        overflow:hidden;
    }
    .family-workspace-head{
        padding:14px 16px;
        border-bottom:1px solid #eaecf0;
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        flex-wrap:wrap;
        background:#f8fafc;
    }
    .family-workspace-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .family-toolbar{
        padding:12px 16px;
        border-bottom:1px solid #eaecf0;
        display:flex;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        align-items:center;
        background:#fcfcfd;
    }
    .family-toolbar-search{
        min-width:min(320px,100%);
        flex:1 1 280px;
    }
    .family-toolbar-stats{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .family-stat-pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid #dbe3f4;
        background:#f8fbff;
        color:#1849a9;
        font-size:12px;
        font-weight:800;
    }
    .family-focus-panel{
        padding:14px 16px;
        border-bottom:1px solid #eaecf0;
        background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%);
        display:grid;
        gap:12px;
    }
    .family-focus-panel[hidden]{display:none !important}
    .family-focus-head{
        display:flex;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        align-items:flex-start;
    }
    .family-focus-title{
        font-size:16px;
        font-weight:900;
        color:#101828;
    }
    .family-focus-copy{
        margin-top:4px;
        font-size:12px;
        color:#667085;
    }
    .family-focus-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
    }
    .family-focus-stat{
        border:1px solid #dbe3f4;
        border-radius:12px;
        background:#fff;
        padding:10px 12px;
    }
    .family-focus-label{
        font-size:10px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#98a2b3;
    }
    .family-focus-value{
        margin-top:4px;
        font-size:13px;
        font-weight:800;
        color:#101828;
    }
    .family-focus-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .family-picked td{background:#eef6ff}
    .family-select-cell{width:42px}
    .family-select-cell input{width:16px;height:16px}
    .family-row-note{display:block;margin-top:4px;font-size:11px;color:#667085}
    .family-row-stack{display:grid;gap:4px}
    .family-row-meta{display:flex;flex-wrap:wrap;gap:6px}
    .family-chip{
        display:inline-flex;
        align-items:center;
        gap:4px;
        padding:4px 8px;
        border-radius:999px;
        border:1px solid #e4e7ec;
        background:#fff;
        color:#344054;
        font-size:11px;
        font-weight:800;
    }
    .family-edit-selection{
        border:1px solid #dbe3f4;
        border-radius:12px;
        background:#f8fbff;
        padding:10px 12px;
    }
    .family-edit-selection[hidden]{display:none !important}
    .focus-row td{background:#f8fbff}
    .family-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
        margin-top:12px;
    }
    .family-card{
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:linear-gradient(180deg,#fff 0%,#fcfdff 100%);
        padding:14px;
    }
    .family-card.is-focused{
        border-color:#b2ddff;
        box-shadow:0 0 0 4px rgba(43, 121, 255, .08);
        background:#f8fbff;
    }
    .family-card-title{
        display:flex;
        justify-content:space-between;
        gap:10px;
        align-items:flex-start;
        flex-wrap:wrap;
    }
    .family-card-name{font-size:14px;font-weight:900;color:#101828}
    .family-card-sub{margin-top:5px;font-size:12px;color:#667085;line-height:1.6}
    .family-card-meta{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:12px;
    }
    .family-card-label{
        font-size:10px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#98a2b3;
    }
    .family-card-value{
        margin-top:4px;
        font-size:12px;
        font-weight:800;
        color:#344054;
    }
    .payment-story{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
        margin-bottom:14px;
    }
    .payment-story-card{
        border:1px solid #eaecf0;
        border-radius:16px;
        background:#fcfcfd;
        padding:14px;
    }
    .payment-story-title{font-size:13px;font-weight:900;color:#101828}
    .payment-story-copy{margin-top:6px;font-size:12px;color:#667085;line-height:1.6}
    .payment-field[hidden]{display:none !important}
    .payment-summary{
        border:1px solid #dbe3f4;
        border-radius:16px;
        background:linear-gradient(180deg,#f8faff 0%,#fff 100%);
        padding:14px;
    }
    .payment-summary-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
        margin-top:12px;
    }
    .payment-summary-label{
        font-size:10px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#98a2b3;
    }
    .payment-summary-value{
        margin-top:4px;
        font-size:13px;
        font-weight:900;
        color:#101828;
    }
    .helper-sync-panel{
        border:1px solid #dbeafe;
        border-radius:16px;
        background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%);
        padding:18px;
        display:grid;
        gap:16px;
        width:100%;
        min-width:0;
    }
    .helper-sync-head{
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        flex-wrap:wrap;
    }
    .helper-sync-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:flex-end;
    }
    .helper-sync-table{
        width:100%;
        min-width:760px;
        border-collapse:collapse;
        margin-top:2px;
    }
    .helper-sync-table th,.helper-sync-table td{
        padding:10px;
        border-bottom:1px solid #e5e7eb;
        font-size:12px;
        vertical-align:top;
    }
    .helper-sync-table th{
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:.06em;
        color:#667085;
    }
    .helper-sync-radio{
        width:16px;
        height:16px;
        margin-top:2px;
    }
    .helper-sync-row.is-selected{
        background:#eef6ff;
    }
    .helper-sync-hint{
        padding:10px 12px;
        border-radius:12px;
        border:1px solid #dbeafe;
        background:#eff6ff;
        color:#1849a9;
        font-size:12px;
        line-height:1.6;
    }
    .helper-step-strip{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
        gap:12px;
    }
    .payment-mode-switch{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
        margin-bottom:2px;
        padding:12px;
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:#fcfcfd;
    }
    .payment-mode-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:40px;
        padding:10px 14px;
        border-radius:12px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        font-size:13px;
        font-weight:800;
        cursor:pointer;
    }
    .payment-mode-btn.is-active{
        background:#175cd3;
        border-color:#175cd3;
        color:#fff;
        box-shadow:0 0 0 3px rgba(23,92,211,.12);
    }
    .payment-mode-panel[hidden]{display:none !important}
    .payment-mode-panel{
        width:100%;
        min-width:0;
    }
    #manualEntryPanel{
        grid-column:1 / -1;
        display:grid;
        gap:16px;
        width:100%;
        min-width:0;
    }
    #manualEntryPanel .payment-story{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
        gap:12px;
        width:100%;
        min-width:0;
        margin-bottom:0;
    }
    #manualEntryPanel .pc-step-panel{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
        gap:14px;
        align-items:start;
        width:100%;
        min-width:0;
    }
    #manualEntryPanel .pc-step-panel > .pc-field{
        grid-column:auto;
        width:100%;
        min-width:0;
    }
    #manualEntryPanel .pc-step-panel > .pc-field.full,
    #manualEntryPanel .pc-step-panel > .pc-step-actions{
        grid-column:1 / -1;
    }
    #manualEntryPanel .pc-workflow{
        width:100%;
        min-width:0;
    }
    #manualEntryPanel .pc-step{
        width:100%;
        min-width:0;
    }
    .helper-step-card{
        border:1px solid #dbeafe;
        border-radius:14px;
        background:#fff;
        padding:12px;
        display:grid;
        gap:6px;
    }
    .helper-step-card.is-active{
        border-color:#7f56d9;
        box-shadow:0 0 0 3px rgba(127,86,217,.12);
    }
    .helper-step-index{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:26px;
        height:26px;
        border-radius:999px;
        background:#eef4ff;
        color:#175cd3;
        font-size:12px;
        font-weight:900;
    }
    .helper-step-name{
        font-size:12px;
        font-weight:900;
        color:#101828;
    }
    .helper-step-text{
        font-size:12px;
        line-height:1.5;
        color:#667085;
    }
    .helper-confirm-card{
        border:1px solid #dbeafe;
        border-radius:16px;
        background:#ffffff;
        padding:14px;
        display:grid;
        gap:12px;
    }
    .helper-confirm-card[hidden]{display:none !important}
    .helper-confirm-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
        gap:10px;
    }
    .helper-confirm-label{
        font-size:10px;
        font-weight:900;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#98a2b3;
    }
    .helper-confirm-value{
        margin-top:4px;
        font-size:13px;
        font-weight:900;
        color:#101828;
        word-break:break-word;
    }
    .helper-confirm-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
    }
    .helper-selected-state .helper-sync-table-wrap,
    .helper-selected-state .helper-sync-hint,
    .helper-selected-state .helper-empty-state{
        display:none;
    }
    .hostel-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 20px;
        align-items: start;
    }
    @media(max-width:900px){
        .family-shell,
        .pending-form,
        .family-grid,
        .payment-story,
        .payment-summary-grid,
        .helper-step-strip,
        .helper-confirm-grid,
        .family-card-meta,
        .family-focus-grid,
        .hostel-detail-grid{grid-template-columns:1fr}
        .pc-modal[data-modal="payment-record"] .pc-modal-panel{width:min(100%,calc(100vw - 20px))}
        .pc-modal[data-modal="payment-record"] .pc-modal-body{padding:14px}
        .helper-sync-head{display:grid}
        .helper-sync-actions{justify-content:flex-start}
        .helper-sync-table{min-width:680px}
        #manualEntryPanel .pc-step-panel{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment');
    $canEditHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel');
    $canEditPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.edit_payment');
    $canDeletePayment = \App\Modules\PettyCash\Support\PettyAccess::isAdmin(auth('petty')->user());
    $familyChildren = collect($familyChildren ?? []);
    $familyChildSummaries = collect($familyChildSummaries ?? []);
    $focusChildId = (int) ($focusChildId ?? 0);
    $hasFamilyChildren = $familyChildren->isNotEmpty();
    $agreementType = in_array(strtolower((string) ($agreementType ?? ($hostel->agreement_type ?? 'none'))), ['token', 'send_money', 'package', 'none'], true)
        ? strtolower((string) ($agreementType ?? ($hostel->agreement_type ?? 'none')))
        : 'none';
    $agreementTypeLabel = $agreementTypeLabel ?? match ($agreementType) {
        'token' => 'Token',
        'send_money' => 'Send Money',
        'package' => 'Package',
        default => 'No Agreement',
    };
    $agreementConfigured = $agreementType !== 'none'
        || trim((string) ($hostel->agreement_label ?? '')) !== '';
    $agreementActionLabel = $agreementConfigured ? 'Update Agreement' : 'Set Agreement';
    $isPackageAgreement = $agreementType === 'package';
    $isTokenAgreement = $agreementType === 'token';
    $supportsOverpay = (bool) ($supportsOverpay ?? false);
    $overpayCandidates = collect($overpayCandidates ?? []);
    $terminationSupported = (bool) ($terminationSupported ?? false);
    $agreementTerminated = (bool) ($agreementTerminated ?? false);
    $transferTargets = collect($transferTargets ?? []);
    $transferHostel = $transferHostel ?? null;
    $ontHostels = (array) ($ontCatalog['hostels'] ?? []);
    $ontAvailable = (bool) ($ontCatalog['available'] ?? false);
    $ontMessage = (string) ($ontCatalog['message'] ?? '');
    $modalRequest = strtolower((string) request('modal', ''));
    $oldContext = (string) old('form_context', '');
    $openModal = match ($oldContext) {
        'hostel_edit' => 'hostel-edit',
        'hostel_merge' => 'hostel-merge',
        'record_payment' => 'payment-record',
        'overpay_mark' => 'overpay-mark',
        'agreement_terminate' => 'agreement-terminate',
        default => in_array($modalRequest, ['hostel-edit', 'hostel-merge', 'payment-record', 'overpay-mark', 'agreement-terminate'], true) ? $modalRequest : '',
    };
    $pendingCredits = collect($pendingCredits ?? []);
    $pendingCreditOpen = $pendingCredits->filter(fn ($row) => strtolower((string) ($row->status ?? 'pending')) === 'pending')->values();
    $familySelectedOld = collect(old('selected_hostels', []))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values()
        ->all();
    $helperGatewayDevice = $helperGatewayDevice ?? null;
    $helperPaymentCandidates = collect($helperPaymentCandidates ?? []);
    $paymentEntryRequest = strtolower((string) request('payment_entry', ''));
    $initialPaymentEntryMode = match (true) {
        $paymentEntryRequest === 'helper' => 'helper',
        $paymentEntryRequest === 'manual' => 'manual',
        old('selected_sms_log_id') ? true : false => 'helper',
        default => 'manual',
    };
@endphp
<div class="wrap">
    <div class="top">
        <div class="headline">
            <div class="title-row">
                <h2 style="margin:0">{{ $hostel->hostel_name }}</h2>
                @if($hasFamilyChildren)
                    <span class="pill" style="background:#eef4ff;color:#1849a9;border:1px solid #b2ddff">Parent Hostel</span>
                @endif
                @if(!empty($hostel->chained_from_hostel_id))
                    <span class="pill" style="background:#f8fafc;color:#344054;border:1px solid #d0d5dd">Chained Hostel</span>
                @endif
                @if((bool) ($hostel->ont_merged ?? false))
                    <span class="pill" style="background:#ecfdf3;color:#027a48;border:1px solid #abefc6">Merged</span>
                @else
                    <span class="pill">Not Merged</span>
                @endif
            </div>
            <div class="muted">
                Agreement: <span class="pill">{{ $agreementTypeLabel }}</span>
                @if($agreementTerminated)
                    <span class="pill" style="background:#fef3f2;color:#b42318;border:1px solid #fecdca">Terminated</span>
                @endif
                @if($agreementType === 'token')
                    Meter: <span class="pill">{{ $hostel->meter_no ?? '-' }}</span>
                @endif
                Site S.N: <span class="pill">{{ $hostel->ont_site_sn ?? '-' }}</span>
                Name: <span class="pill">{{ $hostel->contact_person ?? '-' }}</span>
                Phone Number: <span class="pill">{{ $hostel->phone_no ?? '-' }}</span>
                Cycle: <span class="pill">{{ strtoupper($hostel->stake) }}</span>
                Due: <span class="pill">{{ number_format((float)$hostel->amount_due,2) }}</span>
                Routers: <span class="pill">{{ (int) ($familyRouterTotal ?? $hostel->no_of_routers ?? 0) }}</span>
                @if($chainedFromHostel)
                    Chained From: <span class="pill">{{ $chainedFromHostel->hostel_name }}</span>
                @endif
                @if($agreementType === 'package' && trim((string) ($hostel->agreement_label ?? '')) !== '')
                    Package: <span class="pill">{{ $hostel->agreement_label }}</span>
                @endif
            </div>
            @if($hasFamilyChildren)
                <div class="family-banner">
                    <span class="family-tag">Joined Hostels</span>
                    <span class="family-tag">{{ $familyChildren->count() }} hostel{{ $familyChildren->count() === 1 ? '' : 's' }}</span>
                    @if($focusChildId > 0)
                        @php
                            $focusedChild = $familyChildSummaries->firstWhere('id', $focusChildId);
                            $focusedChildName = is_array($focusedChild)
                                ? (string) ($focusedChild['hostel_name'] ?? '')
                                : (string) ($focusedChild->hostel_name ?? '');
                        @endphp
                        @if($focusedChildName !== '')
                            <span class="family-tag">Focused: {{ $focusedChildName }}</span>
                        @endif
                    @endif
                </div>
            @endif

            @if($agreementType !== 'none' && !$agreementTerminated && !$hasTransactions)
                <div class="quick-actions-card">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions-grid">
                        @if($canRecordPayment)
                            <button class="quick-action-btn primary" type="button" data-modal-target="payment-record">Record Payment</button>
                        @endif
                        @if($canEditHostel)
                            <form class="quick-action-form" method="POST" action="{{ route('petty.tokens.hostels.update', ['hostel' => $hostel->id]) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_due_immediately" value="{{ $hostel->is_due_immediately ? '0' : '1' }}">
                                <button type="submit" class="quick-action-btn">
                                    {{ $hostel->is_due_immediately ? 'Unmark Due Immediately' : 'Mark Due Immediately' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @if($lastPayment)
                <div class="muted" style="margin-top:6px">
                    Last payment: <span class="pill">{{ number_format((float)$lastPayment['amount'],2) }}</span>
                    <span class="pill">{{ $lastPayment['date'] }}</span>
                </div>
            @endif
            @if($agreementTerminated)
                <div class="muted" style="margin-top:6px">
                    Termination reason: <strong>{{ strtoupper(str_replace('_', ' ', (string) ($hostel->agreement_termination_reason ?? ''))) }}</strong>
                    @if($transferHostel)
                        • Transferred to: <strong>{{ $transferHostel->hostel_name }}</strong>
                    @endif
                </div>
            @endif
        </div>
        <div class="action-row">
            <a class="btn2" href="{{ route('petty.tokens.index') }}">Back</a>
            @if($canEditHostel)
                <a class="btn2" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $hostel->id, 'focus_step' => 'agreement-family']) }}">Manage Child Hostels</a>
            @endif
            <details class="action-menu">
                <summary class="btn2">Management ▾</summary>
                <div class="action-menu-list">
                    <a class="action-menu-item" href="{{ route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'pdf']) }}">Export PDF</a>
                    <a class="action-menu-item" href="{{ route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'csv']) }}">Export CSV</a>
                    <a class="action-menu-item" href="{{ route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'excel']) }}">Export Excel</a>
                    @if($canRecordPayment)
                        <button class="action-menu-item" type="button" data-modal-target="payment-record">Record Payment</button>
                    @endif
                    @if($canRecordPayment && $supportsOverpay && !$isPackageAgreement)
                        <button class="action-menu-item" type="button" data-modal-target="overpay-mark" @disabled($overpayCandidates->isEmpty())>Mark Overpay</button>
                    @endif
                    @if($canEditHostel)
                        <a class="action-menu-item" href="{{ route('petty.tokens.hostels.agreement', $hostel->id) }}">{{ $agreementActionLabel }}</a>
                        <button class="action-menu-item" type="button" data-modal-target="hostel-edit">{{ $hasFamilyChildren ? 'Edit Parent / Selected' : 'Edit Hostel' }}</button>
                        @if((bool) ($hostel->ont_merged ?? false))
                            <span class="action-menu-item is-disabled" aria-disabled="true">Merged</span>
                        @else
                            <button class="action-menu-item" type="button" data-modal-target="hostel-merge">Update from ONT</button>
                        @endif
                    @endif
                </div>
            </details>
        </div>
    </div>

    @if($errors->any() && $oldContext === '')
        <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    @if($hasFamilyChildren)
        <div class="card">
            <div class="family-shell">
                <div class="family-parent-panel">
                    <div>
                        <span class="family-tag">Parent Hostel</span>
                        <div class="family-parent-title" style="margin-top:10px">{{ $hostel->hostel_name }}</div>
                        <div class="family-parent-copy">This hostel is the main agreement home. All selected child hostels are managed from this page.</div>
                    </div>
                    <div class="family-parent-grid">
                        <div class="family-parent-stat">
                            <div class="family-parent-label">Agreement</div>
                            <div class="family-parent-value">{{ $agreementTypeLabel }}</div>
                        </div>
                        <div class="family-parent-stat">
                            <div class="family-parent-label">Child Hostels</div>
                            <div class="family-parent-value">{{ $familyChildren->count() }}</div>
                        </div>
                        <div class="family-parent-stat">
                            <div class="family-parent-label">Billing Cycle</div>
                            <div class="family-parent-value">{{ strtoupper($hostel->stake) }}</div>
                        </div>
                        <div class="family-parent-stat">
                            <div class="family-parent-label">Total Routers</div>
                            <div class="family-parent-value">{{ (int) ($familyRouterTotal ?? $hostel->no_of_routers ?? 0) }}</div>
                        </div>
                        <div class="family-parent-stat">
                            <div class="family-parent-label">Parent / Child Routers</div>
                            <div class="family-parent-value">{{ (int) ($hostel->no_of_routers ?? 0) }} / {{ (int) ($familyChildRouterTotal ?? 0) }}</div>
                        </div>
                        <div class="family-parent-stat">
                            <div class="family-parent-label">Due Amount</div>
                            <div class="family-parent-value">{{ number_format((float) $hostel->amount_due, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="family-workspace">
                    <div class="family-workspace-head">
                        <div>
                            <h3 style="margin:0">Child Hostels</h3>
                            <div class="muted" id="familySelectionStatus">Select child hostels to include in parent edit actions.</div>
                        </div>
                        <div class="family-workspace-actions">
                            <button class="btn2" type="button" id="familySelectAllBtn">Select All</button>
                            <button class="btn2" type="button" id="familyClearSelectionBtn">Clear</button>
                            <a class="btn2" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $hostel->id, 'focus_step' => 'agreement-family']) }}">Family Setup</a>
                            @if($canEditHostel)
                                <button class="btn" type="button" id="familyEditSelectedBtn">Edit Selected</button>
                            @endif
                        </div>
                    </div>

                    <div class="family-toolbar">
                        <div class="family-toolbar-search">
                            <input class="pc-input" type="text" id="familyChildSearchInput" placeholder="Search child hostel, site, contact, phone, or agreement">
                        </div>
                        <div class="family-toolbar-stats">
                            <span class="family-stat-pill" id="familyVisibleCountPill">{{ $familyChildren->count() }} visible</span>
                            <span class="family-stat-pill" id="familyVisibleRoutersPill">{{ (int) ($familyChildRouterTotal ?? 0) }} routers</span>
                        </div>
                    </div>

                    <div class="family-focus-panel" id="familyFocusPanel" @if($focusChildId <= 0) hidden @endif>
                        <div class="family-focus-head">
                            <div>
                                <div class="family-focus-title" id="familyFocusTitle">Focused Child Hostel</div>
                                <div class="family-focus-copy" id="familyFocusCopy">Use this workspace to inspect or edit one child hostel without leaving the parent page.</div>
                            </div>
                            <div class="family-focus-actions">
                                @if($canEditHostel)
                                    <button class="btn" type="button" id="familyFocusEditBtn">Edit This Child</button>
                                @endif
                                <a class="btn2" id="familyFocusLedgerLink" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}#transactions">Open Child Ledger</a>
                                <a class="btn2" id="familyFocusManageLink" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $hostel->id, 'focus_step' => 'agreement-family']) }}">Open Child Manager</a>
                            </div>
                        </div>
                        <div class="family-focus-grid">
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Hostel</div>
                                <div class="family-focus-value" id="familyFocusHostel">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Agreement</div>
                                <div class="family-focus-value" id="familyFocusAgreement">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Site / S.N</div>
                                <div class="family-focus-value" id="familyFocusSite">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Routers</div>
                                <div class="family-focus-value" id="familyFocusRouters">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Contact</div>
                                <div class="family-focus-value" id="familyFocusContact">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Phone</div>
                                <div class="family-focus-value" id="familyFocusPhone">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Cycle / Due</div>
                                <div class="family-focus-value" id="familyFocusCycleDue">-</div>
                            </div>
                            <div class="family-focus-stat">
                                <div class="family-focus-label">Last Payment</div>
                                <div class="family-focus-value" id="familyFocusLastPayment">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th class="family-select-cell">
                                    <input type="checkbox" id="familySelectAllCheckbox" aria-label="Select all child hostels">
                                </th>
                                <th>Hostel</th>
                                <th>Site</th>
                                <th>Site S.N</th>
                                <th>Routers</th>
                                <th>Meter Number</th>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Cycle</th>
                                <th>Due</th>
                                <th>Last Payment</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($familyChildSummaries as $familyChild)
                                @php
                                    $familyChildId = (int) ($familyChild['id'] ?? 0);
                                    $isFocusedChild = $focusChildId > 0 && $focusChildId === $familyChildId;
                                    $familyRouteId = (int) ($familyChild['route_hostel_id'] ?? $hostel->id);
                                    $familyAgreementLabel = match ((string) ($familyChild['agreement_type'] ?? 'none')) {
                                        'token' => 'Token',
                                        'send_money' => 'Send Money',
                                        'package' => 'Package',
                                        default => 'No Agreement',
                                    };
                                @endphp
                                <tr
                                    @class(['focus-row' => $isFocusedChild])
                                    data-family-manage-row
                                    data-hostel-id="{{ $familyChildId }}"
                                    data-search="{{ strtolower(trim(implode(' ', array_filter([
                                        (string) ($familyChild['hostel_name'] ?? ''),
                                        (string) ($familyChild['ont_site_id'] ?? ''),
                                        (string) ($familyChild['ont_site_sn'] ?? ''),
                                        (string) ($familyChild['contact_person'] ?? ''),
                                        (string) ($familyChild['phone_no'] ?? ''),
                                        (string) ($familyChild['agreement_label'] ?? ''),
                                        (string) ($familyChild['agreement_type'] ?? ''),
                                    ])))) }}"
                                    data-router-count="{{ (int) ($familyChild['no_of_routers'] ?? 0) }}"
                                    data-hostel-name="{{ $familyChild['hostel_name'] ?? ('Hostel #' . $familyChildId) }}"
                                    data-site-id="{{ $familyChild['ont_site_id'] ?? '' }}"
                                    data-site-sn="{{ $familyChild['ont_site_sn'] ?? '' }}"
                                    data-agreement="{{ trim(($familyAgreementLabel ?? '') . (!empty($familyChild['agreement_label']) ? (' • ' . $familyChild['agreement_label']) : '')) }}"
                                    data-contact="{{ $familyChild['contact_person'] ?? '' }}"
                                    data-phone="{{ $familyChild['phone_no'] ?? '' }}"
                                    data-cycle="{{ strtoupper((string) ($familyChild['stake'] ?? 'monthly')) }}"
                                    data-due="{{ number_format((float) ($familyChild['amount_due'] ?? 0), 2) }}"
                                    data-last-payment="{{ $familyChild['last_payment_date'] ?? '-' }}"
                                >
                                    <td class="family-select-cell">
                                        <input
                                            type="checkbox"
                                            data-family-select
                                            value="{{ $familyChildId }}"
                                            data-name="{{ $familyChild['hostel_name'] ?? ('Hostel #' . $familyChildId) }}"
                                            @checked(in_array($familyChildId, $familySelectedOld, true))
                                        >
                                    </td>
                                    <td>
                                        <div class="family-row-stack">
                                            <strong>{{ $familyChild['hostel_name'] ?? ('Hostel #' . $familyChildId) }}</strong>
                                            <div class="family-row-meta">
                                                <span class="family-chip">{{ $familyAgreementLabel }}</span>
                                                @if(!empty($familyChild['agreement_label']))
                                                    <span class="family-chip">{{ $familyChild['agreement_label'] }}</span>
                                                @endif
                                                @if($isFocusedChild)
                                                    <span class="family-chip" style="background:#eef6ff;border-color:#b2ddff;color:#1849a9">Focused</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $familyChild['ont_site_id'] ?: '-' }}</td>
                                    <td>{{ $familyChild['ont_site_sn'] ?: '-' }}</td>
                                    <td>{{ (int) ($familyChild['no_of_routers'] ?? 0) }}</td>
                                    <td>{{ $familyChild['meter_no'] ?: '-' }}</td>
                                    <td>{{ $familyChild['contact_person'] ?: '-' }}</td>
                                    <td>{{ $familyChild['phone_no'] ?: '-' }}</td>
                                    <td>{{ strtoupper((string) ($familyChild['stake'] ?? 'monthly')) }}</td>
                                    <td>{{ number_format((float) ($familyChild['amount_due'] ?? 0), 2) }}</td>
                                    <td>{{ $familyChild['last_payment_date'] ?: '-' }}</td>
                                    <td>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                                            <button class="btn2" type="button" data-family-focus-btn>
                                                {{ $isFocusedChild ? 'Focused' : 'Focus' }}
                                            </button>
                                            <a class="btn2" href="{{ route('petty.tokens.hostels.show', ['hostel' => $hostel->id, 'focus_child' => $familyChildId, 'child_ledger' => 1]) }}#transactions">Ledger</a>
                                            @if($canEditHostel)
                                                <button class="btn2" type="button" data-family-edit-one>Edit</button>
                                                <form method="POST" action="{{ route('petty.tokens.hostels.children.detach', ['hostel' => $hostel->id, 'child' => $familyChildId]) }}" data-confirm="Detach {{ $familyChild['hostel_name'] ?? ('Hostel #' . $familyChildId) }} from this parent?">
                                                    @csrf
                                                    <button class="btn2" type="submit" style="border-color:#fda29b;color:#b42318">Detach</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="hostel-detail-grid">
        <div class="card">
            <h3 style="margin:0 0 10px">{{ $hasFamilyChildren ? 'Parent Details' : 'Hostel Details' }}</h3>
            <div class="summary-grid">
                <div class="meta-card">
                    <div class="meta-label">Hostel</div>
                    <div class="meta-value">{{ $hostel->hostel_name }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Agreement</div>
                    <div class="meta-value">{{ $agreementTypeLabel }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Label</div>
                    <div class="meta-value">{{ $hostel->agreement_label ?: '-' }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Site S.N</div>
                    <div class="meta-value">{{ $hostel->ont_site_sn ?: '-' }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Site ID</div>
                    <div class="meta-value">{{ $hostel->ont_site_id ?: '-' }}</div>
                </div>
                @if($agreementType === 'token')
                    <div class="meta-card">
                        <div class="meta-label">Meter Number</div>
                        <div class="meta-value">{{ $hostel->meter_no ?: '-' }}</div>
                    </div>
                @endif
                <div class="meta-card">
                    <div class="meta-label">Name</div>
                    <div class="meta-value">{{ $hostel->contact_person ?: '-' }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Phone Number</div>
                    <div class="meta-value">{{ $hostel->phone_no ?: '-' }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Total Routers</div>
                    <div class="meta-value">{{ (int) ($familyRouterTotal ?? $hostel->no_of_routers ?? 0) }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Parent / Child Routers</div>
                    <div class="meta-value">{{ (int) ($hostel->no_of_routers ?? 0) }} / {{ (int) ($familyChildRouterTotal ?? 0) }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Billing Cycle</div>
                    <div class="meta-value">{{ strtoupper($hostel->stake) }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Due Amount</div>
                    <div class="meta-value">{{ number_format((float)$hostel->amount_due,2) }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Next Due</div>
                    <div class="meta-value">{{ $nextDue ? $nextDue->format('Y-m-d') : '-' }}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Status</div>
                    <div class="meta-value">{{ $dueBadge }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin:0 0 10px">M-Pesa QR Code</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div>
                        <div class="meta-label">Payment Type</div>
                        <div class="meta-value">{{ $qrType ?: 'Not generated' }}</div>
                    </div>
                    <div>
                        <div class="meta-label">Amount</div>
                        <div class="meta-value">KES {{ number_format($qrAmount, 2) }}</div>
                    </div>
                </div>
                <div style="display:grid;gap:10px;">
                    <div class="meta-card">
                        <div class="meta-label">Target</div>
                        <div class="meta-value">{{ $qrTarget ?: 'Not configured' }}</div>
                    </div>
                    <div class="meta-card">
                        <div class="meta-label">Reference</div>
                        <div class="meta-value">{{ $qrReference ?: '-' }}</div>
                    </div>
                    <div class="meta-card">
                        <div class="meta-label">Generated</div>
                        <div class="meta-value">{{ $qrGeneratedAt ? $qrGeneratedAt->format('Y-m-d H:i') : 'No QR generated' }}</div>
                    </div>
                </div>

                @if($qrImageUrl)
                    <div style="display:flex;justify-content:center;">
                        <img src="{{ $qrImageUrl }}" alt="M-Pesa QR code" style="max-width:100%;height:auto;border:1px solid #e5e7eb;padding:12px;background:#fff;" />
                    </div>
                @else
                    <div class="muted">Generate a QR code to allow customers to scan and pay this hostel directly.</div>
                @endif

                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    @if($canEditHostel)
                        <form method="POST" action="{{ route('petty.tokens.hostels.qr.generate', $hostel) }}">
                            @csrf
                            <button class="btn" type="submit" style="min-width:140px;">{{ $qrImageUrl ? 'Regenerate QR' : 'Generate QR' }}</button>
                        </form>
                    @endif
                    @if($qrImageUrl)
                        <a class="btn" href="{{ route('petty.tokens.hostels.qr.download', $hostel) }}">Download QR</a>
                    @endif
                </div>

                <div class="pc-help" style="margin-top:0;">
                    The QR is generated from the hostel agreement settings. If the payment target or amount changes, regenerate to refresh the image.
                </div>
            </div>
        </div>
    </div>

    @if($canEditHostel && $terminationSupported)
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
                <div>
                    <h3 style="margin:0">Agreement Status</h3>
                    <div class="muted" style="margin-top:6px">
                        @if($agreementTerminated)
                            This hostel currently has no active agreement. Use `{{ $agreementActionLabel }}` to set a new one again.
                        @else
                            Clear the active agreement only when this hostel should stop billing under the current setup. You can set a new agreement later.
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a class="btn2" href="{{ route('petty.tokens.hostels.agreement', $hostel->id) }}">{{ $agreementActionLabel }}</a>
                    @if(!$agreementTerminated)
                        <button class="btn2" type="button" data-modal-target="agreement-terminate" style="border-color:#fda29b;color:#b42318">Clear Agreement</button>
                    @else
                        <span class="btn2" style="opacity:.6;pointer-events:none">Agreement Cleared</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($isPackageAgreement && ($supportsPendingCredits ?? false))
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                <h3 style="margin:0">Pending Credits</h3>
                <div class="muted">
                    Open: <span class="pill">{{ $pendingCreditOpen->count() }}</span>
                </div>
            </div>

            @if($canRecordPayment)
                <form method="POST" action="{{ route('petty.tokens.hostels.pending_credits.store', ['hostel' => $hostel->id]) }}" class="pending-form">
                    @csrf
                    <div class="pc-field">
                        <label>Amount</label>
                        <input
                            class="pc-input"
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="amount"
                            value="{{ old('amount', (float) ($hostel->amount_due ?? 0) > 0 ? number_format((float) $hostel->amount_due, 2, '.', '') : '') }}"
                            placeholder="e.g. 1000.00"
                        >
                    </div>
                    <div class="pc-field">
                        <label>Reference (optional)</label>
                        <input class="pc-input" name="reference" value="{{ old('reference') }}" placeholder="e.g. package-invoice-22">
                    </div>
                    <div class="pc-field">
                        <label>Notes (optional)</label>
                        <input class="pc-input" name="notes" value="{{ old('notes') }}" placeholder="Add context for this pending credit">
                    </div>
                    <div class="pc-field full">
                        <button class="btn" type="submit" data-loading-label="Generating Pending Credit..." data-loading-copy="Preparing the pending credit for this hostel family.">Generate Pending Credit</button>
                    </div>
                </form>
            @endif

            @if($pendingCredits->isNotEmpty())
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            @if($hasFamilyChildren)
                                <th>Hostel</th>
                            @endif
                            <th>Created</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Sorted Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($pendingCredits as $credit)
                            @php
                                $isSortedCredit = strtolower((string) ($credit->status ?? 'pending')) === 'sorted';
                            @endphp
                            <tr>
                                @if($hasFamilyChildren)
                                    <td>{{ $credit->hostel?->hostel_name ?? $hostel->hostel_name }}</td>
                                @endif
                                <td>{{ optional($credit->created_at)->format('Y-m-d') ?: '-' }}</td>
                                <td>{{ $credit->reference ?: ('Pending #' . $credit->id) }}</td>
                                <td>{{ number_format((float) ($credit->amount ?? 0), 2) }}</td>
                                <td>
                                    <span class="pending-status {{ $isSortedCredit ? 'sorted' : 'pending' }}">
                                        {{ $isSortedCredit ? 'Sorted' : 'Pending' }}
                                    </span>
                                </td>
                                <td>{{ optional($credit->sorted_at)->format('Y-m-d') ?: '-' }}</td>
                                <td>
                                    @if($isSortedCredit)
                                        <span class="muted">Posted</span>
                                    @elseif($canRecordPayment)
                                        <form method="POST" action="{{ route('petty.tokens.hostels.pending_credits.sort', ['hostel' => $hostel->id, 'credit' => $credit->id]) }}" style="margin:0">
                                            @csrf
                                            <button class="btn2" type="submit" data-loading-label="Posting Pending Credit..." data-loading-copy="Posting the pending credit into transaction history.">Mark Sorted</button>
                                        </form>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="muted" style="margin-top:10px">No pending credits yet.</div>
            @endif
        </div>
    @endif

    <div class="card" id="transactions">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap">
            <div>
                <h3 style="margin:0 0 6px">
                    @if($showFocusedChildLedger && $focusedChildSummary)
                        Child Ledger: {{ $focusedChildSummary['hostel_name'] ?? ('Hostel #' . $focusChildId) }}
                    @else
                        Transactions
                    @endif
                </h3>
                @if($showFocusedChildLedger && $focusedChildSummary)
                    <div class="muted">Showing only transactions for the focused child hostel.</div>
                @endif
            </div>
            @if($showFocusedChildLedger)
                <a class="btn2" href="{{ route('petty.tokens.hostels.show', ['hostel' => $hostel->id, 'focus_child' => $focusChildId]) }}#transactions">Show Full Family Ledger</a>
            @endif
        </div>

        @forelse($paymentsByBatch as $batchId => $rows)
            @php
                $batchNo = $rows->first()?->batch?->batch_no ?? ($batchId ? ('Batch #'.$batchId) : 'No Batch');
                $sumAmt = (float) $rows->sum('amount');
                $sumFee = (float) $rows->sum('transaction_cost');
                $sumTotal = $sumAmt + $sumFee;
            @endphp

            <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                <div>
                    <strong>{{ $batchNo }}</strong>
                    <div class="muted">
                        Amount: <span class="pill">{{ number_format($sumAmt,2) }}</span>
                        Fees: <span class="pill">{{ number_format($sumFee,2) }}</span>
                        Total: <span class="pill">{{ number_format($sumTotal,2) }}</span>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Date</th>
                        @if($hasFamilyChildren)
                            <th>Hostel</th>
                        @endif
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Cost</th>
                        <th>Total</th>
                        <th>Name / Phone Number</th>
                        <th>Notes</th>
                        @if($canEditPayment || $canDeletePayment)
                            <th>Actions</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $p)
                        @php
                            $fee = (float)($p->transaction_cost ?? 0);
                            $amt = (float)$p->amount;
                            $isOverpayRow = $supportsOverpay && (bool) ($p->is_overpay_application ?? false);
                            $overpaySourceRef = trim((string) ($p->overpaySource?->reference ?? ''));
                            $overpaySourceDate = $p->overpaySource?->date?->format('Y-m-d');
                        @endphp
                        <tr>
                            <td>{{ $p->date?->format('Y-m-d') }}</td>
                            @if($hasFamilyChildren)
                                <td>{{ $p->hostel?->hostel_name ?? $hostel->hostel_name }}</td>
                            @endif
                            <td>
                                {{ $p->reference }}
                                @if($isOverpayRow)
                                    <div class="muted" style="margin-top:4px">
                                        <span class="pill" style="background:#ecfdf3;color:#027a48;border:1px solid #abefc6">Overpay Applied</span>
                                    </div>
                                @endif
                            </td>
                            <td>{{ number_format($amt, 2) }}</td>
                            <td>{{ number_format($fee, 2) }}</td>
                            <td><strong>{{ number_format($amt + $fee, 2) }}</strong></td>
                            <td>{{ $p->receiver_name }} {{ $p->receiver_phone ? '('.$p->receiver_phone.')' : '' }}</td>
                            <td>
                                @if($isOverpayRow)
                                    <div class="muted" style="margin-bottom:4px">
                                        Source:
                                        @if($overpaySourceRef !== '')
                                            {{ $overpaySourceRef }}
                                        @else
                                            Payment #{{ (int) ($p->overpay_source_payment_id ?? 0) }}
                                        @endif
                                        @if($overpaySourceDate)
                                            ({{ $overpaySourceDate }})
                                        @endif
                                    </div>
                                @endif
                                {{ $p->notes }}
                            </td>
                            @if($canEditPayment || $canDeletePayment)
                                <td>
                                    @if($canEditPayment)
                                        <a href="{{ route('petty.tokens.payments.edit', $p->id) }}">Edit</a>
                                    @endif
                                    @if($canDeletePayment)
                                        <form method="POST" action="{{ route('petty.tokens.payments.destroy', $p->id) }}" style="display:inline-block;margin-left:8px" data-confirm="Delete this token payment?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="border:none;background:none;color:#b42318;cursor:pointer;padding:0">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="muted">No transactions yet.</div>
        @endforelse
    </div>

    @if($canEditHostel)
        <div class="pc-modal" data-modal="hostel-edit" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Edit hostel details">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Edit Hostel Details</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'hostel_edit')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif
                    @php
                        $editSelectedOntKey = old('form_context') === 'hostel_edit'
                            ? (string) old('ont_key', $selectedOntKey)
                            : (string) $selectedOntKey;
                    @endphp
                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.update', $hostel->id) }}" id="hostelEditForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_context" value="hostel_edit">
                        @php
                            $editScope = old('edit_scope', 'parent_only');
                        @endphp

                        <div class="pc-workflow" data-workflow data-workflow-unlock-all="1" data-workflow-open-none="1">
                            <section class="pc-step" data-step="hostel-edit-scope" data-step-unlocked="1">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">1</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Edit scope</span>
                                            <span class="pc-step-text">Choose whether this update stays on the parent or also syncs to selected child hostels.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Ready</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body hidden>
                                    <div class="pc-step-panel">
                                        @if($hasFamilyChildren)
                                            <div class="pc-field full">
                                                <label>Edit Scope</label>
                                                <select class="pc-select" name="edit_scope" id="familyEditScope">
                                                    <option value="parent_only" @selected($editScope === 'parent_only')>Parent hostel only</option>
                                                    <option value="parent_and_selected" @selected($editScope === 'parent_and_selected')>Parent hostel + selected child hostels</option>
                                                </select>
                                                <div class="pc-help">Shared fields can also sync to selected child hostels from this page.</div>
                                            </div>

                                            <div class="pc-field full">
                                                <div class="family-edit-selection" id="familyEditSelectionWrap" @if($editScope !== 'parent_and_selected') hidden @endif>
                                                    <label style="display:block;margin-bottom:6px">Selected Child Hostels</label>
                                                    <input class="pc-input" id="familyEditSelectionPreview" type="text" value="" readonly>
                                                    <div class="pc-help">Select child hostels from the parent page before saving. Shared updates affect Name, Phone Number, Billing Cycle, and Due Amount.</div>
                                                    <div id="familyEditSelectionInputs"></div>
                                                </div>
                                            </div>
                                        @else
                                            <input type="hidden" name="edit_scope" value="parent_only">
                                            <div class="pc-field full">
                                                <div class="ont-sync-note">
                                                    This hostel updates on its own because there are no joined child hostels under it yet.
                                                </div>
                                            </div>
                                        @endif

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-note">Existing hostels can open any section directly from this editor.</div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="pc-step" data-step="hostel-edit-ont" data-step-unlocked="1">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">2</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">ONT site</span>
                                            <span class="pc-step-text">Keep the hostel tied to the right ONT record before saving the profile fields.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Ready</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body hidden>
                                    <div class="pc-step-panel">
                                        <div class="pc-field full">
                                            <label>ONT / Site</label>
                                            <select class="pc-select ont-select ont-select-native" name="ont_key" data-target-prefix="edit" @if(!$ontAvailable) disabled @endif required>
                                                <option value="">Select ONT site</option>
                                                @foreach($ontHostels as $candidate)
                                                    @php
                                                        $optKey = (string) ($candidate['key'] ?? '');
                                                        $optName = (string) ($candidate['hostel_name'] ?? '');
                                                        $optSiteId = (string) ($candidate['site_id'] ?? '');
                                                        $optSiteSn = (string) ($candidate['site_sn'] ?? '');
                                                        $optMergeStatus = (string) ($candidate['merge_status'] ?? 'unlinked');
                                                        $optMergeLabel = (string) ($candidate['merge_status_label'] ?? 'Not Added');
                                                        $optMergeTone = (string) ($candidate['merge_status_tone'] ?? 'muted');
                                                    @endphp
                                                    <option
                                                        value="{{ $optKey }}"
                                                        data-name="{{ $optName }}"
                                                        data-site-id="{{ $optSiteId }}"
                                                        data-site-sn="{{ $optSiteSn }}"
                                                        data-merge-status="{{ $optMergeStatus }}"
                                                        data-merge-status-label="{{ $optMergeLabel }}"
                                                        data-merge-status-tone="{{ $optMergeTone }}"
                                                        @selected($editSelectedOntKey === $optKey)
                                                    >
                                                        {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="ont-smart" data-target-prefix="edit" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'ont_catalog'], false) }}"></div>
                                            <input type="hidden" name="hostel_name" class="hostel-name-hidden" data-target-prefix="edit" value="{{ old('hostel_name', $hostel->hostel_name) }}">
                                        </div>

                                        <div class="pc-field full">
                                            <div class="ont-sync-note @if(!$ontAvailable) error @endif">
                                                @if($ontAvailable)
                                                    Selected ONT name will be saved as hostel name.
                                                @else
                                                    {{ $ontMessage !== '' ? $ontMessage : 'ONT directory unavailable.' }}
                                                @endif
                                                <div style="margin-top:6px;font-weight:700">
                                                    <span class="selected-hostel-preview" data-target-prefix="edit">-</span>
                                                    <span style="margin-left:8px" class="selected-site-preview" data-target-prefix="edit"></span>
                                                    <span style="margin-left:8px" class="selected-sn-preview" data-target-prefix="edit"></span>
                                                    <span style="margin-left:8px" class="ont-status-chip muted selected-status-preview" data-target-prefix="edit">Not Added</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-note">Change the ONT site only when the current site mapping is wrong.</div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="pc-step" data-step="hostel-edit-details" data-step-unlocked="1">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">3</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Hostel details</span>
                                            <span class="pc-step-text">Update the agreement-matching profile fields for this hostel.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Ready</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body hidden>
                                    <div class="pc-step-panel">
                                        @if($agreementType === 'token')
                                            <div class="pc-field">
                                                <label>Meter Number</label>
                                                <input class="pc-input" name="meter_no" required value="{{ old('meter_no', $hostel->meter_no) }}">
                                            </div>
                                        @else
                                            <input type="hidden" name="meter_no" value="">
                                        @endif

                                        <div class="pc-field">
                                            <label>Name</label>
                                            <input class="pc-input" name="contact_person" value="{{ old('contact_person', $hostel->contact_person) }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Phone Number</label>
                                            <input class="pc-input" name="phone_no" value="{{ old('phone_no', $hostel->phone_no) }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>No of Routers</label>
                                            <input class="pc-input" type="number" min="0" name="no_of_routers" value="{{ old('no_of_routers', $hostel->no_of_routers) }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Billing Cycle</label>
                                            <select class="pc-select" name="stake" required>
                                                <option value="monthly" @selected(old('stake', $hostel->stake) === 'monthly')>Monthly</option>
                                                <option value="semester" @selected(old('stake', $hostel->stake) === 'semester')>Semester</option>
                                            </select>
                                        </div>

                                        <div class="pc-field">
                                            <label>Due Amount</label>
                                            <input class="pc-input" type="number" step="0.01" min="0" name="amount_due" required value="{{ old('amount_due', $hostel->amount_due) }}">
                                        </div>

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-main">
                                                <button class="btn" type="submit" data-loading-label="Updating Hostel Details..." data-loading-copy="Applying the edited details to this hostel." @disabled(!$ontAvailable)>Save Hostel Details</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canEditHostel && !(bool) ($hostel->ont_merged ?? false))
        @php
            $mergeSelectedOntKey = old('form_context') === 'hostel_merge'
                ? (string) old('ont_key', $selectedOntKey)
                : (string) $selectedOntKey;
        @endphp
        <div class="pc-modal" data-modal="hostel-merge" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Merge hostel with ONT">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Merge / Update from ONT</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'hostel_merge')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif
                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.merge_ont', $hostel->id) }}">
                        @csrf
                        <input type="hidden" name="form_context" value="hostel_merge">

                        <div class="pc-workflow" data-workflow>
                            <section class="pc-step" data-step="hostel-merge-ont" data-step-open="1" data-step-unlocked="1">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">1</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Choose the ONT site</span>
                                            <span class="pc-step-text">Pick the live ONT record that should take over this hostel profile.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Open</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body>
                                    <div class="pc-step-panel">
                                        <div class="pc-field full">
                                            <label>ONT Site</label>
                                            <select class="pc-select ont-select ont-select-native" name="ont_key" data-target-prefix="merge" @if(!$ontAvailable) disabled @endif required>
                                                <option value="">Select ONT site</option>
                                                @foreach($ontHostels as $candidate)
                                                    @php
                                                        $optKey = (string) ($candidate['key'] ?? '');
                                                        $optName = (string) ($candidate['hostel_name'] ?? '');
                                                        $optSiteId = (string) ($candidate['site_id'] ?? '');
                                                        $optSiteSn = (string) ($candidate['site_sn'] ?? '');
                                                        $optMergeStatus = (string) ($candidate['merge_status'] ?? 'unlinked');
                                                        $optMergeLabel = (string) ($candidate['merge_status_label'] ?? 'Not Added');
                                                        $optMergeTone = (string) ($candidate['merge_status_tone'] ?? 'muted');
                                                    @endphp
                                                    <option
                                                        value="{{ $optKey }}"
                                                        data-name="{{ $optName }}"
                                                        data-site-id="{{ $optSiteId }}"
                                                        data-site-sn="{{ $optSiteSn }}"
                                                        data-merge-status="{{ $optMergeStatus }}"
                                                        data-merge-status-label="{{ $optMergeLabel }}"
                                                        data-merge-status-tone="{{ $optMergeTone }}"
                                                        @selected($mergeSelectedOntKey === $optKey)
                                                    >
                                                        {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="ont-smart" data-target-prefix="merge" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'ont_catalog'], false) }}"></div>
                                            <input type="hidden" name="hostel_name" class="hostel-name-hidden" data-target-prefix="merge" value="{{ old('hostel_name', $hostel->hostel_name) }}">
                                        </div>

                                        <div class="pc-field full">
                                            <div class="ont-sync-note @if(!$ontAvailable) error @endif">
                                                @if($ontAvailable)
                                                    This process updates hostel name to match ONT data.
                                                @else
                                                    {{ $ontMessage !== '' ? $ontMessage : 'ONT directory unavailable.' }}
                                                @endif
                                                <div style="margin-top:6px;font-weight:700">
                                                    <span class="selected-hostel-preview" data-target-prefix="merge">-</span>
                                                    <span style="margin-left:8px" class="selected-site-preview" data-target-prefix="merge"></span>
                                                    <span style="margin-left:8px" class="selected-sn-preview" data-target-prefix="merge"></span>
                                                    <span style="margin-left:8px" class="ont-status-chip muted selected-status-preview" data-target-prefix="merge">Not Added</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-main">
                                                <button class="btn2" type="button" data-step-next @disabled(!$ontAvailable)>Proceed to Merge Details</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="pc-step" data-step="hostel-merge-details">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">2</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Merge details</span>
                                            <span class="pc-step-text">Confirm the remaining hostel details before the ONT merge is applied.</span>
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
                                            <label>No of Routers</label>
                                            <input class="pc-input" type="number" min="0" name="no_of_routers" value="{{ old('form_context') === 'hostel_merge' ? old('no_of_routers', $hostel->no_of_routers) : $hostel->no_of_routers }}">
                                        </div>

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-main">
                                                <button class="btn" type="submit" data-loading-label="Merging Hostel..." data-loading-copy="Updating this hostel from the selected ONT record." @disabled(!$ontAvailable)>Merge ONT into Hostel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canEditHostel && $terminationSupported)
        <div class="pc-modal" data-modal="agreement-terminate" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Clear active agreement">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Clear Active Agreement</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'agreement_terminate')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif

                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.agreement.terminate', $hostel->id) }}" data-confirm="Clear the active agreement for {{ $hostel->hostel_name }}? You can set a new agreement again later.">
                        @csrf
                        <input type="hidden" name="form_context" value="agreement_terminate">

                        <div class="pc-field full">
                            <label>Reason (required)</label>
                            <select class="pc-select" name="termination_reason" id="agreementTerminateReason" required>
                                <option value="">Select reason</option>
                                <option value="transfer_agreement" @selected(old('termination_reason') === 'transfer_agreement')>Transfer agreement</option>
                                <option value="closed" @selected(old('termination_reason') === 'closed')>Closed / Not active</option>
                                <option value="moved" @selected(old('termination_reason') === 'moved')>Moved / Relocated</option>
                                <option value="other" @selected(old('termination_reason') === 'other')>Other</option>
                            </select>
                            <div class="pc-help">This clears the active agreement from this hostel. You can set a new agreement again later.</div>
                        </div>

                        <div class="pc-field full" id="agreementTransferWrap" style="display:none;">
                            <label>Transfer To Hostel / Payment Channel</label>
                            <select class="pc-select" name="transfer_hostel_id" id="agreementTransferHostel">
                                <option value="">Select hostel</option>
                                @foreach($transferTargets as $target)
                                    <option value="{{ $target->id }}" @selected((string) old('transfer_hostel_id') === (string) $target->id)>
                                        {{ $target->hostel_name }} @if($target->meter_no || $target->phone_no) ({{ $target->meter_no ?: '-' }} / {{ $target->phone_no ?: '-' }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="pc-help">Required when reason is transfer agreement.</div>
                        </div>

                        <div class="pc-field full">
                            <label>Notes (optional)</label>
                            <input class="pc-input" name="termination_notes" value="{{ old('termination_notes') }}" placeholder="Context for this termination">
                        </div>

                        <div class="pc-actions">
                            <button class="btn" type="submit" data-loading-label="Clearing Agreement..." data-loading-copy="Removing the active agreement from this hostel.">Clear Agreement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canRecordPayment)
        @if($supportsOverpay && !$isPackageAgreement)
            <div class="pc-modal" data-modal="overpay-mark" aria-hidden="true">
                <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Mark overpay">
                    <div class="pc-modal-head">
                        <h3 style="margin:0">Mark Overpay</h3>
                        <button type="button" class="pc-close" data-modal-close>Close</button>
                    </div>
                    <div class="pc-modal-body">
                        @if($errors->any() && old('form_context') === 'overpay_mark')
                            <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                        @endif

                        <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.overpay.apply', $hostel->id) }}">
                            @csrf
                            <input type="hidden" name="form_context" value="overpay_mark">

                            <div class="pc-field full">
                            <label>Source Record</label>
                                <select class="pc-select" name="source_payment_id" id="overpaySourcePayment" required @disabled($overpayCandidates->isEmpty())>
                                    <option value="">Choose payment record</option>
                                    @foreach($overpayCandidates as $candidate)
                                        @php
                                            $candidateAmount = (float) ($candidate->amount ?? 0);
                                            $candidateDateValue = optional($candidate->date)->format('Y-m-d') ?: '';
                                            $candidateDate = $candidateDateValue !== '' ? $candidateDateValue : '-';
                                            $candidateRef = (string) ($candidate->reference ?? ('Payment #' . $candidate->id));
                                        @endphp
                                        <option
                                            value="{{ $candidate->id }}"
                                            @selected((string) old('source_payment_id') === (string) $candidate->id)
                                        >
                                            {{ $candidateDate }} • {{ $candidateRef }} • {{ number_format($candidateAmount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pc-help">Creates a non-deducting payment entry using the selected source record.</div>
                            </div>

                            <div class="pc-field">
                                <label>Marked Date</label>
                                <input
                                    class="pc-input"
                                    type="date"
                                    name="marked_date"
                                    id="overpayMarkedDate"
                                    required
                                    value="{{ old('marked_date', optional($today)->format('Y-m-d') ?: date('Y-m-d')) }}"
                                >
                                <div class="pc-help">Default mode starts a new cycle from this date (one month from today if marked today).</div>
                            </div>

                            <div class="pc-field">
                                <label>Date Handling</label>
                                <select class="pc-select" name="date_mode" id="overpayDateMode">
                                    <option value="from_marked_date" @selected(old('date_mode', 'from_marked_date') === 'from_marked_date')>
                                        Reset from marked date (Default)
                                    </option>
                                    <option value="next_billing_date" @selected(old('date_mode') === 'next_billing_date')>
                                        Choose next billing date
                                    </option>
                                </select>
                                <div class="pc-help">Switch to next billing date only when you need a custom due date.</div>
                            </div>

                            <div class="pc-field full" id="overpayNextBillingWrap" style="display:none;">
                                <label>Next Billing Date</label>
                                <input
                                    class="pc-input"
                                    type="date"
                                    name="next_billing_date"
                                    id="overpayNextBillingDate"
                                    value="{{ old('next_billing_date') }}"
                                >
                                <div class="pc-help">Used only when date handling is set to "Choose next billing date".</div>
                            </div>

                            <div class="pc-field full">
                                <div class="pc-help" id="overpayNextDuePreview">Next due will be: YYYY-MM-DD</div>
                            </div>

                            <div class="pc-field full">
                                <label>Notes (optional)</label>
                                <input class="pc-input" name="notes" value="{{ old('notes') }}" placeholder="Context for this overpay adjustment">
                            </div>

                            @if($overpayCandidates->isEmpty())
                                <div class="ont-sync-note error">
                                    No eligible overpay source records found. Record payments first, then apply overpay from one unused record.
                                </div>
                            @endif

                            <div class="pc-actions">
                                <button class="btn" type="submit" data-loading-label="Applying Overpay..." data-loading-copy="Creating a non-deducting overpay entry." @disabled($overpayCandidates->isEmpty())>Apply Overpay (No Deduction)</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="pc-modal" data-modal="payment-record" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Record payment">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Record Payment</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'record_payment')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif
                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.payments.store', $hostel->id) }}" id="paymentRecordForm" data-json-submit="1" data-success-title="Payment Recorded">
                        @csrf
                        <input type="hidden" name="form_context" value="record_payment">
                        <input type="hidden" name="selected_sms_log_id" id="selectedSmsLogId" value="{{ old('selected_sms_log_id') }}">
                        <div class="err full" data-form-errors @if(!( $errors->any() && old('form_context') === 'record_payment')) hidden @endif>
                            @if($errors->any() && old('form_context') === 'record_payment')
                                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                            @endif
                        </div>

                        <div class="pc-field full">
                            <div class="payment-mode-switch">
                                <button class="payment-mode-btn" type="button" id="manualEntryModeBtn">Manual Entry</button>
                                <button class="payment-mode-btn" type="button" id="helperEntryModeBtn">Fetch From Helper</button>
                                <span class="muted">Use one path at a time. Switching back to manual clears the helper selection.</span>
                            </div>
                        </div>

                        <div class="pc-field full payment-mode-panel" id="helperEntryPanel">
                            <div class="helper-sync-panel" id="helperSyncPanel">
                                <div class="helper-step-strip">
                                    <div class="helper-step-card is-active" id="helperStepPickCard">
                                        <span class="helper-step-index">1</span>
                                        <div class="helper-step-name">Select Helper Payment</div>
                                        <div class="helper-step-text">Fetch the helper inbox result, then choose the exact M-PESA message you want to use.</div>
                                    </div>
                                    <div class="helper-step-card" id="helperStepConfirmCard">
                                        <span class="helper-step-index">2</span>
                                        <div class="helper-step-name">Confirm Imported Details</div>
                                        <div class="helper-step-text">Review the imported transaction code, amount, cost, and target before posting.</div>
                                    </div>
                                    <div class="helper-step-card" id="helperStepPostCard">
                                        <span class="helper-step-index">3</span>
                                        <div class="helper-step-name">Record As Payment</div>
                                        <div class="helper-step-text">Post the selected helper transaction straight into the hostel ledger.</div>
                                    </div>
                                </div>
                                <div class="helper-sync-head">
                                    <div>
                                        <div class="payment-story-title">Fetch From Helper</div>
                                        <div class="payment-story-copy">
                                            Pull uploaded M-PESA messages from the helper flow, then select the relevant one to auto-fill the payment record. You only verify and save.
                                        </div>
                                    </div>
                                    <div class="helper-sync-actions">
                                        <span class="pill">{{ $helperGatewayDevice ? ('Active: ' . $helperGatewayDevice->name) : 'No active helper' }}</span>
                                        <button
                                            class="btn2"
                                            type="submit"
                                            formaction="{{ route('petty.tokens.payments.helper_sync', $hostel->id) }}"
                                            formmethod="POST"
                                            data-skip-json-submit="1"
                                            formnovalidate
                                            data-loading-label="Requesting Helper Sync..."
                                            data-loading-copy="Asking the helper phone to scan inbox SMS and upload missing M-PESA messages."
                                        >Fetch From Helper</button>
                                    </div>
                                </div>

                                <div class="helper-sync-hint">
                                    The helper now watches for older M-PESA inbox messages too. If you paid earlier and are recording later, request a helper sync, then pick the matching transaction below.
                                </div>

                                @if($helperPaymentCandidates->isEmpty())
                                    <div class="muted helper-empty-state">No unrecorded M-PESA helper messages have been found for this meter yet.</div>
                                @else
                                    <div class="helper-sync-table-wrap" style="overflow:auto">
                                        <table class="helper-sync-table">
                                            <thead>
                                            <tr>
                                                <th></th>
                                                <th>Transaction</th>
                                                <th>Amount</th>
                                                <th>Cost</th>
                                                <th>Date</th>
                                                <th>Meter</th>
                                                <th>Sender</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($helperPaymentCandidates as $candidateLog)
                                                @php
                                                    $candidateDate = $candidateLog->sms_received_at?->format('Y-m-d') ?: '';
                                                    $candidateChecked = (string) old('selected_sms_log_id') === (string) $candidateLog->id;
                                                @endphp
                                                <tr class="helper-sync-row @if($candidateChecked) is-selected @endif" data-helper-candidate-row>
                                                    <td>
                                                        <input
                                                            class="helper-sync-radio"
                                                            type="radio"
                                                            name="selected_sms_log_id_picker"
                                                            value="{{ $candidateLog->id }}"
                                                            data-helper-candidate
                                                            data-log-id="{{ $candidateLog->id }}"
                                                            data-reference="{{ $candidateLog->parsed_reference }}"
                                                            data-amount="{{ number_format((float) ($candidateLog->parsed_amount ?? 0), 2, '.', '') }}"
                                                            data-cost="{{ number_format((float) ($candidateLog->parsed_transaction_cost ?? 0), 2, '.', '') }}"
                                                            data-date="{{ $candidateDate }}"
                                                            data-meter="{{ $candidateLog->parsed_meter_number }}"
                                                            @checked($candidateChecked)
                                                        >
                                                    </td>
                                                    <td>
                                                        <strong>{{ $candidateLog->parsed_reference }}</strong>
                                                        <div class="muted">{{ \Illuminate\Support\Str::limit((string) $candidateLog->sms_body, 96) }}</div>
                                                        <div style="margin-top:8px">
                                                            <button class="btn2" type="button" data-helper-pick="{{ $candidateLog->id }}">Use This Payment</button>
                                                        </div>
                                                    </td>
                                                    <td>KES {{ number_format((float) ($candidateLog->parsed_amount ?? 0), 2) }}</td>
                                                    <td>KES {{ number_format((float) ($candidateLog->parsed_transaction_cost ?? 0), 2) }}</td>
                                                    <td>{{ $candidateLog->sms_received_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                                    <td>{{ $candidateLog->parsed_meter_number ?: '-' }}</td>
                                                    <td>{{ $candidateLog->sender ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <div class="helper-confirm-card" id="helperConfirmCard" hidden>
                                    <div>
                                        <div class="payment-story-title">Step 2: Confirm Selected Helper Payment</div>
                                        <div class="payment-story-copy">The manual form is now out of the way. Review this imported M-PESA transaction, then post it directly into the hostel ledger.</div>
                                    </div>
                                    <div class="helper-confirm-grid">
                                        <div>
                                            <div class="helper-confirm-label">Transaction</div>
                                            <div class="helper-confirm-value" id="helperConfirmReference">-</div>
                                        </div>
                                        <div>
                                            <div class="helper-confirm-label">Amount</div>
                                            <div class="helper-confirm-value" id="helperConfirmAmount">-</div>
                                        </div>
                                        <div>
                                            <div class="helper-confirm-label">Cost</div>
                                            <div class="helper-confirm-value" id="helperConfirmCost">-</div>
                                        </div>
                                        <div>
                                            <div class="helper-confirm-label">Date</div>
                                            <div class="helper-confirm-value" id="helperConfirmDate">-</div>
                                        </div>
                                        <div>
                                            <div class="helper-confirm-label">Target</div>
                                            <div class="helper-confirm-value" id="helperConfirmTarget">-</div>
                                        </div>
                                    </div>
                                    <div class="helper-confirm-actions">
                                        <button class="btn" type="submit" id="helperRecordSubmit" data-loading-label="Recording Helper Payment..." data-loading-copy="Saving the selected helper M-PESA payment into the hostel ledger.">Step 3: Record Selected Helper Payment</button>
                                        <button class="btn2" type="button" id="helperBackToManualBtn">Back To Manual Entry</button>
                                        <span class="muted">This uses the imported helper SMS values and saves the ledger entry immediately.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-mode-panel" id="manualEntryPanel">
                        <div class="pc-field full">
                            <div class="payment-story">
                                <div class="payment-story-card">
                                    <div class="payment-story-title" id="paymentFlowTitle">Agreement payment flow</div>
                                    <div class="payment-story-copy" id="paymentFlowCopy">
                                        Only the fields needed for this agreement stay open.
                                    </div>
                                </div>
                                <div class="payment-story-card">
                                    <div class="payment-story-title">Quick check</div>
                                    <div class="payment-story-copy" id="paymentFlowHint">
                                        Save the reference, amount, cost, and only the required destination details.
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($isPackageAgreement)
                            <input type="hidden" name="funding" value="auto">
                        @endif

                        <div class="pc-workflow" data-workflow>
                            <section class="pc-step" data-step="payment-mode" data-step-open="1" data-step-unlocked="1">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">1</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Payment flow</span>
                                            <span class="pc-step-text">Start with the agreement flow and funding mode for this payment.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Open</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body>
                                    <div class="pc-step-panel">
                                        <div class="pc-field full">
                                            <div class="payment-story">
                                                <div class="payment-story-card">
                                                    <div class="payment-story-title" id="paymentFlowTitle">Agreement payment flow</div>
                                                    <div class="payment-story-copy" id="paymentFlowCopy">
                                                        Only the fields needed for this agreement stay open.
                                                    </div>
                                                </div>
                                                <div class="payment-story-card">
                                                    <div class="payment-story-title">Quick check</div>
                                                    <div class="payment-story-copy" id="paymentFlowHint">
                                                        Save the reference, amount, cost, and only the required destination details.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @unless($isPackageAgreement)
                                            <div class="pc-field payment-field" data-payment-types="token,send_money,none">
                                                <label>Funding Mode</label>
                                                <select class="pc-select" name="funding" id="fundingModal" required>
                                                    <option value="auto" @selected(old('funding','auto')==='auto')>Auto (Use TOTAL balance)</option>
                                                    <option value="single" @selected(old('funding')==='single')>Single Batch</option>
                                                </select>
                                                <div class="pc-help">Total available (net): <strong>{{ number_format((float)$totalBalance, 2) }}</strong></div>
                                            </div>

                                            <div class="pc-field payment-field" data-payment-types="token,send_money,none" id="batchWrapModal" style="display:none;">
                                                <label>Batch</label>
                                                <select class="pc-select" name="batch_id" id="batchIdModal">
                                                    <option value="">Select batch</option>
                                                    @foreach($batches as $b)
                                                        <option value="{{ $b->id }}" @selected((string)old('batch_id') === (string)$b->id)>
                                                            {{ $b->batch_no }} (Balance: {{ number_format((float)$b->available_balance,2) }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endunless

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-main">
                                                <button class="btn2" type="button" data-step-next>Proceed to Amount</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="pc-step" data-step="payment-values">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">2</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Payment values</span>
                                            <span class="pc-step-text">Add the reference, amount, cost, date, coverage period, and any meter number required by the agreement.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Locked</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body hidden>
                                    <div class="pc-step-panel">
                                        <div class="pc-field payment-field" data-payment-types="token">
                                            <label>Meter Number</label>
                                            <input class="pc-input" name="meter_no" id="paymentMeterInput" @if($isTokenAgreement) required @endif value="{{ old('meter_no', $hostel->meter_no) }}">
                                            <div class="pc-help">Required only for Token.</div>
                                        </div>

                                        <div class="pc-field">
                                            <label>Reference</label>
                                            <input class="pc-input" name="reference" required value="{{ old('reference') }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Amount</label>
                                            <input class="pc-input" type="number" step="0.01" min="0.01" name="amount" id="paymentAmountInput" required value="{{ old('amount') }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Cost</label>
                                            <input class="pc-input" type="number" step="0.01" min="0" name="transaction_cost" id="paymentFeeInput" value="{{ old('transaction_cost', 0) }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Date</label>
                                            <input class="pc-input" type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Coverage Value</label>
                                            <input class="pc-input" type="number" min="1" step="1" name="coverage_value" required value="{{ old('coverage_value', 1) }}">
                                        </div>

                                        <div class="pc-field">
                                            <label>Coverage Unit</label>
                                            <select class="pc-select" name="coverage_unit" required>
                                                <option value="day" @selected(old('coverage_unit') === 'day')>Days</option>
                                                <option value="week" @selected(old('coverage_unit') === 'week')>Weeks</option>
                                                <option value="month" @selected(old('coverage_unit', 'month') === 'month')>Months</option>
                                            </select>
                                        </div>

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-main">
                                                <button class="btn2" type="button" data-step-next>Proceed to Destination</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="pc-step" data-step="payment-destination">
                                <button class="pc-step-trigger" type="button" data-step-toggle>
                                    <span class="pc-step-trigger-main">
                                        <span class="pc-step-index">3</span>
                                        <span class="pc-step-copy">
                                            <span class="pc-step-title">Destination and note</span>
                                            <span class="pc-step-text">Finish with the receiver fields, summary, and final note.</span>
                                        </span>
                                    </span>
                                    <span style="display:flex;align-items:center;gap:10px">
                                        <span class="pc-step-meta" data-step-meta>Locked</span>
                                        <span class="pc-step-arrow">⌄</span>
                                    </span>
                                </button>
                                <div class="pc-step-body" data-step-body hidden>
                                    <div class="pc-step-panel">
                                        <div class="pc-field payment-field" data-payment-types="send_money,token,package,none">
                                            <label id="paymentReceiverNameLabel">Name</label>
                                            <input class="pc-input" name="receiver_name" id="paymentReceiverNameInput" value="{{ old('receiver_name', $hostel->contact_person) }}">
                                            <div class="pc-help" id="paymentReceiverNameHelp">
                                                {{ $agreementType === 'send_money' ? 'Required for Send Money.' : 'Optional.' }}
                                            </div>
                                        </div>

                                        <div class="pc-field payment-field" data-payment-types="send_money,token,package,none">
                                            <label id="paymentReceiverPhoneLabel">Phone Number</label>
                                            <input class="pc-input" name="receiver_phone" id="paymentReceiverPhoneInput" value="{{ old('receiver_phone', $hostel->phone_no) }}">
                                            <div class="pc-help" id="paymentReceiverPhoneHelp">
                                                {{ $agreementType === 'send_money' ? 'Required for Send Money.' : ($agreementType === 'package' ? 'Optional package credit phone number.' : 'Required for non-package payments.') }}
                                            </div>
                                        </div>

                                        <div class="pc-field full">
                                            <div class="payment-summary">
                                                <div class="payment-story-title">Entry summary</div>
                                                <div class="payment-story-copy" id="paymentSummaryCopy">Review the total before you save the entry.</div>
                                                <div class="payment-summary-grid">
                                                    <div>
                                                        <div class="payment-summary-label">Agreement</div>
                                                        <div class="payment-summary-value" id="paymentSummaryAgreement">{{ $agreementTypeLabel }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="payment-summary-label">Fees</div>
                                                        <div class="payment-summary-value" id="paymentSummaryFee">{{ number_format((float) old('transaction_cost', 0), 2) }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="payment-summary-label">Total Outflow</div>
                                                        <div class="payment-summary-value" id="paymentSummaryTotal">0.00</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pc-field full">
                                            <label>Notes</label>
                                            <input class="pc-input" name="notes" required value="{{ old('notes') }}" placeholder="Short context for this payment">
                                        </div>

                                        <div class="pc-step-actions">
                                            <div class="pc-step-actions-main">
                                                <button class="btn" type="submit" data-submit-label="Save Payment" data-loading-label="Recording Payment..." data-loading-copy="Saving the payment into the hostel ledger.">Save Payment</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function(){
    const body = document.body;
    const modals = Array.from(document.querySelectorAll('[data-modal]'));
    const autoOpenModal = @json($openModal);
    const initialPaymentEntryMode = @json($initialPaymentEntryMode);
    const funding = document.getElementById('fundingModal');
    const batchWrap = document.getElementById('batchWrapModal');
    const batchSel = document.getElementById('batchIdModal');
    const overpayMarkedDate = document.getElementById('overpayMarkedDate');
    const overpayDateMode = document.getElementById('overpayDateMode');
    const overpayNextBillingWrap = document.getElementById('overpayNextBillingWrap');
    const overpayNextBillingDate = document.getElementById('overpayNextBillingDate');
    const overpayNextDuePreview = document.getElementById('overpayNextDuePreview');
    const overpayCycleMonths = @json((($hostel->stake ?: 'monthly') === 'semester') ? 4 : 1);
    const agreementTerminateReason = document.getElementById('agreementTerminateReason');
    const agreementTransferWrap = document.getElementById('agreementTransferWrap');
    const agreementTransferHostel = document.getElementById('agreementTransferHostel');
    const paymentRecordForm = document.getElementById('paymentRecordForm');
    const paymentAgreementType = @json($agreementType);
    const paymentFields = Array.from(document.querySelectorAll('.payment-field'));
    const paymentMeterInput = document.getElementById('paymentMeterInput');
    const paymentReceiverNameInput = document.getElementById('paymentReceiverNameInput');
    const paymentReceiverPhoneInput = document.getElementById('paymentReceiverPhoneInput');
    const paymentReceiverNameLabel = document.getElementById('paymentReceiverNameLabel');
    const paymentReceiverNameHelp = document.getElementById('paymentReceiverNameHelp');
    const paymentReceiverPhoneLabel = document.getElementById('paymentReceiverPhoneLabel');
    const paymentReceiverPhoneHelp = document.getElementById('paymentReceiverPhoneHelp');
    const paymentFlowTitle = document.getElementById('paymentFlowTitle');
    const paymentFlowCopy = document.getElementById('paymentFlowCopy');
    const paymentFlowHint = document.getElementById('paymentFlowHint');
    const paymentSummaryAgreement = document.getElementById('paymentSummaryAgreement');
    const paymentSummaryCopy = document.getElementById('paymentSummaryCopy');
    const paymentSummaryFee = document.getElementById('paymentSummaryFee');
    const paymentSummaryTotal = document.getElementById('paymentSummaryTotal');
    const paymentAmountInput = document.getElementById('paymentAmountInput');
    const paymentFeeInput = document.getElementById('paymentFeeInput');
    const manualEntryModeBtn = document.getElementById('manualEntryModeBtn');
    const helperEntryModeBtn = document.getElementById('helperEntryModeBtn');
    const manualEntryPanel = document.getElementById('manualEntryPanel');
    const helperEntryPanel = document.getElementById('helperEntryPanel');
    const paymentReferenceInput = paymentRecordForm ? paymentRecordForm.querySelector('input[name="reference"]') : null;
    const paymentDateInput = paymentRecordForm ? paymentRecordForm.querySelector('input[name="date"]') : null;
    const selectedSmsLogIdInput = document.getElementById('selectedSmsLogId');
    const helperCandidateInputs = Array.from(document.querySelectorAll('[data-helper-candidate]'));
    const helperCandidateRows = Array.from(document.querySelectorAll('[data-helper-candidate-row]'));
    const helperPickButtons = Array.from(document.querySelectorAll('[data-helper-pick]'));
    const helperSyncPanel = document.getElementById('helperSyncPanel');
    const helperConfirmCard = document.getElementById('helperConfirmCard');
    const helperConfirmReference = document.getElementById('helperConfirmReference');
    const helperConfirmAmount = document.getElementById('helperConfirmAmount');
    const helperConfirmCost = document.getElementById('helperConfirmCost');
    const helperConfirmDate = document.getElementById('helperConfirmDate');
    const helperConfirmTarget = document.getElementById('helperConfirmTarget');
    const helperBackToManualBtn = document.getElementById('helperBackToManualBtn');
    const helperStepPickCard = document.getElementById('helperStepPickCard');
    const helperStepConfirmCard = document.getElementById('helperStepConfirmCard');
    const helperStepPostCard = document.getElementById('helperStepPostCard');
    const familyCheckboxes = Array.from(document.querySelectorAll('[data-family-select]'));
    const familyManageRows = Array.from(document.querySelectorAll('[data-family-manage-row]'));
    const familySelectionStatus = document.getElementById('familySelectionStatus');
    const familySelectAllBtn = document.getElementById('familySelectAllBtn');
    const familyClearSelectionBtn = document.getElementById('familyClearSelectionBtn');
    const familyEditSelectedBtn = document.getElementById('familyEditSelectedBtn');
    const familySelectAllCheckbox = document.getElementById('familySelectAllCheckbox');
    const familyEditScope = document.getElementById('familyEditScope');
    const familyEditSelectionWrap = document.getElementById('familyEditSelectionWrap');
    const familyEditSelectionPreview = document.getElementById('familyEditSelectionPreview');
    const familyEditSelectionInputs = document.getElementById('familyEditSelectionInputs');
    const familyFocusPanel = document.getElementById('familyFocusPanel');
    const familyFocusTitle = document.getElementById('familyFocusTitle');
    const familyFocusCopy = document.getElementById('familyFocusCopy');
    const familyFocusHostel = document.getElementById('familyFocusHostel');
    const familyFocusAgreement = document.getElementById('familyFocusAgreement');
    const familyFocusSite = document.getElementById('familyFocusSite');
    const familyFocusRouters = document.getElementById('familyFocusRouters');
    const familyFocusContact = document.getElementById('familyFocusContact');
    const familyFocusPhone = document.getElementById('familyFocusPhone');
    const familyFocusCycleDue = document.getElementById('familyFocusCycleDue');
    const familyFocusLastPayment = document.getElementById('familyFocusLastPayment');
    const familyFocusEditBtn = document.getElementById('familyFocusEditBtn');
    const familyFocusManageLink = document.getElementById('familyFocusManageLink');
    const familyFocusLedgerLink = document.getElementById('familyFocusLedgerLink');
    const familyChildSearchInput = document.getElementById('familyChildSearchInput');
    const familyVisibleCountPill = document.getElementById('familyVisibleCountPill');
    const familyVisibleRoutersPill = document.getElementById('familyVisibleRoutersPill');
    const initialFamilySelection = new Set(@json($familySelectedOld));
    const hostelEditForm = document.getElementById('hostelEditForm');
    let focusedFamilyId = Number(@json($focusChildId ?? 0));

    function getModal(id){
        return document.querySelector('[data-modal="' + id + '"]');
    }

    function openModal(id){
        const modal = getModal(id);
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('pc-modal-open');
    }

    function closeModal(modal){
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (!modals.some((m) => m.classList.contains('show'))) {
            body.classList.remove('pc-modal-open');
        }
    }

    document.querySelectorAll('[data-modal-target]').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger.getAttribute('data-modal-target')));
    });

    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.closest('[data-modal]')));
    });

    modals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const active = modals.find((m) => m.classList.contains('show'));
        if (active) closeModal(active);
    });

    function money(value){
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value || 0));
    }

    function selectedFamilyIds(){
        return familyCheckboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => Number(checkbox.value || 0))
            .filter((value) => value > 0);
    }

    function syncFamilySelection(){
        if (familyCheckboxes.length === 0) return;

        const selectedIds = selectedFamilyIds();
        const selectedLabels = [];

        familyManageRows.forEach((row) => {
            const rowId = Number(row.dataset.hostelId || 0);
            const isSelected = selectedIds.includes(rowId);
            row.classList.toggle('family-picked', isSelected);
        });

        familyCheckboxes.forEach((checkbox) => {
            const rowLabel = String(checkbox.dataset.name || ('Hostel #' + checkbox.value));
            if (checkbox.checked) {
                selectedLabels.push(rowLabel);
            }
        });

        if (familySelectionStatus) {
            familySelectionStatus.textContent = selectedIds.length > 0
                ? (selectedIds.length + ' child hostel' + (selectedIds.length === 1 ? '' : 's') + ' selected for parent actions.')
                : 'Select child hostels to include in parent edit actions.';
        }

        if (familyEditSelectedBtn) {
            familyEditSelectedBtn.disabled = selectedIds.length === 0;
        }

        if (familySelectAllCheckbox) {
            familySelectAllCheckbox.checked = selectedIds.length > 0 && selectedIds.length === familyCheckboxes.length;
            familySelectAllCheckbox.indeterminate = selectedIds.length > 0 && selectedIds.length < familyCheckboxes.length;
        }

        if (familyEditSelectionPreview) {
            familyEditSelectionPreview.value = selectedLabels.length > 0
                ? selectedLabels.join(', ')
                : 'No child hostels selected';
        }

        if (familyEditSelectionInputs) {
            familyEditSelectionInputs.innerHTML = '';
            selectedIds.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_hostels[]';
                input.value = String(id);
                familyEditSelectionInputs.appendChild(input);
            });
        }
    }

    function setFocusedFamilyId(nextId) {
        focusedFamilyId = Number(nextId || 0);

        const currentUrl = new URL(window.location.href);
        if (focusedFamilyId > 0) {
            currentUrl.searchParams.set('focus_child', String(focusedFamilyId));
        } else {
            currentUrl.searchParams.delete('focus_child');
        }
        window.history.replaceState({}, '', currentUrl.toString());

        syncFamilyFocus();
    }

    function syncFamilyFocus() {
        if (!familyManageRows.length || !familyFocusPanel) return;

        let focusedRow = null;
        familyManageRows.forEach((row) => {
            const rowId = Number(row.dataset.hostelId || 0);
            const isFocused = focusedFamilyId > 0 && rowId === focusedFamilyId;
            row.classList.toggle('focus-row', isFocused);

            const focusBtn = row.querySelector('[data-family-focus-btn]');
            if (focusBtn) {
                focusBtn.textContent = isFocused ? 'Focused' : 'Focus';
            }

            const chips = Array.from(row.querySelectorAll('.family-chip'));
            chips.forEach((chip) => {
                if (chip.textContent.trim() === 'Focused') {
                    chip.remove();
                }
            });

            if (isFocused) {
                focusedRow = row;
                const metaWrap = row.querySelector('.family-row-meta');
                if (metaWrap) {
                    const badge = document.createElement('span');
                    badge.className = 'family-chip';
                    badge.style.background = '#eef6ff';
                    badge.style.borderColor = '#b2ddff';
                    badge.style.color = '#1849a9';
                    badge.textContent = 'Focused';
                    metaWrap.appendChild(badge);
                }
            }
        });

        if (!focusedRow) {
            familyFocusPanel.hidden = true;
            return;
        }

        familyFocusPanel.hidden = false;
        if (familyFocusTitle) familyFocusTitle.textContent = String(focusedRow.dataset.hostelName || 'Focused Child Hostel');
        if (familyFocusCopy) familyFocusCopy.textContent = 'Use this workspace to inspect or edit this child hostel from the parent page.';
        if (familyFocusHostel) familyFocusHostel.textContent = String(focusedRow.dataset.hostelName || '-');
        if (familyFocusAgreement) familyFocusAgreement.textContent = String(focusedRow.dataset.agreement || '-');
        if (familyFocusSite) {
            const siteId = String(focusedRow.dataset.siteId || '').trim();
            const siteSn = String(focusedRow.dataset.siteSn || '').trim();
            familyFocusSite.textContent = [siteId !== '' ? ('Site ' + siteId) : '', siteSn !== '' ? siteSn : '']
                .filter(Boolean)
                .join(' / ') || '-';
        }
        if (familyFocusRouters) familyFocusRouters.textContent = String(focusedRow.dataset.routerCount || '0');
        if (familyFocusContact) familyFocusContact.textContent = String(focusedRow.dataset.contact || '-');
        if (familyFocusPhone) familyFocusPhone.textContent = String(focusedRow.dataset.phone || '-');
        if (familyFocusCycleDue) {
            familyFocusCycleDue.textContent = String(focusedRow.dataset.cycle || '-') + ' / ' + String(focusedRow.dataset.due || '-');
        }
        if (familyFocusLastPayment) familyFocusLastPayment.textContent = String(focusedRow.dataset.lastPayment || '-');

        if (familyFocusManageLink) {
            const url = new URL(familyFocusManageLink.href, window.location.origin);
            url.searchParams.set('focus_step', 'agreement-family');
            url.searchParams.set('focus_child', String(focusedFamilyId));
            familyFocusManageLink.href = url.toString();
        }
        if (familyFocusLedgerLink) {
            const url = new URL(familyFocusLedgerLink.href, window.location.origin);
            url.searchParams.set('focus_child', String(focusedFamilyId));
            url.searchParams.set('child_ledger', '1');
            familyFocusLedgerLink.href = url.toString();
        }
    }

    function syncFamilyEditScope(){
        if (!familyEditScope || !familyEditSelectionWrap) return;
        const isSelectedScope = familyEditScope.value === 'parent_and_selected';
        familyEditSelectionWrap.hidden = !isSelectedScope;
    }

    function syncFamilyRowFilter() {
        if (!familyManageRows.length) return;

        const query = String(familyChildSearchInput ? familyChildSearchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;
        let visibleRouters = 0;

        familyManageRows.forEach((row) => {
            const haystack = String(row.dataset.search || '');
            const matches = query === '' || haystack.includes(query);
            row.hidden = !matches;
            if (!matches) return;
            visibleCount += 1;
            visibleRouters += Number(row.dataset.routerCount || 0);
        });

        if (familyVisibleCountPill) {
            familyVisibleCountPill.textContent = visibleCount + ' visible';
        }
        if (familyVisibleRoutersPill) {
            familyVisibleRoutersPill.textContent = visibleRouters + ' routers';
        }
    }

    function syncFunding(){
        if (!funding || !batchWrap) return;
        const isSingle = funding.value === 'single';
        batchWrap.style.display = isSingle ? 'block' : 'none';
        if (!isSingle && batchSel) batchSel.value = '';
    }

    function syncPaymentFlow(){
        if (!paymentRecordForm) return;

        const configMap = {
            token: {
                title: 'Token flow',
                copy: 'Save meter number, amount, cost, and phone number.',
                hint: 'This deducts petty balance and keeps the meter visible.',
                receiverNameLabel: 'Name',
                receiverNameHelp: 'Optional.',
                receiverPhoneLabel: 'Phone Number',
                receiverPhoneHelp: 'Required for non-package Token payments.',
                requires: { meter: true, receiverName: false, receiverPhone: true },
            },
            send_money: {
                title: 'Send Money flow',
                copy: 'Save name, phone number, amount, and cost. Meter stays hidden.',
                hint: 'Use the real Send Money name and phone number.',
                receiverNameLabel: 'Name',
                receiverNameHelp: 'Required for Send Money.',
                receiverPhoneLabel: 'Phone Number',
                receiverPhoneHelp: 'Required for Send Money.',
                requires: { meter: false, receiverName: true, receiverPhone: true },
            },
            package: {
                title: 'Package flow',
                copy: 'Save package amount and the phone number to credit without showing the meter.',
                hint: 'This posts a package credit and does not deduct petty balance.',
                receiverNameLabel: 'Name',
                receiverNameHelp: 'Optional.',
                receiverPhoneLabel: 'Phone Number',
                receiverPhoneHelp: 'Optional package credit phone number.',
                requires: { meter: false, receiverName: false, receiverPhone: false },
            },
            none: {
                title: 'General flow',
                copy: 'Keep the entry simple with amount, cost, and phone number.',
                hint: 'Meter stays hidden, but phone number still matters.',
                receiverNameLabel: 'Name',
                receiverNameHelp: 'Optional.',
                receiverPhoneLabel: 'Phone Number',
                receiverPhoneHelp: 'Required for non-package entries.',
                requires: { meter: false, receiverName: false, receiverPhone: true },
            },
        };

        const agreementType = ['token', 'send_money', 'package', 'none'].includes(String(paymentAgreementType || 'none'))
            ? String(paymentAgreementType || 'none')
            : 'none';
        const config = configMap[agreementType] || configMap.none;

        paymentFields.forEach((field) => {
            const types = String(field.dataset.paymentTypes || '')
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);
            field.hidden = types.length > 0 && !types.includes(agreementType);
        });

        if (paymentFlowTitle) paymentFlowTitle.textContent = config.title;
        if (paymentFlowCopy) paymentFlowCopy.textContent = config.copy;
        if (paymentFlowHint) paymentFlowHint.textContent = config.hint;
        if (paymentSummaryAgreement) {
            paymentSummaryAgreement.textContent =
                agreementType === 'token' ? 'Token' :
                agreementType === 'send_money' ? 'Send Money' :
                agreementType === 'package' ? 'Package' : 'No Agreement';
        }
        if (paymentReceiverNameLabel) paymentReceiverNameLabel.textContent = config.receiverNameLabel;
        if (paymentReceiverNameHelp) paymentReceiverNameHelp.textContent = config.receiverNameHelp;
        if (paymentReceiverPhoneLabel) paymentReceiverPhoneLabel.textContent = config.receiverPhoneLabel;
        if (paymentReceiverPhoneHelp) paymentReceiverPhoneHelp.textContent = config.receiverPhoneHelp;
        if (paymentMeterInput) paymentMeterInput.required = !!config.requires.meter;
        if (paymentReceiverNameInput) paymentReceiverNameInput.required = !!config.requires.receiverName;
        if (paymentReceiverPhoneInput) paymentReceiverPhoneInput.required = !!config.requires.receiverPhone;
    }

    function syncPaymentSummary(){
        const amount = Number(paymentAmountInput ? paymentAmountInput.value : 0);
        const fee = Number(paymentFeeInput ? paymentFeeInput.value : 0);
        if (paymentSummaryFee) paymentSummaryFee.textContent = money(fee);
        if (paymentSummaryTotal) paymentSummaryTotal.textContent = money(amount + fee);
        if (paymentSummaryCopy) {
            paymentSummaryCopy.textContent = 'You are about to post KES ' + money(amount + fee) + ' including fees.';
        }
    }

    function setPaymentEntryMode(mode) {
        const useHelper = mode === 'helper';
        if (manualEntryPanel) {
            manualEntryPanel.hidden = useHelper;
        }
        if (helperEntryPanel) {
            helperEntryPanel.hidden = !useHelper;
        }
        if (manualEntryModeBtn) {
            manualEntryModeBtn.classList.toggle('is-active', !useHelper);
        }
        if (helperEntryModeBtn) {
            helperEntryModeBtn.classList.toggle('is-active', useHelper);
        }
        if (paymentRecordForm) {
            paymentRecordForm.dataset.entryMode = useHelper ? 'helper' : 'manual';
        }
    }

    function parseIsoDate(value){
        const raw = String(value || '').trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) return null;
        const [year, month, day] = raw.split('-').map((item) => Number(item));
        const date = new Date(Date.UTC(year, month - 1, day));
        if (
            date.getUTCFullYear() !== year
            || date.getUTCMonth() !== (month - 1)
            || date.getUTCDate() !== day
        ) {
            return null;
        }
        return date;
    }

    function formatIsoDate(date){
        if (!date) return '';
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const day = String(date.getUTCDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function addMonthsNoOverflow(baseDate, months){
        if (!baseDate) return null;
        const day = baseDate.getUTCDate();
        const targetStart = new Date(Date.UTC(
            baseDate.getUTCFullYear(),
            baseDate.getUTCMonth() + Number(months || 0),
            1
        ));
        const targetYear = targetStart.getUTCFullYear();
        const targetMonth = targetStart.getUTCMonth();
        const lastDay = new Date(Date.UTC(targetYear, targetMonth + 1, 0)).getUTCDate();
        return new Date(Date.UTC(targetYear, targetMonth, Math.min(day, lastDay)));
    }

    function syncOverpayPreview(){
        if (!overpayNextDuePreview) return;
        const mode = overpayDateMode ? String(overpayDateMode.value || 'from_marked_date') : 'from_marked_date';
        let nextDue = null;

        if (mode === 'next_billing_date') {
            nextDue = parseIsoDate(overpayNextBillingDate ? overpayNextBillingDate.value : '');
        } else {
            const markedDate = parseIsoDate(overpayMarkedDate ? overpayMarkedDate.value : '');
            nextDue = addMonthsNoOverflow(markedDate, overpayCycleMonths);
        }

        overpayNextDuePreview.textContent = 'Next due will be: ' + (nextDue ? formatIsoDate(nextDue) : 'YYYY-MM-DD');
    }

    function syncOverpayNextBillingMin(){
        if (!overpayMarkedDate || !overpayNextBillingDate) return;
        overpayNextBillingDate.min = String(overpayMarkedDate.value || '');
    }

    function syncOverpayDateMode(){
        if (!overpayDateMode || !overpayNextBillingWrap || !overpayNextBillingDate) return;
        syncOverpayNextBillingMin();
        const isNextBilling = overpayDateMode.value === 'next_billing_date';
        overpayNextBillingWrap.style.display = isNextBilling ? 'block' : 'none';
        overpayNextBillingDate.required = isNextBilling;
        syncOverpayPreview();
    }

    function syncAgreementTermination(){
        if (!agreementTerminateReason || !agreementTransferWrap || !agreementTransferHostel) return;
        const isTransfer = agreementTerminateReason.value === 'transfer_agreement';
        agreementTransferWrap.style.display = isTransfer ? 'block' : 'none';
        agreementTransferHostel.required = isTransfer;
    }

    function renderPaymentErrors(errors){
        if (!paymentRecordForm) return;
        const errorBox = paymentRecordForm.querySelector('[data-form-errors]');
        if (!errorBox) return;

        errorBox.innerHTML = '';
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
            errorBox.hidden = true;
            return;
        }

        messages.forEach((message) => {
            const line = document.createElement('div');
            line.textContent = message;
            errorBox.appendChild(line);
        });

        errorBox.hidden = false;
    }

    async function submitPaymentForm(event){
        if (!paymentRecordForm || paymentRecordForm.getAttribute('data-json-submit') !== '1') return;
        const submitButton = event.submitter || paymentRecordForm.querySelector('[type="submit"]');
        if (submitButton && submitButton.hasAttribute('data-skip-json-submit')) {
            return;
        }
        event.preventDefault();
        renderPaymentErrors(null);

        const originalLabel = submitButton ? submitButton.textContent : 'Save Payment';
        if (submitButton) {
            if (window.pettySetButtonLoadingState) {
                window.pettySetButtonLoadingState(
                    submitButton,
                    window.pettyResolveLoadingLabel
                        ? window.pettyResolveLoadingLabel(submitButton, 'Recording Payment...')
                        : (submitButton.getAttribute('data-loading-label') || 'Recording Payment...')
                );
            } else {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Saving...</span>';
            }
        }

        let saved = false;
        try {
            const response = await fetch(paymentRecordForm.action, {
                method: 'POST',
                body: new FormData(paymentRecordForm),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));
            if (response.status === 422) {
                renderPaymentErrors(payload.errors || {});
                if (window.pettyCreateToast) {
                    window.pettyCreateToast({
                        type: 'error',
                        title: 'Fix These Fields',
                        message: payload.message || 'The payment entry still has validation issues.',
                    });
                }
                return;
            }

            if (!response.ok) {
                if (window.pettyCreateToast) {
                    window.pettyCreateToast({
                        type: 'error',
                        title: 'Save Failed',
                        message: payload.message || 'Unable to save this payment right now.',
                    });
                }
                return;
            }

            if (window.pettyCreateToast) {
                window.pettyCreateToast({
                    type: 'success',
                    title: paymentRecordForm.dataset.successTitle || 'Saved',
                    message: payload.message || 'Payment recorded.',
                });
            }
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
            if (window.pettyCreateToast) {
                window.pettyCreateToast({
                    type: 'error',
                    title: 'Network Error',
                    message: error && error.message ? error.message : 'Unable to save the payment.',
                });
            }
        } finally {
            if (submitButton && !saved) {
                if (window.pettyRestoreButtonState) {
                    window.pettyRestoreButtonState(submitButton);
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel;
                }
            }
            delete paymentRecordForm.dataset.submitPending;
        }
    }

    function setupOntPicker(prefix){
        const mountNode = document.querySelector('.ont-smart[data-target-prefix="' + prefix + '"]');
        const select = document.querySelector('.ont-select[data-target-prefix="' + prefix + '"]');
        if (!mountNode || !select) return;
        const searchUrl = String(mountNode.dataset.searchUrl || '');
        const defaultLabel = 'Search ONT site by name';
        let activeRequest = 0;

        const hiddenName = document.querySelector('.hostel-name-hidden[data-target-prefix="' + prefix + '"]');
        const hostelPreview = document.querySelector('.selected-hostel-preview[data-target-prefix="' + prefix + '"]');
        const sitePreview = document.querySelector('.selected-site-preview[data-target-prefix="' + prefix + '"]');
        const snPreview = document.querySelector('.selected-sn-preview[data-target-prefix="' + prefix + '"]');
        const statusPreview = document.querySelector('.selected-status-preview[data-target-prefix="' + prefix + '"]');
        if (!hostelPreview || !sitePreview || !snPreview || !statusPreview) return;

        function syncStatusChip(label, tone) {
            statusPreview.textContent = label || 'Not Added';
            statusPreview.classList.remove('success', 'muted');
            statusPreview.classList.add(tone === 'success' ? 'success' : 'muted');
        }

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ont-smart-trigger';
        trigger.disabled = select.disabled || !searchUrl;

        const triggerLabel = document.createElement('span');
        const triggerIcon = document.createElement('span');
        triggerIcon.textContent = '▾';
        trigger.appendChild(triggerLabel);
        trigger.appendChild(triggerIcon);

        const menu = document.createElement('div');
        menu.className = 'ont-smart-menu';
        menu.hidden = true;

        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'ont-smart-search';
        search.placeholder = 'Search site name...';

        const list = document.createElement('div');
        list.className = 'ont-smart-list';

        menu.appendChild(search);
        menu.appendChild(list);
        mountNode.appendChild(trigger);
        mountNode.appendChild(menu);

        function upsertSelectOption(item) {
            const value = String(item.key || '');
            if (value === '') return null;

            const label = String(item.hostel_name || '') + (item.site_id ? (' • Site ' + item.site_id) : '');
            let option = Array.from(select.options).find((row) => row.value === value);
            if (!option) {
                option = new Option(label, value, false, false);
                select.add(option);
            } else {
                option.textContent = label;
            }

            option.dataset.name = String(item.hostel_name || '');
            option.dataset.siteId = String(item.site_id || '');
            option.dataset.siteSn = String(item.site_sn || '');
            option.dataset.mergeStatus = String(item.merge_status || 'unlinked');
            option.dataset.mergeStatusLabel = String(item.merge_status_label || 'Not Added');
            option.dataset.mergeStatusTone = String(item.merge_status_tone || 'muted');

            return option;
        }

        const syncFromSelected = () => {
            const selected = select.options[select.selectedIndex];
            if (!selected || !selected.value) {
                if (hiddenName) hiddenName.value = '';
                hostelPreview.textContent = '-';
                sitePreview.textContent = '';
                snPreview.textContent = '';
                syncStatusChip('Not Added', 'muted');
                return;
            }

            const hostelName = selected.dataset.name || '';
            const siteId = selected.dataset.siteId || '';
            const siteSn = selected.dataset.siteSn || '';
            const mergeLabel = selected.dataset.mergeStatusLabel || 'Not Added';
            const mergeTone = selected.dataset.mergeStatusTone || 'muted';

            if (hiddenName) hiddenName.value = hostelName;
            hostelPreview.textContent = hostelName || '-';
            sitePreview.textContent = siteId !== '' ? ('Site ' + siteId) : 'No site id';
            snPreview.textContent = siteSn !== '' ? ('S.N ' + siteSn) : '';
            syncStatusChip(mergeLabel, mergeTone);
        };

        function renderHint(text) {
            list.innerHTML = '';
            const row = document.createElement('div');
            row.className = 'ont-smart-empty';
            row.textContent = text;
            list.appendChild(row);
        }

        function renderItems(items) {
            list.innerHTML = '';
            if (!Array.isArray(items) || items.length === 0) {
                renderHint('No site found');
                return;
            }

            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ont-smart-item';
                if (select.value === String(item.key || '')) {
                    btn.classList.add('active');
                }

                const title = document.createElement('div');
                title.className = 'ont-smart-item-title';
                title.textContent = String(item.hostel_name || item.site_name || '-');

                const meta = document.createElement('div');
                meta.className = 'ont-smart-item-meta';

                const site = document.createElement('span');
                site.className = 'ont-smart-item-site';
                site.textContent = item.site_id ? ('Site ' + item.site_id) : 'No site id';

                const sn = document.createElement('span');
                sn.className = 'ont-smart-item-sn';
                sn.textContent = 'S.N ' + String(item.site_sn || '-');

                const status = document.createElement('span');
                status.className = 'ont-status-chip ' + (String(item.merge_status_tone || 'muted') === 'success' ? 'success' : 'muted');
                status.textContent = String(item.merge_status_label || 'Not Added');

                meta.appendChild(site);
                meta.appendChild(sn);
                meta.appendChild(status);
                btn.appendChild(title);
                btn.appendChild(meta);

                btn.addEventListener('click', function () {
                    const option = upsertSelectOption(item);
                    if (!option) return;
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    menu.hidden = true;
                    updateTriggerLabel();
                });

                list.appendChild(btn);
            });
        }

        async function fetchAndRender(query) {
            if (!searchUrl) {
                renderHint('ONT search endpoint unavailable');
                return;
            }

            const requestNo = ++activeRequest;
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '40');
            renderHint('Searching ONT sites...');

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));
                if (requestNo !== activeRequest) return;

                if (!response.ok) {
                    renderHint(String(payload.message || 'Search failed'));
                    return;
                }

                renderItems(Array.isArray(payload.hostels) ? payload.hostels : (Array.isArray(payload.onts) ? payload.onts : []));
            } catch (error) {
                if (requestNo !== activeRequest) return;
                const detail = error && error.message ? (': ' + error.message) : '. Try again.';
                renderHint('Search failed' + detail);
            }
        }

        function updateTriggerLabel() {
            const selected = select.options[select.selectedIndex];
            triggerLabel.textContent = selected && selected.value ? selected.textContent.trim() : defaultLabel;
        }

        async function onSearchInput() {
            const q = search.value.trim();
            if (q.length < 2) {
                renderHint('Type 2+ characters');
                return;
            }
            await fetchAndRender(q);
        }

        trigger.addEventListener('click', function () {
            if (trigger.disabled) return;
            menu.hidden = !menu.hidden;
            if (!menu.hidden) {
                if (search.value.trim().length < 2) {
                    renderHint('Type 2+ characters');
                } else {
                    fetchAndRender(search.value.trim());
                }
                search.focus();
            }
        });

        search.addEventListener('input', onSearchInput);
        search.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const firstOption = list.querySelector('.ont-smart-item');
            if (firstOption) {
                firstOption.click();
            }
        });

        document.addEventListener('click', function (event) {
            if (!mountNode.contains(event.target)) {
                menu.hidden = true;
            }
        });

        select.addEventListener('change', syncFromSelected);
        select.addEventListener('change', updateTriggerLabel);
        updateTriggerLabel();
        syncFromSelected();
        renderHint('Type 2+ characters');
    }

    familyCheckboxes.forEach((checkbox) => {
        if (initialFamilySelection.has(Number(checkbox.value || 0))) {
            checkbox.checked = true;
        }
        checkbox.addEventListener('change', syncFamilySelection);
    });

    familyManageRows.forEach((row) => {
        const rowId = Number(row.dataset.hostelId || 0);
        const focusBtn = row.querySelector('[data-family-focus-btn]');
        const editBtn = row.querySelector('[data-family-edit-one]');

        if (focusBtn) {
            focusBtn.addEventListener('click', function () {
                setFocusedFamilyId(focusedFamilyId === rowId ? 0 : rowId);
            });
        }

        if (editBtn) {
            editBtn.addEventListener('click', function () {
                familyCheckboxes.forEach((checkbox) => {
                    checkbox.checked = Number(checkbox.value || 0) === rowId;
                });
                syncFamilySelection();
                if (familyEditScope) {
                    familyEditScope.value = 'parent_and_selected';
                }
                syncFamilyEditScope();
                setFocusedFamilyId(rowId);
                openModal('hostel-edit');
            });
        }
    });

    if (familySelectAllCheckbox) {
        familySelectAllCheckbox.addEventListener('change', function () {
            familyCheckboxes.forEach((checkbox) => {
                checkbox.checked = familySelectAllCheckbox.checked;
            });
            syncFamilySelection();
        });
    }

    if (familySelectAllBtn) {
        familySelectAllBtn.addEventListener('click', function () {
            familyCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });
            syncFamilySelection();
        });
    }

    if (familyClearSelectionBtn) {
        familyClearSelectionBtn.addEventListener('click', function () {
            familyCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncFamilySelection();
        });
    }

    if (familyEditSelectedBtn) {
        familyEditSelectedBtn.addEventListener('click', function () {
            if (selectedFamilyIds().length === 0) {
                if (window.pettyCreateToast) {
                    window.pettyCreateToast({
                        type: 'error',
                        title: 'Select Child Hostels',
                        message: 'Pick at least one child hostel before using the shared edit action.',
                    });
                }
                return;
            }
            if (familyEditScope) {
                familyEditScope.value = 'parent_and_selected';
            }
            syncFamilyEditScope();
            openModal('hostel-edit');
        });
    }

    if (familyFocusEditBtn) {
        familyFocusEditBtn.addEventListener('click', function () {
            if (focusedFamilyId <= 0) return;
            familyCheckboxes.forEach((checkbox) => {
                checkbox.checked = Number(checkbox.value || 0) === focusedFamilyId;
            });
            syncFamilySelection();
            if (familyEditScope) {
                familyEditScope.value = 'parent_and_selected';
            }
            syncFamilyEditScope();
            openModal('hostel-edit');
        });
    }

    if (familyEditScope) {
        familyEditScope.addEventListener('change', syncFamilyEditScope);
    }

    if (hostelEditForm) {
        hostelEditForm.addEventListener('submit', function (event) {
            if (!familyEditScope || familyEditScope.value !== 'parent_and_selected') return;
            if (selectedFamilyIds().length > 0) return;
            event.preventDefault();
            if (window.pettyCreateToast) {
                window.pettyCreateToast({
                    type: 'error',
                    title: 'Select Child Hostels',
                    message: 'Choose at least one child hostel before saving a shared update.',
                });
            }
        });
    }

    if (familyChildSearchInput) {
        familyChildSearchInput.addEventListener('input', syncFamilyRowFilter);
    }

    if (funding) funding.addEventListener('change', syncFunding);
    syncFunding();

    function syncHelperCandidateSelection(selectedInput){
        const currentId = selectedInput ? String(selectedInput.dataset.logId || '') : '';
        helperCandidateRows.forEach((row) => {
            const radio = row.querySelector('[data-helper-candidate]');
            row.classList.toggle('is-selected', !!radio && radio === selectedInput);
        });
        if (helperSyncPanel) {
            helperSyncPanel.classList.toggle('helper-selected-state', !!selectedInput);
        }
        if (helperStepPickCard) {
            helperStepPickCard.classList.toggle('is-active', !selectedInput);
        }
        if (helperStepConfirmCard) {
            helperStepConfirmCard.classList.toggle('is-active', !!selectedInput);
        }
        if (helperStepPostCard) {
            helperStepPostCard.classList.toggle('is-active', !!selectedInput);
        }
        if (selectedSmsLogIdInput) {
            selectedSmsLogIdInput.value = currentId;
        }
        if (helperConfirmCard) {
            helperConfirmCard.hidden = !selectedInput;
        }
        if (!selectedInput) {
            if (helperConfirmReference) helperConfirmReference.textContent = '-';
            if (helperConfirmAmount) helperConfirmAmount.textContent = '-';
            if (helperConfirmCost) helperConfirmCost.textContent = '-';
            if (helperConfirmDate) helperConfirmDate.textContent = '-';
            if (helperConfirmTarget) helperConfirmTarget.textContent = '-';
            return;
        }
        if (helperConfirmReference) helperConfirmReference.textContent = selectedInput.dataset.reference || '-';
        if (helperConfirmAmount) helperConfirmAmount.textContent = 'KES ' + money(selectedInput.dataset.amount || 0);
        if (helperConfirmCost) helperConfirmCost.textContent = 'KES ' + money(selectedInput.dataset.cost || 0);
        if (helperConfirmDate) helperConfirmDate.textContent = selectedInput.dataset.date || '-';
        if (helperConfirmTarget) helperConfirmTarget.textContent = selectedInput.dataset.meter || '-';
        setPaymentEntryMode(selectedInput ? 'helper' : 'manual');
        if (!selectedInput) return;
        if (paymentReferenceInput && selectedInput.dataset.reference) {
            paymentReferenceInput.value = selectedInput.dataset.reference;
        }
        if (paymentAmountInput && selectedInput.dataset.amount) {
            paymentAmountInput.value = selectedInput.dataset.amount;
        }
        if (paymentFeeInput && selectedInput.dataset.cost) {
            paymentFeeInput.value = selectedInput.dataset.cost;
        }
        if (paymentDateInput && selectedInput.dataset.date) {
            paymentDateInput.value = selectedInput.dataset.date;
        }
        if (paymentMeterInput && selectedInput.dataset.meter) {
            paymentMeterInput.value = selectedInput.dataset.meter;
        }
        syncPaymentSummary();
    }

    helperCandidateInputs.forEach((input) => {
        input.addEventListener('change', function () {
            syncHelperCandidateSelection(input.checked ? input : null);
        });
    });

    helperPickButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const targetId = String(button.getAttribute('data-helper-pick') || '');
            const radio = helperCandidateInputs.find((input) => String(input.dataset.logId || '') === targetId);
            if (!radio) return;
            radio.checked = true;
            syncHelperCandidateSelection(radio);
        });
    });

    if (helperBackToManualBtn) {
        helperBackToManualBtn.addEventListener('click', function () {
            helperCandidateInputs.forEach((input) => {
                input.checked = false;
            });
            syncHelperCandidateSelection(null);
        });
    }

    if (manualEntryModeBtn) {
        manualEntryModeBtn.addEventListener('click', function () {
            helperCandidateInputs.forEach((input) => {
                input.checked = false;
            });
            syncHelperCandidateSelection(null);
            setPaymentEntryMode('manual');
        });
    }

    if (helperEntryModeBtn) {
        helperEntryModeBtn.addEventListener('click', function () {
            setPaymentEntryMode('helper');
        });
    }

    if (paymentAmountInput) {
        paymentAmountInput.addEventListener('input', syncPaymentSummary);
    }
    if (paymentFeeInput) {
        paymentFeeInput.addEventListener('input', syncPaymentSummary);
    }
    if (paymentRecordForm) {
        paymentRecordForm.addEventListener('submit', submitPaymentForm);
        syncPaymentFlow();
        syncPaymentSummary();
    }
    const checkedHelperCandidate = helperCandidateInputs.find((input) => input.checked);
    if (checkedHelperCandidate) {
        syncHelperCandidateSelection(checkedHelperCandidate);
    } else {
        setPaymentEntryMode(initialPaymentEntryMode === 'helper' ? 'helper' : 'manual');
    }

    if (overpayMarkedDate) {
        overpayMarkedDate.addEventListener('change', syncOverpayNextBillingMin);
        overpayMarkedDate.addEventListener('change', syncOverpayPreview);
        syncOverpayNextBillingMin();
    }

    if (overpayNextBillingDate) {
        overpayNextBillingDate.addEventListener('change', syncOverpayPreview);
    }

    if (overpayDateMode) {
        overpayDateMode.addEventListener('change', syncOverpayDateMode);
        syncOverpayDateMode();
    }

    if (agreementTerminateReason) {
        agreementTerminateReason.addEventListener('change', syncAgreementTermination);
        syncAgreementTermination();
    }

    syncOverpayPreview();
    syncFamilySelection();
    syncFamilyEditScope();
    syncFamilyRowFilter();
    syncFamilyFocus();

    setupOntPicker('edit');
    setupOntPicker('merge');

    if (autoOpenModal) {
        openModal(autoOpenModal);
    }
})();
</script>
@endpush
