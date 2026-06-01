@extends('pettycash::layouts.app')

@section('title','Token - Hostels')

@push('styles')
<style>
    .wrap{max-width:none;margin:0}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px;overflow:visible}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}

    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top;overflow-wrap:anywhere}
    th{font-size:12px;color:#475467;text-align:left;white-space:normal}

    .btn{display:inline-block;padding:8px 11px;border-radius:9px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:700;border:none;cursor:pointer;font-size:13px;line-height:1.15}
    .btn2{display:inline-block;padding:8px 11px;border-radius:9px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700;font-size:13px;line-height:1.15}
    .btn2:hover{background:#f9fafb}
    .btn2-slim{
        padding:5px 8px;
        border-radius:8px;
        font-size:11px;
        line-height:1.1;
    }
    .actions-trigger{
        min-height:28px;
        padding:5px 8px;
        border-radius:8px;
        font-size:11px;
    }
    .action-menu{position:relative;display:inline-block}
    .action-menu[open]{z-index:1200}
    .action-menu > summary{list-style:none;cursor:pointer;user-select:none}
    .action-menu > summary::-webkit-details-marker{display:none}
    .action-menu-list{
        position:absolute;
        right:0;
        top:calc(100% + 6px);
        z-index:2000;
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
    .action-menu-item.is-highlight{color:#175cd3}
    .action-menu-item.is-disabled{
        color:#98a2b3;
        background:#f9fafb;
        cursor:not-allowed;
    }

    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;white-space:nowrap}
    .hostel-link{
        color:#175cd3;
        font-weight:900;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
        overflow-wrap:anywhere;
        word-break:break-word;
    }
    .hostel-link:hover{color:#1849a9;text-decoration:underline}

    /* Status badges */
    .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
    .b-upcoming{background:#eff8ff;border:1px solid #b2ddff;color:#175cd3}
    .b-due{background:#fffaeb;border:1px solid #fedf89;color:#b54708}
    .b-overdue{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
    .b-unknown{background:#f2f4f7;border:1px solid #eaecf0;color:#344054}

    /* Reminder cards */
    .rem-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:12px;
        align-items:stretch;
    }
    .rem-grid > *{min-width:0}
    .rem-card{
        border:1px solid #e7e9f2;
        background:#fff;
        border-radius:14px;
        padding:12px;
        box-shadow:0 6px 18px rgba(16,24,40,.06);
        min-height:96px;
        transition: all .16s ease;
    }
    .rem-grid > a:hover .rem-card {
        box-shadow: 0 10px 24px rgba(16,24,40,.12);
        transform: translateY(-2px);
    }
    .rem-title{font-size:12px;color:#475467;margin:0}
    .rem-count{font-size:20px;font-weight:900;margin:3px 0 0}
    .rem-hint{margin:6px 0 0;font-size:12px;color:#667085}
    .rem-card.due{border-color:#fedf89}
    .rem-card.overdue{border-color:#fecdca}
    .rem-card.soon{border-color:#b2ddff}

    /* Search bar */
    .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
    .input{width:min(420px,100%);padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;font-size:13px;outline:none}
    .input:focus{border-color:#7f56d9;box-shadow:0 0 0 4px rgba(127,86,217,.12)}
    .mini{font-size:12px;color:#667085}
    .right{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}

    /* Responsive table */
    .table-wrap{
        overflow-x:hidden;
        overflow-y:visible;
        border-radius:14px;
        border:1px solid #eef2f6;
        margin-top:12px;
        background:#fff;
    }
    .table-wrap.table-wrap-hostels{
        overflow:visible !important;
        position:relative;
        z-index:5;
    }
    .table-wrap table{
        margin-top:0;
        width:100%;
        min-width:100% !important;
        table-layout:auto;
    }
    .table-wrap th,.table-wrap td{padding:12px 10px}
    .table-wrap td{white-space:normal}
    .table-wrap tbody tr:hover{background:#f9fbff}
    .table-wrap .nowrap{white-space:normal}
    .col-site-sn{
        white-space:nowrap !important;
        word-break:normal !important;
        overflow-wrap:normal !important;
    }
    .col-site-sn .site-sn-text{
        white-space:nowrap;
        font-weight:700;
    }
    .col-routers{
        width:64px;
        min-width:64px;
        text-align:center;
    }
    .col-actions{
        width:96px;
        min-width:96px;
    }

    .section-title{
        font-weight:900;
        font-size:16px;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .section-title::before{
        content:"";
        width:6px;
        height:18px;
        border-radius:99px;
        background:#7f56d9;
    }
    .footer-note{
        margin-top:10px;
        border-top:1px dashed #d0d5dd;
        padding-top:10px;
    }
    .pager{
        margin-top:12px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .pager-meta{
        font-size:12px;
        color:#667085;
    }
    .pager-nav{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
    }
    .pg-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:36px;
        height:36px;
        padding:0 12px;
        border-radius:10px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:13px;
        font-weight:700;
        line-height:1;
        transition:.16s ease;
    }
    .pg-btn:hover{
        border-color:#98a2b3;
        background:#f9fafb;
    }
    .pg-btn.active{
        border-color:#111827;
        background:#111827;
        color:#fff;
    }
    .pg-btn.disabled{
        color:#98a2b3;
        background:#f9fafb;
        border-color:#eaecf0;
        pointer-events:none;
    }

    /* Stats row */
    .stat-grid{display:grid;grid-template-columns:repeat(3, minmax(0, 1fr));gap:10px;margin-top:12px}
    .stat-card{border:1px solid #e7e9f2;background:#fff;border-radius:14px;padding:12px;box-shadow:0 6px 18px rgba(16,24,40,.06)}
    .stat-label{font-size:12px;color:#475467;margin:0}
    .stat-value{font-size:22px;font-weight:900;margin:4px 0 0}
    .stat-sub{font-size:12px;color:#667085;margin-top:6px}
    .mobile-hostels{display:none;margin-top:12px}
    .mobile-hostels-shell{
        border:1px solid #e7e9f2;
        border-radius:16px;
        background:#fff;
        box-shadow:0 8px 22px rgba(16,24,40,.08);
        overflow:hidden;
    }
    .mobile-hostel-card{
        border-bottom:1px solid #eef2f6;
        background:#fff;
    }
    .mobile-hostel-card:last-child{border-bottom:none}
    .mobile-hostel-summary{
        list-style:none;
        display:grid;
        grid-template-columns:auto 1fr auto;
        align-items:center;
        gap:10px;
        padding:11px 12px;
        cursor:pointer;
    }
    .mobile-hostel-summary::-webkit-details-marker{display:none}
    .mobile-hostel-card[open] .mobile-hostel-summary{background:#f9fbff}
    .mobile-chevron{
        width:24px;
        height:24px;
        border-radius:8px;
        border:1px solid #d0d5dd;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:12px;
        color:#475467;
        transition:transform .16s ease;
        flex:0 0 auto;
    }
    .mobile-hostel-card[open] .mobile-chevron{transform:rotate(90deg)}
    .mobile-hostel-title{
        font-size:14px;
        font-weight:900;
        color:#101828;
        line-height:1.2;
        overflow-wrap:anywhere;
    }
    .mobile-hostel-sub{
        margin-top:3px;
        font-size:12px;
        color:#667085;
    }
    .mobile-hostel-right{
        text-align:right;
        display:grid;
        gap:5px;
        justify-items:end;
        flex:0 0 auto;
    }
    .mobile-hostel-amount{
        font-size:12px;
        font-weight:800;
        color:#101828;
    }
    .mobile-hostel-extra{
        border-top:1px solid #eef2f6;
        padding:10px 12px 12px;
        display:grid;
        gap:9px;
    }
    .mobile-kv{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
    }
    .mobile-kv-item{
        border:1px solid #eef2f6;
        border-radius:9px;
        padding:7px 8px;
        background:#fcfcfd;
    }
    .mobile-kv-label{
        font-size:10px;
        font-weight:800;
        letter-spacing:.04em;
        text-transform:uppercase;
        color:#667085;
    }
    .mobile-kv-value{
        margin-top:3px;
        font-size:13px;
        font-weight:800;
        color:#101828;
        overflow-wrap:anywhere;
    }
    .mobile-actions{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
    }
    .mobile-action-btn{
        display:flex;
        align-items:center;
        justify-content:center;
        width:100%;
        min-height:35px;
        padding:8px 9px;
        border-radius:9px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:11px;
        font-weight:800;
        line-height:1.1;
    }
    .mobile-action-btn.primary{
        background:#7f56d9;
        border-color:#7f56d9;
        color:#fff;
    }
    .mobile-action-btn.disabled{
        background:#f9fafb;
        color:#98a2b3;
        border-color:#eaecf0;
        cursor:not-allowed;
    }
    .mobile-empty{
        padding:14px 12px;
        font-size:13px;
        color:#667085;
    }
    .status-banner{margin-top:12px;padding:12px;border-radius:12px;border:1px solid #d0d5dd;background:#f8fafc;color:#344054;font-size:13px}
    .status-banner.error{border-color:#fecdca;background:#fef3f2;color:#b42318}
    .status-banner.ok{border-color:#abefc6;background:#ecfdf3;color:#027a48}
    .pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:18px}
    .pc-modal.show{display:flex}
    .pc-modal-panel{width:min(900px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #e7e9f2;box-shadow:0 22px 50px rgba(16,24,40,.25)}
    .pc-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #eaecf0}
    .pc-modal-body{padding:14px 16px}
    .pc-close{border:1px solid #d0d5dd;background:#fff;border-radius:10px;padding:6px 10px;font-weight:700;cursor:pointer}
    body.pc-modal-open{overflow:hidden}
    [data-modal="hostel-add"] .pc-modal-panel{width:min(980px,100%)}
    [data-modal="agreement-history-sync"] .pc-modal-panel{width:min(1180px,100%)}
    .busy-overlay{
        position:fixed;
        inset:0;
        z-index:2600;
        background:rgba(15,23,42,.48);
        display:none;
        align-items:center;
        justify-content:center;
        padding:24px;
    }
    .busy-overlay.show{display:flex}
    .busy-overlay-card{
        width:min(420px,100%);
        border-radius:18px;
        background:#fff;
        border:1px solid #e7e9f2;
        box-shadow:0 24px 44px rgba(16,24,40,.24);
        padding:20px;
        display:grid;
        gap:12px;
        text-align:center;
    }
    .busy-spinner{
        width:42px;
        height:42px;
        border-radius:999px;
        border:3px solid #dbe3f4;
        border-top-color:#7f56d9;
        margin:0 auto;
        animation:pcBusySpin .9s linear infinite;
    }
    @keyframes pcBusySpin{to{transform:rotate(360deg)}}
    .agreement-sync-shell{display:grid;gap:16px}
    .agreement-sync-hero{
        display:grid;
        grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);
        gap:14px;
        align-items:stretch;
    }
    .agreement-sync-lead,
    .agreement-sync-side{
        border:1px solid #e7e9f2;
        border-radius:18px;
        background:linear-gradient(180deg,#fcfcff 0%,#fff 100%);
        padding:18px;
        box-shadow:0 10px 22px rgba(16,24,40,.06);
    }
    .agreement-sync-eyebrow{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        background:#eef4ff;
        color:#175cd3;
        font-size:11px;
        font-weight:900;
        letter-spacing:.06em;
        text-transform:uppercase;
    }
    .agreement-sync-title{
        margin:10px 0 0;
        font-size:22px;
        line-height:1.2;
        font-weight:900;
        color:#101828;
    }
    .agreement-sync-copy{
        margin-top:10px;
        font-size:13px;
        line-height:1.7;
        color:#667085;
        max-width:70ch;
    }
    .agreement-sync-stats{
        margin-top:14px;
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
    }
    .agreement-stat{
        border:1px solid #dbe3f4;
        border-radius:14px;
        background:#fff;
        padding:12px;
    }
    .agreement-stat-label{
        font-size:11px;
        color:#667085;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.06em;
    }
    .agreement-stat-value{
        margin-top:6px;
        font-size:20px;
        font-weight:900;
        color:#101828;
    }
    .agreement-sync-side h4{margin:0;font-size:14px}
    .agreement-sync-side p{margin:8px 0 0;font-size:13px;line-height:1.6;color:#667085}
    .agreement-sync-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:14px}
    .agreement-sync-list{
        border:1px solid #e7e9f2;
        border-radius:18px;
        background:#fff;
        overflow:hidden;
    }
    .agreement-sync-list-head{
        padding:14px 16px;
        border-bottom:1px solid #eaecf0;
        display:flex;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        align-items:center;
        background:#fcfcfd;
    }
    .agreement-sync-toolbar{
        padding:12px 16px;
        border-bottom:1px solid #eef2f6;
        display:flex;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        align-items:center;
        background:#fff;
    }
    .agreement-selection-pills{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .agreement-selection-pill{
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
    .agreement-sync-toolbar-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
    }
    .agreement-sync-table-wrap{overflow:auto}
    .agreement-sync-table{width:100%;border-collapse:collapse}
    .agreement-sync-table th,.agreement-sync-table td{
        padding:12px 14px;
        border-bottom:1px solid #eef2f6;
        vertical-align:top;
        font-size:13px;
    }
    .agreement-sync-table th{
        font-size:11px;
        letter-spacing:.06em;
        text-transform:uppercase;
        color:#667085;
        background:#fcfcfd;
    }
    .agreement-sync-table tbody tr.is-picked{
        background:#f7faff;
        box-shadow:inset 4px 0 0 #175cd3;
    }
    .agreement-sync-table tbody tr:hover{background:#fbfcff}
    .agreement-hostel-name{font-weight:900;color:#101828}
    .agreement-reason{margin-top:6px;font-size:12px;color:#667085;line-height:1.6}
    .agreement-chip{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:5px 10px;
        border-radius:999px;
        border:1px solid #d0d5dd;
        background:#fff;
        font-size:11px;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.05em;
        color:#344054;
    }
    .agreement-chip.suggested{background:#eef4ff;border-color:#b2ddff;color:#175cd3}
    .agreement-detail-list{display:grid;gap:6px}
    .agreement-detail-line{font-size:12px;color:#475467;line-height:1.5}
    .agreement-detail-line strong{color:#101828}
    .agreement-select-cell{width:48px}
    .agreement-inline-actions{
        margin-top:10px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .agreement-inline-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:34px;
        padding:7px 10px;
        border-radius:10px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        font-size:12px;
        font-weight:800;
        cursor:pointer;
    }
    .agreement-inline-btn:hover{background:#f9fafb}
    .agreement-inline-btn.primary{
        background:#175cd3;
        border-color:#175cd3;
        color:#fff;
    }
    .agreement-empty{
        padding:28px 18px;
        text-align:center;
        color:#667085;
        font-size:13px;
        line-height:1.7;
    }
    @media(max-width:980px){
        .agreement-sync-hero,
        .agreement-sync-stats{grid-template-columns:1fr}
    }
    .hostel-create-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .hostel-create-form > .pc-field.full,
    .hostel-create-form > .source-panel,
    .hostel-create-form > .pc-actions{grid-column:1 / -1}
    .hostel-create-mode{
        border:1px solid #e4e7ec;
        border-radius:16px;
        padding:16px;
        background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
    }
    .hostel-create-mode .pc-help{max-width:72ch}
    .source-panel{
        grid-column:1 / -1;
        display:grid;
        grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);
        gap:16px;
        align-items:start;
        padding:16px;
        border:1px solid #e4e7ec;
        border-radius:16px;
        background:#fcfcfd;
    }
    .source-panel .pc-field.full{grid-column:auto;margin:0}
    .hostel-source-side{
        display:grid;
        gap:12px;
        align-self:stretch;
    }
    .hostel-source-note{
        font-size:12px;
        color:#667085;
        line-height:1.6;
    }
    .hostel-create-form .pc-actions{
        margin-top:4px;
        padding-top:12px;
        border-top:1px solid #eaecf0;
    }
    .hostel-create-form .ont-preview{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .hostel-create-form .ont-meta{
        min-height:82px;
        padding:12px;
    }
    .hostel-create-form .pc-input,
    .hostel-create-form .pc-select,
    .hostel-create-form .ont-smart-trigger{
        min-height:44px;
    }
    .hostel-create-form .inline-search-shell,
    .hostel-create-form .ont-smart{width:100%}
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
    .ont-preview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .ont-meta{border:1px solid #eaecf0;background:#fcfcfd;border-radius:12px;padding:10px}
    .ont-meta .label{font-size:11px;letter-spacing:.04em;font-weight:800;color:#667085;text-transform:uppercase}
    .ont-meta .value{margin-top:4px;font-size:14px;font-weight:800;color:#101828}
    .source-panel[hidden]{display:none !important}
    .inline-search-shell{position:relative}
    .inline-search-menu{position:absolute;z-index:40;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #d0d5dd;border-radius:12px;box-shadow:0 16px 30px rgba(16,24,40,.12);overflow:hidden}
    .inline-search-item{width:100%;border:none;background:#fff;text-align:left;padding:10px 12px;font-size:13px;color:#101828;cursor:pointer}
    .inline-search-item:hover{background:#eef4ff}
    .inline-search-title{font-weight:800;color:#101828}
    .inline-search-meta{margin-top:4px;display:flex;flex-wrap:wrap;gap:8px;font-size:12px;color:#667085}
    .source-mode-note{margin-top:8px;font-size:12px;color:#667085}
    .hostel-context-row{margin-top:4px;display:flex;flex-wrap:wrap;gap:6px;align-items:center}
    .tag-soft{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:3px 8px;
        border-radius:999px;
        font-size:11px;
        font-weight:800;
        border:1px solid #d0d5dd;
        background:#f8fafc;
        color:#344054;
    }
    .mode-choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .mode-choice{position:relative;display:block}
    .mode-choice input{
        position:absolute;
        top:14px;
        left:14px;
        width:20px;
        height:20px;
        margin:0;
        opacity:1;
        accent-color:#1849a9;
        cursor:pointer;
        z-index:2;
    }
    .mode-choice-card{
        display:grid;
        gap:6px;
        min-height:94px;
        padding:14px 14px 14px 46px;
        border:1px solid #d0d5dd;
        border-radius:16px;
        background:#fff;
        cursor:pointer;
        transition:.16s ease;
    }
    .mode-choice-card strong{font-size:14px;color:#101828}
    .mode-choice-card span{font-size:12px;line-height:1.6;color:#667085}
    .mode-choice input:checked + .mode-choice-card{
        border-color:#1849a9;
        background:#eef6ff;
        box-shadow:0 0 0 3px rgba(24,73,169,.12);
    }

    @media(max-width:900px){
        .btn,.btn2{padding:7px 10px;font-size:12px;border-radius:8px}
        .btn2-slim,.actions-trigger{padding:4px 7px;font-size:10px;min-height:26px}
        .ont-preview{grid-template-columns:1fr}
        .hostel-create-form{grid-template-columns:1fr}
        .mode-choice-grid{grid-template-columns:1fr}
        .source-panel{grid-template-columns:1fr}
        .source-panel .pc-field.full{grid-column:1 / -1}
        .stat-grid{
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:8px;
        }
        .stat-card{padding:9px;border-radius:11px}
        .stat-label{font-size:10px}
        .stat-value{font-size:16px;margin-top:2px}
        .stat-sub{font-size:10px;margin-top:4px}
    }

    @media(max-width:520px){
        .rem-grid{grid-template-columns:repeat(auto-fit, minmax(150px, 1fr))}
    }

    @media(max-width:760px){
        .table-wrap{display:none}
        .mobile-hostels{display:block}
        .rem-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:8px;
        }
        .rem-card{
            min-height:86px;
            padding:10px;
        }
        .rem-count{font-size:18px}
        .rem-hint{font-size:11px}
    }
    @media(max-width:420px){
        .btn,.btn2{padding:6px 8px;font-size:11px}
        .btn2-slim,.actions-trigger{padding:4px 6px;font-size:10px;min-height:24px}
        .stat-grid{gap:6px}
        .stat-card{padding:7px}
        .stat-value{font-size:14px}
        .stat-sub{display:none}
        .rem-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        .mobile-kv{grid-template-columns:1fr}
        .mobile-actions{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    // Total hostels (unfiltered) - safe if controller didn't provide it:
    // If you want true total even when filtered, pass $totalHostels from controller.
    $canAddHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.create_hostel');
    $canEditHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel');
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment');
    $filteredCount = isset($hostels)
        ? (method_exists($hostels, 'total') ? $hostels->total() : $hostels->count())
        : 0;
    $shownCount = isset($hostels) ? $hostels->count() : 0;
    $totalCount = isset($totalHostels) ? (int)$totalHostels : $filteredCount;
    $currentPerPage = isset($perPage) ? (int)$perPage : 25;
    $currentSortDue = $sortDue ?? 'asc';
    $currentDueFilter = $dueFilter ?? 'all';
    $pageSizes = $perPageOptions ?? [15, 25, 30, 50, 100];
    if ($currentDueFilter === 'due_in_days') {
        $currentDueFilter = 'all';
    }

    $hasSearch = !empty($q);
    $exportBase = [
        'q' => $q ?? '',
        'sort_due' => $currentSortDue,
        'due_filter' => $currentDueFilter,
    ];
    $ontHostels = (array) ($ontCatalog['hostels'] ?? []);
    $ontAvailable = (bool) ($ontCatalog['available'] ?? false);
    $ontMessage = (string) ($ontCatalog['message'] ?? '');
    $selectedOntKey = (string) old('ont_key', '');
    $selectedCreateMode = (string) old('create_mode', ($chainingSupported ?? false) ? '' : 'ont_site');
    $selectedChainParentId = (int) old('chained_from_hostel_id', 0);
    $selectedChainParentOntKey = (string) old('chained_from_ont_key', '');
    $agreementHistorySuggestions = collect($agreementHistorySuggestions ?? []);
    $agreementHistorySummary = (array) ($agreementHistorySummary ?? []);
    if ($selectedOntKey === '' && old('hostel_name')) {
        $normalizedOldHostel = strtoupper(trim((string) old('hostel_name')));
        foreach ($ontHostels as $candidate) {
            if (strtoupper(trim((string) ($candidate['hostel_name'] ?? ''))) === $normalizedOldHostel) {
                $selectedOntKey = (string) ($candidate['key'] ?? '');
                break;
            }
        }
    }
    if (($chainingSupported ?? false) === false && $selectedCreateMode === 'chained_hostel') {
        $selectedCreateMode = 'ont_site';
    }
    $modalRequest = strtolower((string) request('modal', ''));
    $oldContext = (string) old('form_context', '');
    $openModal = match ($oldContext) {
        'add_hostel_modal' => 'hostel-add',
        'agreement_history_apply' => 'agreement-history-sync',
        default => in_array($modalRequest, ['hostel-add', 'agreement-history-sync'], true) ? $modalRequest : '',
    };
@endphp

<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">Token (Hostels)</h2>
            {{-- <div class="muted">Click meter/phone → compact view to record payments. Reminders are based on last payment date.</div> --}}
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            @if($canEditHostel || $canAddHostel)
                <a class="btn2" href="{{ route('petty.tokens.index', array_merge(request()->query(), ['modal' => 'agreement-history-sync', 'agreement_scan' => 1])) }}">Batch Update Agreements</a>
            @endif
            @if($canAddHostel)
                <button class="btn" type="button" data-modal-target="hostel-add">+ Add Hostel</button>
            @endif
            @include('pettycash::partials.export_select', [
                'options' => [
                    'PDF' => route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'pdf'])),
                    'CSV' => route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'csv'])),
                    'Excel' => route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'excel'])),
                ],
            ])
        </div>
    </div>

    @if($canAddHostel)
        <div class="pc-modal" data-modal="hostel-add" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Add hostel">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Add Hostel</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if(!$ontAvailable)
                        <div class="status-banner error" style="margin-top:0">
                            {{ $ontMessage !== '' ? $ontMessage : 'ONT directory is unavailable.' }}
                        </div>
                    @endif

                    @if($errors->any() && $oldContext === 'add_hostel_modal')
                        <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif

                    <form class="pc-form hostel-create-form" method="POST" action="{{ route('petty.tokens.hostels.store') }}">
                        @csrf
                        <input type="hidden" name="form_context" value="add_hostel_modal">

                        <div class="pc-field full hostel-create-mode">
                            <label>Create Mode</label>
                            <div class="mode-choice-grid">
                                <label class="mode-choice" for="createModeModalOnt">
                                    <input type="radio" name="create_mode" id="createModeModalOnt" value="ont_site" required @checked($selectedCreateMode === 'ont_site')>
                                    <span class="mode-choice-card">
                                        <strong>From ONT Site</strong>
                                        <span>Create the hostel directly from the original ONT site record.</span>
                                    </span>
                                </label>
                                @if($chainingSupported ?? false)
                                    <label class="mode-choice" for="createModeModalChained">
                                        <input type="radio" name="create_mode" id="createModeModalChained" value="chained_hostel" required @checked($selectedCreateMode === 'chained_hostel')>
                                        <span class="mode-choice-card">
                                            <strong>Chained Hostel</strong>
                                            <span>Keep this hostel’s own details, but depend on a main site that already exists in PettyCash.</span>
                                        </span>
                                    </label>
                                @endif
                            </div>
                            <div class="pc-help">Choose exactly one source before continuing.</div>
                            @unless($chainingSupported ?? false)
                                <div class="source-mode-note">Chained hostel mode appears here after the latest hostel migrations are run.</div>
                            @endunless
                        </div>

                        <div class="source-panel" id="ontSourcePanelModal" @if($selectedCreateMode !== 'ont_site') hidden @endif>
                            <div class="pc-field full">
                                <label>ONT / Site</label>
                                <select class="pc-select ont-select-native" name="ont_key" id="ontKeyModal" data-ont-available="{{ $ontAvailable ? '1' : '0' }}" @if(!$ontAvailable || $selectedCreateMode !== 'ont_site') disabled @endif @if($selectedCreateMode === 'ont_site') required @endif>
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
                                            @selected($selectedOntKey === $optKey)
                                        >
                                            {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="ont-smart" id="ontSmartIndex" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'ont_catalog'], false) }}"></div>
                                <input type="hidden" name="hostel_name" id="hostelNameHiddenModal" value="{{ $selectedCreateMode === 'ont_site' ? old('hostel_name') : '' }}" @if($selectedCreateMode !== 'ont_site') disabled @endif>
                                <div class="hostel-source-note">Search the ONT directory and create the hostel from a real site record.</div>
                            </div>

                            <div class="pc-field full hostel-source-side">
                                <div class="ont-preview">
                                    <div class="ont-meta">
                                        <div class="label">Selected Hostel</div>
                                        <div class="value" id="selectedHostelPreviewModal">-</div>
                                    </div>
                                    <div class="ont-meta">
                                        <div class="label">Selected Site</div>
                                        <div class="value" id="selectedSitePreviewModal">-</div>
                                    </div>
                                    <div class="ont-meta">
                                        <div class="label">Site S.N</div>
                                        <div class="value" id="selectedSnPreviewModal">-</div>
                                    </div>
                                    <div class="ont-meta">
                                        <div class="label">Merge Status</div>
                                        <div class="value">
                                            <span class="ont-status-chip muted" id="selectedMergeStatusModal">Not Added</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="source-panel" id="chainSourcePanelModal" @if($selectedCreateMode !== 'chained_hostel') hidden @endif>
                            <div class="pc-field full">
                                <label for="manualHostelNameModal">Hostel Name</label>
                                <input class="pc-input" id="manualHostelNameModal" name="hostel_name" value="{{ $selectedCreateMode === 'chained_hostel' ? old('hostel_name') : '' }}" placeholder="e.g. Block B Annex" @if($selectedCreateMode !== 'chained_hostel') disabled @endif>
                                <div class="pc-help">Keep the hostel details you enter here. The site will come from the selected main ONT/site.</div>
                            </div>

                            <div class="pc-field full">
                                <label for="chainParentSearchInputModal">Chained From Main ONT/Site</label>
                                <div class="inline-search-shell" id="chainParentShellModal" data-search-url="{{ route('petty.tokens.hostels.search', ['source' => 'chain_parent'], false) }}">
                                    <input class="pc-input" type="text" id="chainParentSearchInputModal" placeholder="Search main ONT/site, site id, or site serial" value="" @if($selectedCreateMode !== 'chained_hostel') disabled @endif>
                                    <div class="inline-search-menu" id="chainParentSearchMenuModal" hidden></div>
                                </div>
                                <input type="hidden" name="chained_from_hostel_id" id="chainedFromHostelIdModal" value="{{ $selectedChainParentId > 0 ? $selectedChainParentId : '' }}">
                                <input type="hidden" name="chained_from_ont_key" id="chainedFromOntKeyModal" value="{{ $selectedChainParentOntKey }}">
                                <div class="ajax-row" id="chainParentStatusModal" aria-live="polite"></div>
                                <div class="hostel-source-note">This search now comes from the main ONT catalogue. If the main hostel is not yet in PettyCash, it will be created automatically from the selected ONT/site.</div>
                            </div>

                            <div class="pc-field full hostel-source-side">
                                <div class="ont-preview">
                                    <div class="ont-meta">
                                        <div class="label">Main Hostel</div>
                                        <div class="value" id="chainParentHostelPreviewModal">-</div>
                                    </div>
                                    <div class="ont-meta">
                                        <div class="label">Main Site</div>
                                        <div class="value" id="chainParentSitePreviewModal">-</div>
                                    </div>
                                    <div class="ont-meta">
                                        <div class="label">Site S.N</div>
                                        <div class="value" id="chainParentSnPreviewModal">-</div>
                                    </div>
                                    <div class="ont-meta">
                                        <div class="label">Routers</div>
                                        <div class="value" id="chainParentRoutersPreviewModal">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pc-field">
                            <label>Contact Person Name</label>
                            <input class="pc-input" name="contact_person" value="{{ old('contact_person') }}" required>
                        </div>

                        <div class="pc-field">
                            <label>Contact Person Number</label>
                            <input class="pc-input" name="phone_no" value="{{ old('phone_no') }}" required>
                        </div>

                        <div class="pc-field">
                            <label>No of Routers</label>
                            <input class="pc-input" id="noOfRoutersInputModal" type="number" min="1" name="no_of_routers" value="{{ old('no_of_routers', 1) }}" required>
                        </div>

                        <div class="pc-actions">
                            <button class="btn" type="submit" data-loading-label="Creating Hostel..." data-loading-copy="Saving the hostel and opening the agreement builder.">Save and Continue to Agreement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Counter cards --}}
    <div class="stat-grid">
        <div class="stat-card">
            <p class="stat-label">Total Hostels</p>
            <div class="stat-value">{{ number_format($totalCount) }}</div>
            <div class="stat-sub">All hostels</div>
        </div>

        <div class="stat-card">
            <p class="stat-label">Listed</p>
            <div class="stat-value">{{ number_format($filteredCount) }}</div>
            <div class="stat-sub">
                @if($hasSearch)
                    Filtered results (search active)
                @else
                    All listed records
                @endif
            </div>
        </div>

        <div class="stat-card">
            <p class="stat-label">Today's Date</p>
            <div class="stat-value">{{ isset($today) ? $today->format('Y-m-d') : now()->format('Y-m-d') }}</div>
            {{-- <div class="stat-sub">Snapshot date</div> --}}
        </div>
    </div>

    {{-- Reminders summary --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:end">
            <div>
                <div class="section-title">Payment Reminders</div>
                {{-- <div class="muted">Today: <strong>{{ isset($today) ? $today->format('Y-m-d') : now()->format('Y-m-d') }}</strong></div> --}}
            </div>
            {{-- <div class="mini">Due buckets: 3 days, 2 days, 1 day, due today, overdue.</div> --}}
        </div>

        @php
            $cDueToday = isset($reminders['due_today']) ? $reminders['due_today']->count() : 0;
            $c1 = isset($reminders['due_1']) ? $reminders['due_1']->count() : 0;
            $c2 = isset($reminders['due_2']) ? $reminders['due_2']->count() : 0;
            $c3 = isset($reminders['due_3']) ? $reminders['due_3']->count() : 0;
            $cOver = isset($reminders['overdue']) ? $reminders['overdue']->count() : 0;
            $cNoPay = isset($reminders['no_payments']) ? $reminders['no_payments']->count() : 0;

            $sumDueToday = isset($reminders['due_today']) ? (float) $reminders['due_today']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sum1 = isset($reminders['due_1']) ? (float) $reminders['due_1']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sum2 = isset($reminders['due_2']) ? (float) $reminders['due_2']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sum3 = isset($reminders['due_3']) ? (float) $reminders['due_3']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sumOver = isset($reminders['overdue']) ? (float) $reminders['overdue']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sumNoPay = isset($reminders['no_payments']) ? (float) $reminders['no_payments']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
        @endphp

        <div class="rem-grid" style="margin-top:12px">
            <a href="{{ route('petty.tokens.index', ['due_filter' => 'due_today'] + request()->query()) }}" style="text-decoration:none;color:inherit">
                <div class="rem-card due">
                    <p class="rem-title">Due Today</p>
                    <div class="rem-count">{{ $cDueToday }}</div>
                    <p class="rem-hint">Expected: KES {{ number_format($sumDueToday, 2) }}</p>
                </div>
            </a>
            <a href="{{ route('petty.tokens.index', ['due_filter' => 'due_tomorrow'] + request()->query()) }}" style="text-decoration:none;color:inherit">
                <div class="rem-card soon">
                    <p class="rem-title">Due Tomorrow</p>
                    <div class="rem-count">{{ $c1 }}</div>
                    <p class="rem-hint">Expected: KES {{ number_format($sum1, 2) }}</p>
                </div>
            </a>
            <a href="{{ route('petty.tokens.index', ['due_filter' => 'due_in_2_days'] + request()->query()) }}" style="text-decoration:none;color:inherit">
                <div class="rem-card soon">
                    <p class="rem-title">Due in 2 Days</p>
                    <div class="rem-count">{{ $c2 }}</div>
                    <p class="rem-hint">Expected: KES {{ number_format($sum2, 2) }}</p>
                </div>
            </a>
            <a href="{{ route('petty.tokens.index', ['due_filter' => 'due_in_3_days'] + request()->query()) }}" style="text-decoration:none;color:inherit">
                <div class="rem-card soon">
                    <p class="rem-title">Due in 3 Days</p>
                    <div class="rem-count">{{ $c3 }}</div>
                    <p class="rem-hint">Expected: KES {{ number_format($sum3, 2) }}</p>
                </div>
            </a>
            <a href="{{ route('petty.tokens.index', ['due_filter' => 'overdue'] + request()->query()) }}" style="text-decoration:none;color:inherit">
                <div class="rem-card overdue">
                    <p class="rem-title">Overdue</p>
                    <div class="rem-count">{{ $cOver }}</div>
                    <p class="rem-hint">Expected: KES {{ number_format($sumOver, 2) }}</p>
                </div>
            </a>
            <a href="{{ route('petty.tokens.index', ['due_filter' => 'no_payments'] + request()->query()) }}" style="text-decoration:none;color:inherit">
                <div class="rem-card" style="border-color:#d0d5dd">
                    <p class="rem-title">No Payments</p>
                    <div class="rem-count">{{ $cNoPay }}</div>
                    <p class="rem-hint">Expected: KES {{ number_format($sumNoPay, 2) }}</p>
                </div>
            </a>
    </div>

    {{-- Filters --}}
    @php
        $hasTokenFilter = !empty($q)
            || (int)$currentPerPage !== 25
            || $currentSortDue !== 'asc'
            || $currentDueFilter !== 'all';
    @endphp
    <div class="pc-filter-dock">
        <details class="pc-filter-panel" @if($hasTokenFilter) open @endif>
            <summary>
                <span class="pc-filter-title">Filters</span>
                <span class="pc-filter-state">{{ $hasTokenFilter ? 'active' : 'optional' }}</span>
            </summary>
            <div class="pc-filter-body">
                <form method="GET" action="{{ route('petty.tokens.index') }}" class="pc-filter-row" data-busy-form data-busy-title="Refreshing Hostels..." data-busy-copy="Applying the latest search, due-date, and hostel filters.">
                    <input class="input pc-filter-grow" type="text" name="q" value="{{ $q ?? '' }}"
                           placeholder="Search hostel, chained parent, site S.N, meter, name, or phone number">
                    <select class="input" name="due_filter" style="width:220px" onchange="this.form.submit()">
                        <option value="all" @selected($currentDueFilter === 'all')>Due Filter: All Records</option>
                        <option value="overdue" @selected($currentDueFilter === 'overdue')>Overdue</option>
                        <option value="due_today" @selected($currentDueFilter === 'due_today')>Due Today</option>
                        <option value="due_tomorrow" @selected($currentDueFilter === 'due_tomorrow')>Due Tomorrow</option>
                        <option value="due_in_2_days" @selected($currentDueFilter === 'due_in_2_days')>Due In 2 Days</option>
                        <option value="due_in_3_days" @selected($currentDueFilter === 'due_in_3_days')>Due In 3 Days</option>
                        <option value="no_payments" @selected($currentDueFilter === 'no_payments')>No Payments Yet</option>
                        <option value="terminated" @selected($currentDueFilter === 'terminated')>Agreement Terminated</option>
                    </select>
                    <select class="input" name="sort_due" style="width:190px" onchange="this.form.submit()">
                        <option value="asc" @selected($currentSortDue === 'asc')>Due Date: Earliest First</option>
                        <option value="desc" @selected($currentSortDue === 'desc')>Due Date: Latest First</option>
                    </select>
                    <select class="input" name="per_page" style="width:140px" onchange="this.form.submit()">
                        @foreach($pageSizes as $size)
                            <option value="{{ $size }}" @selected((int)$currentPerPage === (int)$size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <div class="right">
                        <button class="btn2" type="submit">Search</button>
                        @if($hasTokenFilter)
                            <a class="btn2" href="{{ route('petty.tokens.index') }}">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </details>
    </div>

    @if($canEditHostel || $canAddHostel)
        <div class="pc-modal" data-modal="agreement-history-sync" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Batch update agreements from history">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Batch Update Agreements From History</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    <div class="agreement-sync-shell">
                        <div class="agreement-sync-hero">
                            <div class="agreement-sync-lead">
                                <span class="agreement-sync-eyebrow">Automated History Check</span>
                                <div class="agreement-sync-title">Detect token, send money, and package setups from past payments.</div>
                                <div class="agreement-sync-copy">
                                    This scan looks through existing payment history and linked token spendings, then prepares likely agreement corrections for review. You can approve one or many at once. Billing cycle and amount due are left untouched here.
                                </div>
                                <div class="agreement-sync-stats">
                                    <div class="agreement-stat">
                                        <div class="agreement-stat-label">Hostels Scanned</div>
                                        <div class="agreement-stat-value">{{ number_format((int) ($agreementHistorySummary['scanned'] ?? 0)) }}</div>
                                    </div>
                                    <div class="agreement-stat">
                                        <div class="agreement-stat-label">Suggestions Ready</div>
                                        <div class="agreement-stat-value">{{ number_format((int) ($agreementHistorySummary['suggested'] ?? 0)) }}</div>
                                    </div>
                                    <div class="agreement-stat">
                                        <div class="agreement-stat-label">Token / Send Money</div>
                                        <div class="agreement-stat-value">{{ number_format((int) ($agreementHistorySummary['token'] ?? 0)) }} / {{ number_format((int) ($agreementHistorySummary['send_money'] ?? 0)) }}</div>
                                    </div>
                                    <div class="agreement-stat">
                                        <div class="agreement-stat-label">Package</div>
                                        <div class="agreement-stat-value">{{ number_format((int) ($agreementHistorySummary['package'] ?? 0)) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="agreement-sync-side">
                                <h4>Workflow</h4>
                                <p>Run the check, review the evidence, keep the right rows selected, then apply them. This is meant to clean up hostels that already have payment history but still show the wrong agreement setup.</p>
                                <div class="agreement-sync-actions">
                                    <a class="btn2" href="{{ route('petty.tokens.index', array_merge(request()->query(), ['modal' => 'agreement-history-sync', 'agreement_scan' => 1])) }}" data-busy-trigger data-busy-title="Refreshing Agreement Scan..." data-busy-copy="Reviewing previous payments and rebuilding agreement suggestions.">Refresh Check</a>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('petty.tokens.agreements.apply_history') }}" data-busy-form data-busy-title="Applying Agreement Updates..." data-busy-copy="Saving the selected agreement suggestions to the hostel records.">
                            @csrf
                            <input type="hidden" name="form_context" value="agreement_history_apply">
                            <input type="hidden" name="single_apply_hostel_id" id="singleAgreementApplyHostelId" value="">

                            <div class="agreement-sync-list">
                                <div class="agreement-sync-list-head">
                                    <div>
                                        <div style="font-weight:900;color:#101828">Agreement suggestions</div>
                                        <div class="muted">Select the rows you trust, then apply them in one pass.</div>
                                    </div>
                                </div>

                                @if($agreementHistorySuggestions->isNotEmpty())
                                    <div class="agreement-sync-toolbar">
                                        <div class="agreement-selection-pills">
                                            <span class="agreement-selection-pill"><span id="agreementSelectedCount">{{ $agreementHistorySuggestions->count() }}</span> selected</span>
                                            <span class="agreement-selection-pill">{{ number_format($agreementHistorySuggestions->count()) }} suggestions loaded</span>
                                        </div>
                                        <div class="agreement-sync-toolbar-actions">
                                            <button class="btn2" type="button" id="agreementSelectAllBtn">Select All</button>
                                            <button class="btn2" type="button" id="agreementClearAllBtn">Clear</button>
                                            <button class="btn" type="submit" data-loading-label="Applying Agreement Updates..." data-loading-copy="Saving the selected agreement suggestions to hostel records.">Apply Selected Updates</button>
                                        </div>
                                    </div>
                                @endif

                                @if($agreementHistorySuggestions->isEmpty())
                                    <div class="agreement-empty">
                                        No agreement corrections are pending right now. If you just recorded more history, run the refresh check again and the suggestions will rebuild here.
                                    </div>
                                @else
                                    <div class="agreement-sync-table-wrap">
                                        <table class="agreement-sync-table">
                                            <thead>
                                            <tr>
                                                <th class="agreement-select-cell">
                                                    <input type="checkbox" id="agreementSelectAllCheckbox" checked>
                                                </th>
                                                <th>Hostel</th>
                                                <th>Current</th>
                                                <th>Suggested</th>
                                                <th>Evidence</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($agreementHistorySuggestions as $suggestionRow)
                                                @php
                                                    $suggestedHostel = $suggestionRow['hostel'];
                                                    $currentAgreement = (array) ($suggestionRow['current'] ?? []);
                                                    $suggestedAgreement = (array) ($suggestionRow['suggested'] ?? []);
                                                    $suggestedType = strtolower(trim((string) ($suggestedAgreement['agreement_type'] ?? 'none')));
                                                    $currentType = strtolower(trim((string) ($currentAgreement['agreement_type'] ?? 'none')));
                                                @endphp
                                                <tr>
                                                    <td class="agreement-select-cell">
                                                        <input type="checkbox" name="selected_hostels[]" value="{{ $suggestedHostel->id }}" data-agreement-select checked>
                                                        <input type="hidden" name="suggestions[{{ $suggestedHostel->id }}][agreement_type]" value="{{ $suggestedAgreement['agreement_type'] ?? 'none' }}">
                                                        <input type="hidden" name="suggestions[{{ $suggestedHostel->id }}][agreement_label]" value="{{ $suggestedAgreement['agreement_label'] ?? '' }}">
                                                        <input type="hidden" name="suggestions[{{ $suggestedHostel->id }}][meter_no]" value="{{ $suggestedAgreement['meter_no'] ?? '' }}">
                                                        <input type="hidden" name="suggestions[{{ $suggestedHostel->id }}][phone_no]" value="{{ $suggestedAgreement['phone_no'] ?? '' }}">
                                                        <input type="hidden" name="suggestions[{{ $suggestedHostel->id }}][contact_person]" value="{{ $suggestedAgreement['contact_person'] ?? '' }}">
                                                    </td>
                                                    <td>
                                                        <div class="agreement-hostel-name">{{ $suggestedHostel->hostel_name }}</div>
                                                        <div class="agreement-reason">Hostel #{{ $suggestedHostel->id }} @if(trim((string) ($suggestedHostel->phone_no ?? '')) !== '') • {{ $suggestedHostel->phone_no }} @endif</div>
                                                    </td>
                                                    <td>
                                                        <div class="agreement-detail-list">
                                                            <div><span class="agreement-chip">{{ $currentType === 'none' ? 'No agreement' : str_replace('_', ' ', $currentType) }}</span></div>
                                                            @if(trim((string) ($currentAgreement['meter_no'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Meter:</strong> {{ $currentAgreement['meter_no'] }}</div>
                                                            @endif
                                                            @if(trim((string) ($currentAgreement['phone_no'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Phone:</strong> {{ $currentAgreement['phone_no'] }}</div>
                                                            @endif
                                                            @if(trim((string) ($currentAgreement['agreement_label'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Label:</strong> {{ $currentAgreement['agreement_label'] }}</div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="agreement-detail-list">
                                                            <div><span class="agreement-chip suggested">{{ str_replace('_', ' ', $suggestedType) }}</span></div>
                                                            @if(trim((string) ($suggestedAgreement['meter_no'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Meter:</strong> {{ $suggestedAgreement['meter_no'] }}</div>
                                                            @endif
                                                            @if(trim((string) ($suggestedAgreement['phone_no'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Phone:</strong> {{ $suggestedAgreement['phone_no'] }}</div>
                                                            @endif
                                                            @if(trim((string) ($suggestedAgreement['contact_person'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Name:</strong> {{ $suggestedAgreement['contact_person'] }}</div>
                                                            @endif
                                                            @if(trim((string) ($suggestedAgreement['agreement_label'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Label:</strong> {{ $suggestedAgreement['agreement_label'] }}</div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="agreement-detail-list">
                                                            <div class="agreement-detail-line"><strong>Why:</strong> {{ $suggestedAgreement['reason'] ?? 'Previous payment history found.' }}</div>
                                                            @if(trim((string) ($suggestedAgreement['evidence_reference'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Reference:</strong> {{ $suggestedAgreement['evidence_reference'] }}</div>
                                                            @endif
                                                            @if(trim((string) ($suggestedAgreement['evidence_date'] ?? '')) !== '')
                                                                <div class="agreement-detail-line"><strong>Date:</strong> {{ $suggestedAgreement['evidence_date'] }}</div>
                                                            @endif
                                                        </div>
                                                        <div class="agreement-inline-actions">
                                                            <button class="agreement-inline-btn primary" type="button" data-apply-one="{{ $suggestedHostel->id }}">Approve This One</button>
                                                            <a class="agreement-inline-btn" href="{{ route('petty.tokens.hostels.show', ['hostel' => $suggestedHostel->id]) }}">Open Hostel</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="table-wrap table-wrap-hostels">
        <table>
            <thead>
            <tr>
                <th>Hostel</th>
                <th class="col-site-sn">Site S.N</th>
                <th>Meter No</th>
                <th>Name</th>
                <th>Phone Number</th>
                <th class="col-routers">Routers</th>
                <th>Billing Cycle</th>
                <th>Amount Due</th>
                <th>Last Payment</th>
                <th>Next Due</th>
                <th>Status</th>
                @if($canEditHostel)
                    <th class="col-actions">Management</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($hostels as $h)
                @php
                    $status = $h->due_status ?? 'unknown';
                    $badge = $h->due_badge ?? '—';
                    $nextDue = $h->next_due_date ?? null;
                    $agreementType = strtolower(trim((string) ($h->agreement_type ?? 'none')));
                    if (!in_array($agreementType, ['token', 'send_money', 'package', 'none'], true)) {
                        $agreementType = 'none';
                    }
                    $agreementConfigured = $agreementType !== 'none'
                        || trim((string) ($h->agreement_label ?? '')) !== '';
                    $agreementActionLabel = $agreementConfigured ? 'Update Agreement' : 'Set Agreement';
                    $canMarkOverpay = $canRecordPayment && $agreementType !== 'package' && !empty($h->last_payment_date);
                    $agreementTerminated = !empty($h->agreement_terminated_at);

                    $badgeClass = 'b-unknown';
                    if($status === 'overdue') $badgeClass = 'b-overdue';
                    elseif($status === 'due_today') $badgeClass = 'b-due';
                    elseif($status === 'upcoming') $badgeClass = 'b-upcoming';
                @endphp

                <tr>
                    <td data-label="Hostel">
                        <a class="hostel-link" href="{{ route('petty.tokens.hostels.show', $h->id) }}">{{ $h->hostel_name }}</a>
                        <div class="muted">
                            ID: {{ $h->id }}
                            @if((int) ($h->merged_child_count ?? 0) > 0)
                                • {{ (int) ($h->merged_child_count ?? 0) }} child hostel{{ (int) ($h->merged_child_count ?? 0) === 1 ? '' : 's' }}
                            @endif
                        </div>
                        @if(!empty($h->is_chained) || !empty($h->chained_from_name))
                            <div class="hostel-context-row">
                                <span class="tag-soft">Chained Hostel</span>
                                @if(!empty($h->chained_from_name))
                                    <span class="muted">From {{ $h->chained_from_name }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td data-label="Site S.N" class="col-site-sn">
                        <span class="site-sn-text">{{ $h->ont_site_sn ?: '-' }}</span>
                    </td>
                    <td data-label="Meter No">
                        {{ $h->meter_no ?? '-' }}
                    </td>
                    <td data-label="Name">
                        {{ $h->contact_person ?? '-' }}
                    </td>
                    <td data-label="Phone Number">
                        {{ $h->phone_no ?? '-' }}
                    </td>
                    <td data-label="Routers" class="col-routers">
                        <strong>{{ (int) ($h->family_router_total ?? $h->no_of_routers) }}</strong>
                        @if((int) ($h->merged_child_count ?? 0) > 0)
                            <div class="muted">Parent {{ (int) ($h->no_of_routers ?? 0) }} + children {{ max(0, (int) ($h->family_router_total ?? 0) - (int) ($h->no_of_routers ?? 0)) }}</div>
                        @endif
                    </td>
                    <td data-label="Billing Cycle"><span class="pill">{{ strtoupper($h->stake) }}</span></td>
                    <td data-label="Amount Due">{{ number_format((float)$h->amount_due, 2) }}</td>
                    <td data-label="Last Payment">
                        @if($h->last_payment_amount !== null)
                            <span class="pill">{{ number_format((float)$h->last_payment_amount, 2) }}</span>
                            <div class="muted">{{ $h->last_payment_date }}</div>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td data-label="Next Due">
                        @if($nextDue)
                            <span class="pill">{{ $nextDue }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
                    </td>
                    @if($canEditHostel)
                        <td data-label="Management" class="col-actions">
                            <details class="action-menu">
                                <summary class="btn2 btn2-slim actions-trigger">Actions ▾</summary>
                                <div class="action-menu-list">
                                    <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id]) }}">Open Hostel</a>
                                    @if($canRecordPayment && (float) ($h->amount_due ?? 0) > 0 && !empty($h->meter_no))
                                        <form method="POST" action="{{ route('petty.tokens.hostels.gateway_pay', ['hostel' => $h->id]) }}" style="margin:0">
                                            @csrf
                                            <input type="hidden" name="payment_type" value="prepaid">
                                            <button type="submit" class="action-menu-item is-highlight">Pay With Gateway</button>
                                        </form>
                                    @endif
                                    <a class="action-menu-item is-highlight" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $h->id]) }}">{{ $agreementActionLabel }}</a>
                                    @if((bool) ($h->ont_merged ?? false))
                                        @if(trim((string) ($h->ont_site_sn ?? '')) === '')
                                            <form method="POST" action="{{ route('petty.tokens.hostels.refresh_ont_sn', ['hostel' => $h->id]) }}" style="margin:0">
                                                @csrf
                                                <button type="submit" class="action-menu-item" title="Refresh Site S.N from ONT">&#8635; Refresh Site S.N</button>
                                            </form>
                                        @endif
                                        <span class="action-menu-item is-disabled" aria-disabled="true">Merged</span>
                                    @else
                                        <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'hostel-merge']) }}">Update from ONT</a>
                                    @endif
                                </div>
                            </details>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $canEditHostel ? 12 : 11 }}" class="muted" style="padding:16px">No hostels yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mobile-hostels">
        <div class="mobile-hostels-shell">
            @forelse($hostels as $h)
                @php
                    $status = $h->due_status ?? 'unknown';
                    $badge = $h->due_badge ?? '—';
                    $nextDue = $h->next_due_date ?? null;
                    $agreementType = strtolower(trim((string) ($h->agreement_type ?? 'none')));
                    if (!in_array($agreementType, ['token', 'send_money', 'package', 'none'], true)) {
                        $agreementType = 'none';
                    }
                    $agreementConfigured = $agreementType !== 'none'
                        || trim((string) ($h->agreement_label ?? '')) !== '';
                    $agreementActionLabel = $agreementConfigured ? 'Update Agreement' : 'Set Agreement';
                    $canMarkOverpay = $canRecordPayment && $agreementType !== 'package' && !empty($h->last_payment_date);
                    $agreementTerminated = !empty($h->agreement_terminated_at);

                    $badgeClass = 'b-unknown';
                    if($status === 'overdue') $badgeClass = 'b-overdue';
                    elseif($status === 'due_today') $badgeClass = 'b-due';
                    elseif($status === 'upcoming') $badgeClass = 'b-upcoming';
                @endphp

                <details class="mobile-hostel-card">
                    <summary class="mobile-hostel-summary">
                        <span class="mobile-chevron">▸</span>
                        <div>
                            <div class="mobile-hostel-title">
                                <a class="hostel-link mobile-hostel-link" href="{{ route('petty.tokens.hostels.show', $h->id) }}">{{ $h->hostel_name }}</a>
                            </div>
                            <div class="mobile-hostel-sub">
                                ID: {{ $h->id }}
                                @if(trim((string) ($h->ont_site_sn ?? '')) !== '')
                                    • S.N {{ $h->ont_site_sn }}
                                @endif
                                @if((int) ($h->merged_child_count ?? 0) > 0)
                                    • {{ (int) ($h->merged_child_count ?? 0) }} child hostel{{ (int) ($h->merged_child_count ?? 0) === 1 ? '' : 's' }}
                                @endif
                            </div>
                            @if(!empty($h->is_chained) || !empty($h->chained_from_name))
                                <div class="hostel-context-row">
                                    <span class="tag-soft">Chained Hostel</span>
                                    @if(!empty($h->chained_from_name))
                                        <span class="muted">From {{ $h->chained_from_name }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="mobile-hostel-right">
                            <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
                            <div class="mobile-hostel-amount">KES {{ number_format((float)$h->amount_due, 2) }}</div>
                        </div>
                    </summary>
                    <div class="mobile-hostel-extra">
                        <div class="mobile-kv">
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Meter No</div>
                                <div class="mobile-kv-value">{{ $h->meter_no ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Name</div>
                                <div class="mobile-kv-value">{{ $h->contact_person ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Phone Number</div>
                                <div class="mobile-kv-value">{{ $h->phone_no ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Routers</div>
                                <div class="mobile-kv-value">
                                    {{ (int) ($h->family_router_total ?? $h->no_of_routers) }}
                                    @if((int) ($h->merged_child_count ?? 0) > 0)
                                        <div class="muted">Parent {{ (int) ($h->no_of_routers ?? 0) }} + children {{ max(0, (int) ($h->family_router_total ?? 0) - (int) ($h->no_of_routers ?? 0)) }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Billing Cycle</div>
                                <div class="mobile-kv-value">{{ strtoupper((string) $h->stake) }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Next Due</div>
                                <div class="mobile-kv-value">{{ $nextDue ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Last Payment</div>
                                <div class="mobile-kv-value">
                                    @if($h->last_payment_amount !== null)
                                        {{ number_format((float)$h->last_payment_amount, 2) }} ({{ $h->last_payment_date }})
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mobile-actions">
                            <a class="mobile-action-btn primary" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id]) }}">Open Hostel</a>
                            @if($canRecordPayment && (float) ($h->amount_due ?? 0) > 0 && !empty($h->meter_no))
                                <form method="POST" action="{{ route('petty.tokens.hostels.gateway_pay', ['hostel' => $h->id]) }}" style="margin:0">
                                    @csrf
                                    <input type="hidden" name="payment_type" value="prepaid">
                                    <button class="mobile-action-btn" type="submit">Pay With Gateway</button>
                                </form>
                            @endif
                            @if($canEditHostel)
                                <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $h->id]) }}">{{ $agreementActionLabel }}</a>
                                @if((bool) ($h->ont_merged ?? false))
                                    @if(trim((string) ($h->ont_site_sn ?? '')) === '')
                                        <form method="POST" action="{{ route('petty.tokens.hostels.refresh_ont_sn', ['hostel' => $h->id]) }}" style="margin:0">
                                            @csrf
                                            <button class="mobile-action-btn" type="submit">&#8635; Refresh Site S.N</button>
                                        </form>
                                    @endif
                                    <span class="mobile-action-btn disabled" aria-disabled="true">Merged</span>
                                @else
                                    <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'hostel-merge']) }}">Update from ONT</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </details>
            @empty
                <div class="mobile-empty">No hostels yet.</div>
            @endforelse
        </div>
    </div>

    <div class="muted footer-note">
        Showing {{ number_format($shownCount) }} of {{ number_format($filteredCount) }} hostel{{ $filteredCount === 1 ? '' : 's' }}.
        Page size: <strong>{{ $currentPerPage }}</strong>.
    </div>

    @if(method_exists($hostels, 'links'))
        @php
            $current = $hostels->currentPage();
            $last = $hostels->lastPage();
            $start = max(1, $current - 2);
            $end = min($last, $current + 2);
        @endphp
        <div class="pager">
            <div class="pager-meta">
                Page <strong>{{ $current }}</strong> of <strong>{{ $last }}</strong>
            </div>

            <div class="pager-nav">
                @if($hostels->onFirstPage())
                    <span class="pg-btn disabled">Previous</span>
                @else
                    <a class="pg-btn" href="{{ $hostels->previousPageUrl() }}" rel="prev">Previous</a>
                @endif

                @if($start > 1)
                    <a class="pg-btn" href="{{ $hostels->url(1) }}">1</a>
                    @if($start > 2)
                        <span class="pg-btn disabled">...</span>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if($page === $current)
                        <span class="pg-btn active">{{ $page }}</span>
                    @else
                        <a class="pg-btn" href="{{ $hostels->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="pg-btn disabled">...</span>
                    @endif
                    <a class="pg-btn" href="{{ $hostels->url($last) }}">{{ $last }}</a>
                @endif

                @if($hostels->hasMorePages())
                    <a class="pg-btn" href="{{ $hostels->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pg-btn disabled">Next</span>
                @endif
            </div>
        </div>
    @endif

    <div class="busy-overlay" id="pageBusyOverlay" aria-hidden="true">
        <div class="busy-overlay-card">
            <div class="busy-spinner" aria-hidden="true"></div>
            <div style="font-size:18px;font-weight:900;color:#101828" id="pageBusyTitle">Working...</div>
            <div class="muted" style="font-size:13px;line-height:1.7" id="pageBusyCopy">Please wait while the latest token hostel updates are prepared.</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const body = document.body;
    const modals = Array.from(document.querySelectorAll('[data-modal]'));
    const autoOpenModal = @json($openModal);
    const busyOverlay = document.getElementById('pageBusyOverlay');
    const busyTitle = document.getElementById('pageBusyTitle');
    const busyCopy = document.getElementById('pageBusyCopy');
    const agreementSelectAllCheckbox = document.getElementById('agreementSelectAllCheckbox');
    const agreementSelectAllButtons = Array.from(document.querySelectorAll('#agreementSelectAllBtn'));
    const agreementClearAllButtons = Array.from(document.querySelectorAll('#agreementClearAllBtn'));
    const agreementSelectedCount = document.getElementById('agreementSelectedCount');
    const singleAgreementApplyHostelId = document.getElementById('singleAgreementApplyHostelId');
    const agreementApplyOneButtons = Array.from(document.querySelectorAll('[data-apply-one]'));
    const agreementApplyForm = document.querySelector('form[action="{{ route('petty.tokens.agreements.apply_history') }}"]');
    const agreementRowCheckboxes = Array.from(document.querySelectorAll('[data-agreement-select]'));

    function showBusyOverlay(title, copy) {
        if (!busyOverlay) return;
        if (busyTitle) busyTitle.textContent = title || 'Working...';
        if (busyCopy) busyCopy.textContent = copy || 'Please wait while the page finishes this task.';
        busyOverlay.classList.add('show');
        busyOverlay.setAttribute('aria-hidden', 'false');
    }

    function syncAgreementSelection() {
        if (!agreementSelectAllCheckbox) return;
        const checkedCount = agreementRowCheckboxes.filter((input) => input.checked).length;
        agreementSelectAllCheckbox.checked = checkedCount > 0 && checkedCount === agreementRowCheckboxes.length;
        agreementSelectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < agreementRowCheckboxes.length;
        if (agreementSelectedCount) {
            agreementSelectedCount.textContent = String(checkedCount);
        }
        agreementRowCheckboxes.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.toggle('is-picked', checkbox.checked);
            }
        });
    }

    function getModal(id) {
        return document.querySelector('[data-modal="' + id + '"]');
    }

    function openModal(id) {
        const modal = getModal(id);
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('pc-modal-open');

        if (id === 'hostel-add') {
            window.setTimeout(() => {
                if (typeof window.pettySyncHostelCreateMode === 'function') {
                    window.pettySyncHostelCreateMode(true);
                }
            }, 30);
        }
    }

    function closeModal(modal) {
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

    document.querySelectorAll('[data-busy-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            showBusyOverlay(
                trigger.getAttribute('data-busy-title') || 'Working...',
                trigger.getAttribute('data-busy-copy') || 'Please wait while the latest updates are prepared.'
            );
        });
    });

    document.querySelectorAll('form[data-busy-form]').forEach((form) => {
        form.addEventListener('submit', function () {
            showBusyOverlay(
                form.getAttribute('data-busy-title') || 'Working...',
                form.getAttribute('data-busy-copy') || 'Please wait while this update is being applied.'
            );
        });
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

    const mobileRows = Array.from(document.querySelectorAll('.mobile-hostels .mobile-hostel-card'));
    mobileRows.forEach(function (row) {
        row.addEventListener('toggle', function () {
            if (!row.open) return;
            mobileRows.forEach(function (other) {
                if (other !== row) other.removeAttribute('open');
            });
        });
    });

    document.querySelectorAll('.mobile-hostel-link').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    agreementRowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncAgreementSelection);
    });

    if (agreementSelectAllCheckbox) {
        agreementSelectAllCheckbox.addEventListener('change', function () {
            agreementRowCheckboxes.forEach((checkbox) => {
                checkbox.checked = agreementSelectAllCheckbox.checked;
            });
            syncAgreementSelection();
        });
    }

    agreementSelectAllButtons.forEach((button) => {
        button.addEventListener('click', function () {
            agreementRowCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });
            syncAgreementSelection();
        });
    });

    agreementClearAllButtons.forEach((button) => {
        button.addEventListener('click', function () {
            agreementRowCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncAgreementSelection();
        });
    });

    agreementApplyOneButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const hostelId = String(button.getAttribute('data-apply-one') || '').trim();
            if (!hostelId || !agreementApplyForm) return;
            agreementRowCheckboxes.forEach((checkbox) => {
                checkbox.checked = String(checkbox.value) === hostelId;
            });
            if (singleAgreementApplyHostelId) {
                singleAgreementApplyHostelId.value = hostelId;
            }
            syncAgreementSelection();
            showBusyOverlay('Applying Single Agreement Update...', 'Saving the detected agreement settings for the selected hostel only.');
            agreementApplyForm.submit();
        });
    });

    function buildOntSmartSelect(config) {
        const select = config.select;
        const mountNode = config.mountNode;
        const hostelNameHidden = config.hostelNameHidden;
        const hostelPreview = config.hostelPreview;
        const sitePreview = config.sitePreview;
        const snPreview = config.snPreview;
        const mergeStatusPreview = config.mergeStatusPreview;
        const searchUrl = mountNode ? String(mountNode.dataset.searchUrl || '') : '';

        if (!select || !mountNode || !hostelNameHidden || !hostelPreview || !sitePreview || !snPreview || !mergeStatusPreview || !searchUrl) {
            return null;
        }

        function syncStatusChip(label, tone) {
            mergeStatusPreview.textContent = label || 'Not Added';
            mergeStatusPreview.classList.remove('success', 'muted');
            mergeStatusPreview.classList.add(tone === 'success' ? 'success' : 'muted');
        }

        function syncFromSelected() {
            const selected = select.options[select.selectedIndex];
            if (!selected || !selected.value) {
                hostelNameHidden.value = '';
                hostelPreview.textContent = '-';
                sitePreview.textContent = '-';
                snPreview.textContent = '-';
                syncStatusChip('Not Added', 'muted');
                return;
            }

            const hostelName = selected.dataset.name || '';
            const siteId = selected.dataset.siteId || '';
            const siteSn = selected.dataset.siteSn || '';
            const mergeLabel = selected.dataset.mergeStatusLabel || 'Not Added';
            const mergeTone = selected.dataset.mergeStatusTone || 'muted';

            hostelNameHidden.value = hostelName;
            hostelPreview.textContent = hostelName || '-';
            sitePreview.textContent = siteId !== '' ? ('Site ' + siteId) : 'No site id';
            snPreview.textContent = siteSn !== '' ? siteSn : '-';
            syncStatusChip(mergeLabel, mergeTone);
        }

        let activeRequest = 0;
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ont-smart-trigger';

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
        search.placeholder = 'Search ONT site...';

        const list = document.createElement('div');
        list.className = 'ont-smart-list';

        menu.appendChild(search);
        menu.appendChild(list);
        mountNode.appendChild(trigger);
        mountNode.appendChild(menu);

        function renderHint(text) {
            list.innerHTML = '';
            const row = document.createElement('div');
            row.className = 'ont-smart-empty';
            row.textContent = text;
            list.appendChild(row);
        }

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
                renderHint(error && error.message ? error.message : 'Search failed');
            }
        }

        function updateTriggerLabel() {
            const selected = select.options[select.selectedIndex];
            triggerLabel.textContent = selected && selected.value ? selected.textContent.trim() : 'Search ONT site by name';
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

        search.addEventListener('input', function () {
            const query = search.value.trim();
            if (query.length < 2) {
                renderHint('Type 2+ characters');
                return;
            }
            fetchAndRender(query);
        });

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

        select.addEventListener('change', function () {
            updateTriggerLabel();
            syncFromSelected();
        });

        updateTriggerLabel();
        syncFromSelected();
        renderHint('Type 2+ characters');

        return {
            setDisabled(disabled) {
                trigger.disabled = !!disabled;
                if (disabled) {
                    menu.hidden = true;
                }
            },
            syncFromSelected,
            activate() {
                if (trigger.disabled) return;
                menu.hidden = false;
                renderHint('Type 2+ characters');
                search.focus();
            },
        };
    }

    function wireChainParentSearch(config) {
        const createMode = config.createMode;
        const shell = config.shell;
        const input = config.input;
        const menu = config.menu;
        const hidden = config.hidden;
        const hiddenOntKey = config.hiddenOntKey;
        const status = config.status;
        const previewHostel = config.previewHostel;
        const previewSite = config.previewSite;
        const previewSn = config.previewSn;
        const previewRouters = config.previewRouters;
        const searchUrl = shell ? String(shell.dataset.searchUrl || '') : '';
        const cache = new Map();
        let activeRequest = 0;

        if (!createMode || !shell || !input || !menu || !hidden || !hiddenOntKey || !status || !previewHostel || !previewSite || !previewSn || !previewRouters || !searchUrl) {
            return null;
        }

        function setStatus(message, tone) {
            status.textContent = message || '';
            status.className = tone ? ('ajax-row ' + tone) : 'ajax-row';
        }

        function clearSelection(message) {
            hidden.value = '';
            hiddenOntKey.value = '';
            input.dataset.selectedId = '';
            input.dataset.selectedKey = '';
            previewHostel.textContent = '-';
            previewSite.textContent = '-';
            previewSn.textContent = '-';
            previewRouters.textContent = '-';
            setStatus(message || '', '');
        }

        function updateValidity() {
            if (createMode.value === 'chained_hostel' && hidden.value === '' && hiddenOntKey.value === '') {
                input.setCustomValidity('Select the main ONT/site this record is chained from.');
            } else {
                input.setCustomValidity('');
            }
        }

        function applySelection(item) {
            if (!item) {
                clearSelection('');
                updateValidity();
                return;
            }

            const id = String(item.id || '');
            const ontKey = String(item.ont_key || '');
            if (id !== '' || ontKey !== '') {
                cache.set(id !== '' ? id : ontKey, item);
            }

            hidden.value = id;
            hiddenOntKey.value = ontKey;
            input.value = String(item.hostel_name || '');
            input.dataset.selectedId = id;
            input.dataset.selectedKey = ontKey;
            previewHostel.textContent = String(item.hostel_name || '-');
            previewSite.textContent = item.site_id ? ('Site ' + item.site_id) : '-';
            previewSn.textContent = String(item.site_sn || '-');
            previewRouters.textContent = String(item.no_of_routers ?? 0);
            setStatus(String(item.source_label || 'Main ONT/site selected.'), 'ok');
            updateValidity();
        }

        function renderItems(items) {
            menu.innerHTML = '';
            if (!Array.isArray(items) || items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'ont-smart-empty';
                empty.textContent = 'No main ONT/site found.';
                menu.appendChild(empty);
                menu.hidden = false;
                return;
            }

            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'inline-search-item';

                const title = document.createElement('div');
                title.className = 'inline-search-title';
                title.textContent = String(item.hostel_name || '-');

                const meta = document.createElement('div');
                meta.className = 'inline-search-meta';
                meta.innerHTML = [
                    item.site_id ? ('Site ' + item.site_id) : 'No site id',
                    item.site_sn ? ('S.N ' + item.site_sn) : 'No site serial',
                    'Routers ' + String(item.no_of_routers ?? 0),
                    String(item.source_label || ''),
                ].filter(Boolean).map((entry) => '<span>' + entry + '</span>').join('');

                btn.appendChild(title);
                btn.appendChild(meta);
                btn.addEventListener('click', function () {
                    applySelection(item);
                    menu.hidden = true;
                });

                menu.appendChild(btn);
            });

            menu.hidden = false;
        }

        async function search(query) {
            const requestNo = ++activeRequest;
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '20');
            setStatus('Searching main ONT/sites...', '');

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
                    setStatus(String(payload.message || 'Search failed.'), 'error');
                    menu.hidden = true;
                    return;
                }

                const items = Array.isArray(payload.hostels) ? payload.hostels : [];
                items.forEach((item) => {
                    const id = String(item.id || '');
                    const key = String(item.ont_key || '');
                    if (id !== '' || key !== '') {
                        cache.set(id !== '' ? id : key, item);
                    }
                });
                renderItems(items);
                setStatus(String(payload.message || ''), items.length ? 'ok' : '');
            } catch (error) {
                setStatus(error && error.message ? error.message : 'Search failed.', 'error');
                menu.hidden = true;
            }
        }

        async function preloadSelected(value) {
            if (!value) return;
            const url = new URL(searchUrl, window.location.origin);
            if (String(value).startsWith('site:')) {
                url.searchParams.set('ont_key', value);
            } else {
                url.searchParams.set('ids', value);
            }

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(String(payload.message || 'Unable to load main ONT/site.'));
                }
                const items = Array.isArray(payload.hostels) ? payload.hostels : [];
                const match = items.find((item) => {
                    const itemId = String(item.id || '');
                    const itemKey = String(item.ont_key || '');
                    return itemId === String(value) || itemKey === String(value);
                });
                if (match) {
                    applySelection(match);
                } else {
                    clearSelection('Main ONT/site could not be loaded. Search again.');
                    setStatus('Main ONT/site could not be loaded. Search again.', 'error');
                }
            } catch (error) {
                clearSelection('');
                setStatus(error && error.message ? error.message : 'Unable to load main ONT/site.', 'error');
            }
        }

        input.addEventListener('input', function () {
            if (createMode.value !== 'chained_hostel') return;

            const query = input.value.trim();
            const selectedId = String(hidden.value || '');
            const selectedKey = String(hiddenOntKey.value || '');
            const cacheKey = selectedId !== '' ? selectedId : selectedKey;
            const selectedName = cacheKey !== ''
                ? String((cache.get(cacheKey) || {}).hostel_name || '')
                : '';

            if (selectedName === '' || query !== selectedName) {
                clearSelection('');
            }

            updateValidity();

            if (query.length < 2) {
                menu.hidden = true;
                setStatus(query === '' ? '' : 'Type 2+ characters to search.', '');
                return;
            }

            search(query);
        });

        input.addEventListener('focus', function () {
            if (createMode.value !== 'chained_hostel') return;
            const query = input.value.trim();
            if (query.length >= 2) {
                search(query);
            }
        });

        input.addEventListener('blur', updateValidity);

        document.addEventListener('click', function (event) {
            if (!shell.contains(event.target)) {
                menu.hidden = true;
            }
        });

        preloadSelected(String(hidden.value || hiddenOntKey.value || ''));
        updateValidity();

        return {
            clearSelection,
            updateValidity,
        };
    }

    const ontSelect = document.getElementById('ontKeyModal');
    const ontSmart = document.getElementById('ontSmartIndex');
    const hostelNameHidden = document.getElementById('hostelNameHiddenModal');
    const hostelPreview = document.getElementById('selectedHostelPreviewModal');
    const sitePreview = document.getElementById('selectedSitePreviewModal');
    const snPreview = document.getElementById('selectedSnPreviewModal');
    const mergeStatusPreview = document.getElementById('selectedMergeStatusModal');
    const createModeInputs = Array.from(document.querySelectorAll('.pc-modal[data-modal="hostel-add"] input[name="create_mode"]'));
    const ontSourcePanel = document.getElementById('ontSourcePanelModal');
    const chainSourcePanel = document.getElementById('chainSourcePanelModal');
    const manualHostelName = document.getElementById('manualHostelNameModal');
    const chainParentSearch = wireChainParentSearch({
        createMode: {
            get value() {
                const checked = createModeInputs.find((input) => input.checked);
                return checked ? String(checked.value || 'ont_site') : 'ont_site';
            }
        },
        shell: document.getElementById('chainParentShellModal'),
        input: document.getElementById('chainParentSearchInputModal'),
        menu: document.getElementById('chainParentSearchMenuModal'),
        hidden: document.getElementById('chainedFromHostelIdModal'),
        hiddenOntKey: document.getElementById('chainedFromOntKeyModal'),
        status: document.getElementById('chainParentStatusModal'),
        previewHostel: document.getElementById('chainParentHostelPreviewModal'),
        previewSite: document.getElementById('chainParentSitePreviewModal'),
        previewSn: document.getElementById('chainParentSnPreviewModal'),
        previewRouters: document.getElementById('chainParentRoutersPreviewModal'),
    });
    const ontWidget = buildOntSmartSelect({
        select: ontSelect,
        mountNode: ontSmart,
        hostelNameHidden,
        hostelPreview,
        sitePreview,
        snPreview,
        mergeStatusPreview,
    });

    function getCreateModeValue() {
        const checked = createModeInputs.find((input) => input.checked);
        return checked ? String(checked.value || '') : '';
    }

    function syncCreateMode(activateOntPicker) {
        if (createModeInputs.length === 0 || !ontSelect || !manualHostelName) return;

        const selectedMode = getCreateModeValue();
        const hasSelection = selectedMode !== '';
        const isOntMode = selectedMode === 'ont_site';
        const ontAvailable = String(ontSelect.dataset.ontAvailable || '0') === '1';
        const chainInput = document.getElementById('chainParentSearchInputModal');

        if (ontSourcePanel) ontSourcePanel.hidden = !isOntMode;
        if (chainSourcePanel) chainSourcePanel.hidden = selectedMode !== 'chained_hostel';

        ontSelect.disabled = !isOntMode || !ontAvailable;
        ontSelect.required = isOntMode && ontAvailable;
        if (ontWidget) {
            ontWidget.setDisabled(ontSelect.disabled);
            ontWidget.syncFromSelected();
            if (activateOntPicker && isOntMode) {
                window.requestAnimationFrame(() => ontWidget.activate());
            }
        }
        hostelNameHidden.disabled = !isOntMode;

        manualHostelName.disabled = !hasSelection || isOntMode;
        manualHostelName.required = selectedMode === 'chained_hostel';

        if (chainInput) {
            chainInput.disabled = !hasSelection || isOntMode;
        }
        if (chainParentSearch) {
            chainParentSearch.updateValidity();
        }

        if (!hasSelection) {
            if (ontWidget) {
                ontWidget.setDisabled(true);
            }
            hostelNameHidden.disabled = true;
            return;
        }
    }

    window.pettySyncHostelCreateMode = syncCreateMode;
    createModeInputs.forEach((input) => input.addEventListener('change', () => syncCreateMode(true)));
    syncCreateMode(false);

    if (autoOpenModal) {
        openModal(autoOpenModal);
    }

    syncAgreementSelection();
})();
</script>
@endpush
