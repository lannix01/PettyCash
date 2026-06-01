@php
    $pettyUser = auth('petty')->user();
    $pettyCan = static fn (string $permission): bool => \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, $permission);
    $pettyCanViewNotifications = $pettyCan('notifications.view') || $pettyCan('notifications.manage');
    $pettyCanManageSettings = \App\Modules\PettyCash\Support\PettyAccess::isAdmin($pettyUser);

    $pettyUnread = $pettyCanViewNotifications
        ? \App\Modules\PettyCash\Models\PettyNotification::where('module', 'pettycash')->where('is_read', false)->count()
        : 0;

    $pettyName = trim((string) ($pettyUser->name ?? 'User'));
    $pettyParts = preg_split('/\s+/', $pettyName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $pettyInitials = strtoupper(substr($pettyParts[0] ?? 'U', 0, 1) . substr($pettyParts[1] ?? '', 0, 1));

    $pettyLogoPath = '/root/Marcep/netbil/app/Modules/PettyCash/Skybrix_Logo.png';
    $pettyMarkPath = '/root/Marcep/netbil/app/Modules/PettyCash/skybrix_pettycash_logo.png';

    $pettyLogo = is_file($pettyLogoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($pettyLogoPath))
        : null;
    $pettyMark = is_file($pettyMarkPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($pettyMarkPath))
        : null;
@endphp


<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'PettyCash')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $pettyMark ?: asset('assets/pettycash ico.png') }}">
    <link rel="shortcut icon" href="{{ $pettyMark ?: asset('assets/pettycash ico.png') }}">
    <link rel="apple-touch-icon" href="{{ $pettyLogo ?: asset('assets/images/logo.png') }}">

    <style>
        :root{
            --bg:#f3f7f6;
            --card:#ffffff;
            --border:#dce6e3;
            --text:#12212f;
            --muted:#5f7283;
            --brand:#0d8b6f;
            --brand-deep:#0b5c4d;
            --brand-soft:#e5f6f0;
            --brand-ink:#0c3c35;
            --navy:#15283a;
            --thead:#f6faf9;
            --row-alt:#fbfdfd;
            --row-hover:#f2f8f6;
            --shadow:0 14px 36px rgba(18,33,47,.06);
            --radius:18px;
        }
        *{box-sizing:border-box}
        body{
            font-family:system-ui;
            background:
                radial-gradient(circle at top left, rgba(13,139,111,.06), transparent 24%),
                radial-gradient(circle at bottom right, rgba(21,40,58,.05), transparent 24%),
                linear-gradient(180deg, #f7fbfa 0%, var(--bg) 100%);
            margin:0;
            color:var(--text);
        }
        a{text-decoration:none}

        /* ===== App shell ===== */
        .app{
            display:grid;
            grid-template-columns: 276px 1fr;
            min-height:100vh;
        }

        /* ===== Sidebar ===== */
        .sidebar{
            background:linear-gradient(180deg, #fcfefd 0%, #f4f9f7 100%);
            border-right:1px solid var(--border);
            padding:14px;
            position:sticky;
            top:0;
            height:100vh;
            overflow:auto;
        }
        .brand{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:10px 10px 14px 10px;
            border-bottom:1px solid var(--border);
            margin-bottom:12px;
        }
        .brand .name{
            font-weight:900;
            letter-spacing:.2px;
        }
        .brand .sub{
            font-size:12px;
            color:var(--muted);
            margin-top:2px;
        }
        .brand-lockup{
            display:flex;
            align-items:center;
            gap:10px;
            min-width:0;
        }
        .brand-mark{
            width:48px;
            height:48px;
            border-radius:14px;
            border:1px solid rgba(10,123,99,.14);
            background:linear-gradient(180deg, #ffffff 0%, #eefaf6 100%);
            padding:7px;
            box-shadow:0 10px 24px rgba(10,123,99,.12);
            flex:0 0 auto;
        }
        .brand-mark img{
            width:100%;
            height:100%;
            object-fit:contain;
            display:block;
        }
        .brand-logo{
            height:22px;
            width:auto;
            display:block;
            margin-bottom:3px;
        }
        .brand-kicker{
            display:inline-flex;
            align-items:center;
            gap:6px;
            margin-top:5px;
            font-size:11px;
            font-weight:800;
            color:var(--brand-ink);
            background:var(--brand-soft);
            border:1px solid rgba(10,123,99,.14);
            border-radius:999px;
            padding:4px 8px;
        }
        .brand-kicker::before{
            content:"";
            width:6px;
            height:6px;
            border-radius:50%;
            background:var(--brand);
            display:block;
        }

        /* ===== Main ===== */
        .main{
            display:flex;
            flex-direction:column;
            min-width:0;
        }

        /* Topbar */
        .topbar{
            background:rgba(255,255,255,.88);
            backdrop-filter:blur(10px);
            border-bottom:1px solid rgba(220,230,227,.85);
            padding:14px 24px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            position:sticky;
            top:0;
            z-index:10;
        }
        .topbar-left{
            display:flex;
            align-items:center;
            gap:10px;
            min-width:0;
        }
        .topbar-right{
            display:flex;
            align-items:center;
            gap:8px;
        }
        .topbar-title{
            font-size:18px;
            font-weight:900;
            letter-spacing:-.02em;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .topbar-subtitle{
            font-size:12px;
            color:var(--muted);
            margin-top:2px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        /* Mobile menu button */
        .menu-btn{
            display:none;
            border:1px solid rgba(21,40,58,.10);
            background:#fff;
            color:var(--navy);
            padding:8px 12px;
            border-radius:12px;
            font-weight:800;
            cursor:pointer;
        }

        .top-icon-btn{
            border:1px solid rgba(21,40,58,.10);
            background:#fff;
            color:var(--navy);
            width:40px;
            height:40px;
            border-radius:12px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            position:relative;
        }
        .top-icon-btn:hover{border-color:rgba(13,139,111,.22);background:#f8fcfb}
        .notify-badge{
            position:absolute;
            top:-6px;
            right:-6px;
            background:#b42318;
            color:#fff;
            border-radius:999px;
            min-width:18px;
            height:18px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:11px;
            font-weight:900;
            padding:0 4px;
        }

        .profile-menu{position:relative}
        .avatar-btn{
            border:1px solid rgba(21,40,58,.10);
            background:#fff;
            color:var(--navy);
            height:40px;
            border-radius:12px;
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:0 8px 0 6px;
            cursor:pointer;
        }
        .avatar-btn:hover{border-color:rgba(13,139,111,.22);background:#f8fcfb}
        .avatar-dot{
            width:26px;
            height:26px;
            border-radius:10px;
            background:var(--brand-soft);
            color:var(--brand-ink);
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:11px;
            font-weight:900;
        }
        .avatar-caret{
            font-size:11px;
            color:#667085;
            line-height:1;
        }
        .profile-dropdown{
            position:absolute;
            right:0;
            top:44px;
            min-width:220px;
            background:#fff;
            border:1px solid #e4e7ec;
            border-radius:16px;
            box-shadow:0 20px 48px rgba(18,33,47,.15);
            padding:8px;
            z-index:40;
        }
        .profile-head{
            padding:8px;
            border-bottom:1px solid #f1f3f7;
            margin-bottom:6px;
        }
        .profile-head .name{
            font-size:13px;
            font-weight:900;
            color:#101828;
        }
        .profile-head .sub{
            font-size:11px;
            color:#667085;
            margin-top:2px;
        }
        .profile-item{
            width:100%;
            border:none;
            background:transparent;
            text-align:left;
            padding:9px 10px;
            border-radius:8px;
            color:#344054;
            font-size:13px;
            font-weight:700;
            text-decoration:none;
            display:block;
            cursor:pointer;
        }
        .profile-item:hover{background:#f2f4f7}

        /* Content area */
        .content{
            padding:24px 28px 32px;
        }
        .container{
            width:100%;
            max-width:none;
            margin:0;
        }

        /* Default card utility for pages */
        .card{
            background:var(--card);
            border:1px solid var(--border);
            border-radius:var(--radius);
            padding:16px;
            box-shadow:var(--shadow);
            overflow-x:auto;
        }
        .muted{color:var(--muted);font-size:12px}
        .content .wrap{
            width:100% !important;
            max-width:none !important;
            margin:0 !important;
        }

        /* Shared form UX */
        .form-wrap{
            width:100%;
            max-width:none;
            margin:0;
        }
        .form-wrap.form-wrap-sm{max-width:none}
        .form-header{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }
        .form-header h2{
            margin:0;
            font-size:22px;
            line-height:1.2;
            letter-spacing:-.02em;
        }
        .form-subtitle{
            color:#667085;
            font-size:13px;
            margin-top:4px;
        }
        .form-card{
            background:linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border:1px solid #e4e7ec;
            border-radius:16px;
            padding:18px;
            box-shadow:0 14px 34px rgba(16,24,40,.08);
            margin-top:12px;
        }
        .pc-form{
            display:grid;
            grid-template-columns:repeat(12, minmax(0, 1fr));
            gap:14px;
        }
        .pc-field{
            grid-column:span 6;
            min-width:0;
        }
        .pc-field.full{
            grid-column:1 / -1;
        }
        .pc-field label{
            display:block;
            font-size:11px;
            font-weight:800;
            letter-spacing:.04em;
            color:#475467;
            text-transform:uppercase;
            margin-bottom:6px;
        }
        .pc-input,
        .pc-select,
        .pc-textarea{
            width:100%;
            border:1px solid #d0d5dd;
            background:#fff;
            border-radius:12px;
            padding:10px 12px;
            font-size:14px;
            color:#101828;
            transition:border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .pc-input:focus,
        .pc-select:focus,
        .pc-textarea:focus{
            border-color:var(--brand);
            box-shadow:0 0 0 4px rgba(13,139,111,.12);
            outline:none;
            background:#fff;
        }
        .pc-input[disabled],
        .pc-input[readonly],
        .pc-select[disabled],
        .pc-textarea[readonly]{
            background:#f8fafc;
            color:#475467;
        }
        .pc-help{
            color:#667085;
            font-size:12px;
            margin-top:6px;
        }
        .pc-actions{
            grid-column:1 / -1;
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            padding-top:2px;
        }
        .pc-inline-grid{
            grid-column:1 / -1;
            display:grid;
            grid-template-columns:repeat(12, minmax(0, 1fr));
            gap:14px;
        }
        .pc-inline-grid .pc-field{
            grid-column:span 6;
        }
        .pc-check{
            display:inline-flex;
            align-items:center;
            gap:8px;
            color:#344054;
            font-size:13px;
            font-weight:600;
        }
        .pc-field label.pc-check{
            text-transform:none;
            letter-spacing:0;
            font-size:13px;
            font-weight:700;
            margin-bottom:0;
        }
        .pc-check input{
            width:16px;
            height:16px;
            margin:0;
            accent-color:var(--brand);
        }
        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            min-height:38px;
            padding:0 14px;
            border-radius:10px;
            border:1px solid var(--brand);
            background:var(--brand);
            color:#fff;
            text-decoration:none;
            font-size:13px;
            font-weight:800;
            line-height:1;
            cursor:pointer;
        }
        .btn:hover{
            background:var(--brand-deep);
            border-color:var(--brand-deep);
        }
        .btn2{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            min-height:38px;
            padding:0 14px;
            border-radius:10px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#344054;
            text-decoration:none;
            font-size:13px;
            font-weight:800;
            line-height:1;
            cursor:pointer;
        }
        .btn2:hover{
            border-color:#98a2b3;
            background:#f9fafb;
        }
        .err{
            grid-column:1 / -1;
            background:#fef3f2;
            color:#b42318;
            border:1px solid #fecdca;
            padding:10px 12px;
            border-radius:10px;
            font-size:13px;
            font-weight:700;
        }
        .content .container{
            max-width:none !important;
            width:100% !important;
            margin:0 !important;
        }
        .content .form-wrap,
        .content .form-wrap.form-wrap-sm{
            max-width:none !important;
            width:100% !important;
            margin:0 !important;
        }

        .pc-workflow{
            display:grid;
            gap:14px;
            width:100%;
        }
        .pc-form > .pc-workflow{
            grid-column:1 / -1;
        }
        .pc-step{
            border:1px solid #e4e7ec;
            border-radius:16px;
            background:linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            box-shadow:0 14px 34px rgba(16,24,40,.08);
            overflow:hidden;
            transition:border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }
        .pc-step.is-open{
            border-color:#c7d7fe;
            box-shadow:0 18px 42px rgba(16,24,40,.11);
        }
        .pc-step.is-complete{
            border-color:#abefc6;
        }
        .pc-step.is-locked{
            background:#f8fafc;
            box-shadow:none;
        }
        .pc-step-trigger{
            width:100%;
            border:none;
            background:transparent;
            padding:18px 20px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            text-align:left;
            cursor:pointer;
        }
        .pc-step-trigger:disabled{
            cursor:not-allowed;
        }
        .pc-step-trigger-main{
            display:flex;
            align-items:flex-start;
            gap:14px;
            min-width:0;
        }
        .pc-step-index{
            width:32px;
            height:32px;
            border-radius:999px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#344054;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
            font-weight:900;
            flex:0 0 auto;
        }
        .pc-step.is-open .pc-step-index{
            border-color:var(--brand);
            background:var(--brand);
            color:#fff;
        }
        .pc-step.is-complete .pc-step-index{
            border-color:#12b76a;
            background:#12b76a;
            color:#fff;
        }
        .pc-step-copy{
            min-width:0;
        }
        .pc-step-title{
            margin:0;
            font-size:18px;
            line-height:1.2;
            color:#101828;
            letter-spacing:-.02em;
        }
        .pc-step-text{
            margin-top:4px;
            font-size:13px;
            line-height:1.5;
            color:#667085;
        }
        .pc-step-meta{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:28px;
            padding:0 10px;
            border-radius:999px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#475467;
            font-size:11px;
            font-weight:800;
            letter-spacing:.05em;
            text-transform:uppercase;
            white-space:nowrap;
        }
        .pc-step.is-complete .pc-step-meta{
            border-color:#abefc6;
            background:#ecfdf3;
            color:#027a48;
        }
        .pc-step.is-locked .pc-step-meta{
            background:#f2f4f7;
            color:#667085;
        }
        .pc-step-arrow{
            width:32px;
            height:32px;
            border-radius:999px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#344054;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:14px;
            line-height:1;
            flex:0 0 auto;
            transition:transform .18s ease, border-color .18s ease, background-color .18s ease;
        }
        .pc-step.is-open .pc-step-arrow{
            transform:rotate(180deg);
            border-color:#c7d7fe;
            background:#eef4ff;
        }
        .pc-step-body{
            padding:0 20px 20px;
            border-top:1px solid #eaecf0;
        }
        .pc-step-body[hidden]{
            display:none !important;
        }
        .pc-step-panel{
            padding-top:18px;
            display:grid;
            grid-template-columns:repeat(12, minmax(0, 1fr));
            gap:14px;
        }
        .pc-step-panel > :first-child{
            margin-top:0 !important;
        }
        .pc-step-panel > :not(.pc-field){
            grid-column:1 / -1;
        }
        .pc-step-actions{
            margin-top:18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            flex-wrap:wrap;
        }
        .pc-step-actions-main{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }
        .pc-step-actions-note{
            color:#667085;
            font-size:12px;
            font-weight:700;
        }
        .pc-step-summary{
            display:grid;
            gap:10px;
        }
        .pc-step-summary-grid{
            display:grid;
            grid-template-columns:repeat(12, minmax(0, 1fr));
            gap:12px;
        }
        .pc-step-summary-card{
            grid-column:span 4;
            border:1px solid #eaecf0;
            border-radius:14px;
            background:#fff;
            padding:12px 14px;
        }
        .pc-step-summary-label{
            font-size:11px;
            font-weight:800;
            letter-spacing:.05em;
            color:#667085;
            text-transform:uppercase;
        }
        .pc-step-summary-value{
            margin-top:6px;
            font-size:16px;
            font-weight:900;
            color:#101828;
        }
        .spinner{
            width:14px;
            height:14px;
            border-radius:50%;
            border:2px solid rgba(13,139,111,.18);
            border-top-color:var(--brand);
            animation:pcSpin .8s linear infinite;
            flex:0 0 auto;
        }
        .pc-loading-modal{
            position:fixed;
            inset:0;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            background:rgba(15,23,42,.24);
            backdrop-filter:blur(4px);
            z-index:160;
            opacity:0;
            pointer-events:none;
            transition:opacity .18s ease;
        }
        .pc-loading-modal.is-open{
            opacity:1;
            pointer-events:auto;
        }
        .pc-loading-dialog{
            width:min(100%, 280px);
            display:grid;
            justify-items:center;
            gap:14px;
            padding:22px 20px 18px;
            border:1px solid rgba(228,231,236,.95);
            border-radius:20px;
            background:rgba(255,255,255,.97);
            box-shadow:0 24px 60px rgba(16,24,40,.18);
            text-align:center;
        }
        .pc-loading-title{
            font-size:15px;
            font-weight:900;
            color:#101828;
            line-height:1.35;
        }
        .pc-loading-copy{
            font-size:12px;
            color:#667085;
            line-height:1.5;
        }
        .loader{
            width:50px;
            aspect-ratio:1;
            display:grid;
            border-radius:50%;
            background:
                linear-gradient(0deg, rgb(16 24 40 / 50%) 30%, #0000 0 70%, rgb(16 24 40 / 100%) 0) 50%/8% 100%,
                linear-gradient(90deg, rgb(16 24 40 / 25%) 30%, #0000 0 70%, rgb(16 24 40 / 75%) 0) 50%/100% 8%;
            background-repeat:no-repeat;
            animation:pcLoaderSpin 1s infinite steps(12);
        }
        .loader::before,
        .loader::after{
            content:"";
            grid-area:1/1;
            border-radius:50%;
            background:inherit;
            opacity:.915;
            transform:rotate(30deg);
        }
        .loader::after{
            opacity:.83;
            transform:rotate(60deg);
        }
        @keyframes pcSpin{
            to{transform:rotate(360deg)}
        }
        @keyframes pcLoaderSpin{
            100%{transform:rotate(1turn)}
        }

        /* Compact filters: keep filters secondary to main cards/tables */
        .pc-filter-dock{
            display:block;
            margin:0 0 14px;
        }
        .pc-filter-panel{
            width:100%;
            border:1px solid #e4e7ec;
            border-radius:16px;
            background:linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            box-shadow:0 12px 26px rgba(16,24,40,.07);
        }
        .pc-filter-panel summary{
            list-style:none;
            cursor:pointer;
            padding:14px 16px 12px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            font-size:13px;
            font-weight:800;
            color:#344054;
        }
        .pc-filter-panel summary::-webkit-details-marker{display:none}
        .pc-filter-panel[open] summary{
            border-bottom:1px solid #eaecf0;
            background:#f8fafc;
            border-radius:16px 16px 0 0;
        }
        .pc-filter-title{
            display:inline-flex;
            align-items:center;
            gap:8px;
        }
        .pc-filter-title::before{
            content:"";
            width:6px;
            height:6px;
            border-radius:999px;
            background:var(--brand);
        }
        .pc-filter-state{
            font-size:11px;
            font-weight:800;
            color:#667085;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .pc-filter-body{
            padding:14px 16px 16px;
        }
        .pc-filter-row{
            display:flex;
            gap:12px;
            align-items:flex-end;
            flex-wrap:wrap;
        }
        .pc-filter-body .row,
        .pc-filter-body .filters{
            margin-top:0;
        }
        .pc-filter-row .muted{
            font-size:11px;
            margin-bottom:4px;
        }
        .pc-filter-row input,
        .pc-filter-row select{
            min-width:140px;
        }
        .pc-filter-grow{
            flex:1 1 300px;
            min-width:220px;
        }
        .pc-filter-actions{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            margin-left:auto;
        }
        .pc-filter-shell{
            position:relative;
        }
        .pc-list-shell{
            position:relative;
            transition:opacity .18s ease;
        }
        .pc-list-shell.is-loading{
            opacity:.66;
            pointer-events:none;
        }
        .pc-list-shell.is-loading::after{
            content:"";
            position:absolute;
            inset:0;
            background:rgba(255,255,255,.42);
            z-index:3;
        }
        .pc-inline-refresh{
            position:absolute;
            top:12px;
            right:12px;
            z-index:4;
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 10px;
            border-radius:999px;
            border:1px solid #d0d5dd;
            background:rgba(255,255,255,.96);
            color:#344054;
            font-size:11px;
            font-weight:800;
            box-shadow:0 10px 24px rgba(16,24,40,.10);
            opacity:0;
            transform:translateY(-4px);
            pointer-events:none;
            transition:opacity .18s ease, transform .18s ease;
        }
        .pc-list-shell.is-loading .pc-inline-refresh{
            opacity:1;
            transform:translateY(0);
        }
        @media(max-width:900px){
            .pc-field{grid-column:1 / -1}
            .pc-inline-grid .pc-field{grid-column:1 / -1}
            .pc-step-trigger{
                padding:16px;
            }
            .pc-step-body{
                padding:0 16px 16px;
            }
            .pc-step-summary-card{
                grid-column:1 / -1;
            }
        }

        /* ===== Mobile behaviour ===== */
        @media(max-width: 980px){
            .app{grid-template-columns:1fr;}
            .sidebar{
                position:fixed;
                z-index:30;
                left:0; top:0;
                width:300px;
                height:100vh;
                transform:translateX(-105%);
                transition:transform .22s ease;
                box-shadow:0 20px 60px rgba(16,24,40,.22);
            }
            body.sidebar-open .sidebar{transform:translateX(0);}
            body.sidebar-open{overflow:hidden;}

            .menu-btn{display:inline-block}
            .overlay{
                display:none;
                position:fixed;
                inset:0;
                background:rgba(16,24,40,.45);
                z-index:25;
            }
            body.sidebar-open .overlay{display:block}
            .content{padding:14px}
            .container{max-width:100%}
            .form-card{padding:14px}
        }

        /* Shared table UX */
        .content .table-wrap{
            overflow:auto;
            -webkit-overflow-scrolling:touch;
            width:100%;
            margin-top:12px;
            border:1px solid var(--border);
            border-radius:12px;
            background:#fff;
        }

        .content .card table,
        .content .table-wrap table{
            width:100%;
            border-collapse:separate;
            border-spacing:0;
            margin-top:12px;
            min-width:820px;
        }

        .content .table-wrap table{
            margin-top:0;
        }

        .content .card > table{
            border:1px solid var(--border);
            border-radius:12px;
            overflow:hidden;
        }

        .content .card table th,
        .content .card table td,
        .content .table-wrap table th,
        .content .table-wrap table td{
            padding:11px 12px;
            border-bottom:1px solid #eaecf0;
            font-size:13px;
            vertical-align:middle;
            text-align:left;
        }

        .content .card table th,
        .content .table-wrap table th{
            background:var(--thead);
            color:#475467;
            font-size:11px;
            font-weight:800;
            letter-spacing:.04em;
            text-transform:uppercase;
            white-space:nowrap;
        }

        .content .table-wrap table th{
            position:sticky;
            top:0;
            z-index:2;
        }

        .content .card table tbody tr:nth-child(even),
        .content .table-wrap table tbody tr:nth-child(even){
            background:var(--row-alt);
        }

        .content .card table tbody tr:hover,
        .content .table-wrap table tbody tr:hover{
            background:var(--row-hover);
        }

        .content .card table tbody tr:last-child td,
        .content .table-wrap table tbody tr:last-child td{
            border-bottom:none;
        }

        .content .card table .num,
        .content .card table .text-end,
        .content .table-wrap table .num,
        .content .table-wrap table .text-end{
            text-align:right;
            white-space:nowrap;
        }

        .petty-pager{
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:8px;
            flex-wrap:wrap;
        }
        .petty-pager-btn,
        .petty-page-link{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:34px;
            height:34px;
            padding:0 10px;
            border:1px solid #d0d5dd;
            border-radius:8px;
            background:#fff;
            color:#344054;
            font-size:13px;
            font-weight:800;
            text-decoration:none;
        }
        .petty-pager-btn:hover,
        .petty-page-link:hover{
            border-color:#98a2b3;
            background:#f9fafb;
        }
        .petty-pager-btn.is-disabled{
            color:#98a2b3;
            background:#f9fafb;
            pointer-events:none;
        }
        .petty-page-link.is-active{
            background:#111827;
            border-color:#111827;
            color:#fff;
        }
        .petty-page-list{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
        }
        .petty-page-gap{
            color:#98a2b3;
            padding:0 2px;
            font-size:13px;
            font-weight:800;
        }

        .pc-toast-stack{
            position:fixed;
            top:74px;
            left:50%;
            transform:translateX(-50%);
            z-index:120;
            display:grid;
            gap:10px;
            width:min(540px, calc(100vw - 24px));
            pointer-events:none;
        }
        .pc-toast{
            pointer-events:auto;
            border-radius:14px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#101828;
            box-shadow:0 18px 42px rgba(16,24,40,.20);
            padding:12px 14px 10px;
            animation:pcToastIn .22s ease-out;
            display:grid;
            gap:8px;
        }
        @keyframes pcToastIn{
            from{opacity:0;transform:translateY(-10px) scale(.98)}
            to{opacity:1;transform:translateY(0) scale(1)}
        }
        .pc-toast-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:10px;
        }
        .pc-toast-content{
            display:flex;
            align-items:flex-start;
            gap:10px;
            min-width:0;
        }
        .pc-toast-icon{
            width:22px;
            height:22px;
            border-radius:50%;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:12px;
            font-weight:900;
            flex:0 0 auto;
            margin-top:1px;
        }
        .pc-toast-title{
            font-size:12px;
            font-weight:900;
            letter-spacing:.02em;
            text-transform:uppercase;
            margin-bottom:2px;
        }
        .pc-toast-message{
            font-size:13px;
            font-weight:700;
            line-height:1.4;
            color:#101828;
            word-break:break-word;
        }
        .pc-toast-close{
            border:none;
            background:transparent;
            color:#667085;
            font-weight:900;
            line-height:1;
            cursor:pointer;
            padding:0;
            font-size:16px;
        }
        .pc-toast-close:hover{color:#101828}
        .pc-toast-progress{
            height:3px;
            border-radius:999px;
            background:rgba(16,24,40,.09);
            overflow:hidden;
        }
        .pc-toast-progress > i{
            display:block;
            height:100%;
            width:100%;
            transform-origin:left center;
            animation:pcToastBar var(--toast-ms, 5600ms) linear forwards;
        }
        @keyframes pcToastBar{
            from{transform:scaleX(1)}
            to{transform:scaleX(0)}
        }
        .pc-toast-success{border-color:#abefc6;background:#f0fdf4}
        .pc-toast-success .pc-toast-icon{background:#d1fadf;color:#067647}
        .pc-toast-success .pc-toast-title{color:#067647}
        .pc-toast-success .pc-toast-progress > i{background:#12b76a}
        .pc-toast-error{border-color:#fecdca;background:#fef3f2}
        .pc-toast-error .pc-toast-icon{background:#fee4e2;color:#b42318}
        .pc-toast-error .pc-toast-title{color:#b42318}
        .pc-toast-error .pc-toast-progress > i{background:#f04438}
        .pc-toast-warning{border-color:#fedf89;background:#fffaeb}
        .pc-toast-warning .pc-toast-icon{background:#fef0c7;color:#b54708}
        .pc-toast-warning .pc-toast-title{color:#b54708}
        .pc-toast-warning .pc-toast-progress > i{background:#f79009}

        @media(max-width:980px){
            .pc-toast-stack{top:66px}
            .petty-pager{
                justify-content:center;
            }
        }


    </style>

    @stack('styles')
</head>
<body>
<div class="app">
    {{-- Sidebar --}}
    @include('pettycash::partials.nav')

    {{-- Main --}}
    <div class="main">
        @php
            $pettyToasts = [];
            if (session('success')) {
                $pettyToasts[] = ['type' => 'success', 'title' => 'Success', 'message' => (string) session('success'), 'dismiss_ms' => 5600];
            }
            if (session('error')) {
                $pettyToasts[] = ['type' => 'error', 'title' => 'Error', 'message' => (string) session('error'), 'dismiss_ms' => 7600];
            }
            if (session('warning')) {
                $pettyToasts[] = ['type' => 'warning', 'title' => 'Warning', 'message' => (string) session('warning'), 'dismiss_ms' => 6800];
            }
            if ($errors->any() && !session('error')) {
                $pettyToasts[] = ['type' => 'error', 'title' => 'Validation Error', 'message' => (string) $errors->first(), 'dismiss_ms' => 7600];
            }
        @endphp
        @if(!empty($pettyToasts))
            <div id="pettyToastStack" class="pc-toast-stack" role="status" aria-live="polite">
                @foreach($pettyToasts as $toast)
                    @php
                        $toastType = in_array($toast['type'], ['success', 'error', 'warning'], true) ? $toast['type'] : 'success';
                        $toastDismissMs = (int) ($toast['dismiss_ms'] ?? 5600);
                        $toastIcon = $toastType === 'success' ? '✓' : ($toastType === 'warning' ? '!' : '×');
                    @endphp
                    <div class="pc-toast pc-toast-{{ $toastType }}" data-auto-dismiss="1" data-dismiss-ms="{{ $toastDismissMs }}">
                        <div class="pc-toast-main">
                            <div class="pc-toast-content">
                                <span class="pc-toast-icon" aria-hidden="true">{{ $toastIcon }}</span>
                                <div>
                                    <div class="pc-toast-title">{{ $toast['title'] }}</div>
                                    <div class="pc-toast-message">{{ $toast['message'] }}</div>
                                </div>
                            </div>
                            <button class="pc-toast-close" type="button" onclick="dismissToast(this)" aria-label="Dismiss notification">×</button>
                        </div>
                        <div class="pc-toast-progress" style="--toast-ms: {{ $toastDismissMs }}ms"><i></i></div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-btn" type="button" onclick="toggleSidebar()">Menu</button>
                <div>
                    <div class="topbar-title">@yield('title','PettyCash')</div>
                    <div class="topbar-subtitle">Skybrix petty cash operations workspace</div>
                </div>
            </div>

            <div class="topbar-right">
                @if($pettyCanViewNotifications)
                    <a href="{{ route('petty.notifications.index') }}" class="top-icon-btn" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14.5 19a2.5 2.5 0 0 1-5 0m9-3H5.5a1 1 0 0 1-.76-1.65l1.27-1.48V10a6 6 0 1 1 12 0v2.87l1.27 1.48A1 1 0 0 1 18.5 16Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @if($pettyUnread > 0)
                            <span class="notify-badge">
                                {{ $pettyUnread }}
                            </span>
                        @endif
                    </a>
                @endif

                <div class="profile-menu" id="pettyProfileMenu">
                    <button class="avatar-btn" type="button" aria-haspopup="menu" aria-expanded="false" onclick="toggleProfileMenu(event)">
                        <span class="avatar-dot">{{ $pettyInitials }}</span>
                        <span class="avatar-caret">▾</span>
                    </button>
                    <div class="profile-dropdown" id="pettyProfileDropdown" hidden>
                        <div class="profile-head">
                            <div class="name">{{ $pettyUser?->name ?? 'Petty User' }}</div>
                            <div class="sub">{{ $pettyUser?->email ?? '' }}</div>
                        </div>
                        <a class="profile-item" href="{{ route('petty.profile.index') }}">Profile</a>
                        @if($pettyCanManageSettings)
                            <a class="profile-item" href="{{ route('petty.settings.index') }}">Settings</a>
                        @endif
                        <form method="POST" action="{{ route('petty.logout') }}" style="margin:0" data-confirm="Logout from PettyCash?">
                            @csrf
                            <button class="profile-item" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                @yield('content')
            </div>
        </div>
    </div>
</div>

{{-- mobile overlay --}}
<div class="overlay" onclick="toggleSidebar()"></div>
<div id="pettyLoadingModal" class="pc-loading-modal" hidden aria-hidden="true">
    <div class="pc-loading-dialog" role="status" aria-live="polite" aria-atomic="true">
        <div class="loader" aria-hidden="true"></div>
        <div class="pc-loading-title" data-loading-title>Saving...</div>
        <div class="pc-loading-copy" data-loading-copy>Please wait a moment.</div>
    </div>
</div>

<script>
    function toggleSidebar(){
        document.body.classList.toggle('sidebar-open');
    }

    function ensureToastStack(){
        let stack = document.getElementById('pettyToastStack');
        if (stack) return stack;

        stack = document.createElement('div');
        stack.id = 'pettyToastStack';
        stack.className = 'pc-toast-stack';
        stack.setAttribute('role', 'status');
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
        return stack;
    }

    window.pettyCreateToast = function pettyCreateToast(options){
        const payload = options || {};
        const type = ['success', 'error', 'warning'].includes(String(payload.type || 'success'))
            ? String(payload.type || 'success')
            : 'success';
        const title = String(payload.title || (type === 'success' ? 'Success' : (type === 'warning' ? 'Warning' : 'Error')));
        const message = String(payload.message || '');
        const dismissMs = Math.max(2200, Number(payload.dismissMs || 5600));
        const icon = type === 'success' ? '✓' : (type === 'warning' ? '!' : '×');
        const stack = ensureToastStack();

        const toast = document.createElement('div');
        toast.className = 'pc-toast pc-toast-' + type;
        toast.setAttribute('data-auto-dismiss', '1');
        toast.setAttribute('data-dismiss-ms', String(dismissMs));
        toast.innerHTML =
            '<div class="pc-toast-main">' +
                '<div class="pc-toast-content">' +
                    '<span class="pc-toast-icon" aria-hidden="true">' + icon + '</span>' +
                    '<div>' +
                        '<div class="pc-toast-title"></div>' +
                        '<div class="pc-toast-message"></div>' +
                    '</div>' +
                '</div>' +
                '<button class="pc-toast-close" type="button" aria-label="Dismiss notification">×</button>' +
            '</div>' +
            '<div class="pc-toast-progress" style="--toast-ms: ' + dismissMs + 'ms"><i></i></div>';

        toast.querySelector('.pc-toast-title').textContent = title;
        toast.querySelector('.pc-toast-message').textContent = message;
        toast.querySelector('.pc-toast-close').addEventListener('click', function () {
            toast.remove();
        });

        stack.appendChild(toast);

        window.setTimeout(function () {
            toast.remove();
        }, dismissMs);

        return toast;
    };

    function dismissToast(button){
        const toast = button?.closest('.pc-toast');
        if (toast) {
            toast.remove();
        }
    }

    document.querySelectorAll('.pc-toast[data-auto-dismiss="1"]').forEach(function (toast) {
        const timeoutMs = Math.max(2200, Number(toast.getAttribute('data-dismiss-ms') || 5600));
        window.setTimeout(function () {
            toast.remove();
        }, timeoutMs);
    });

    function ensureLoadingModal(){
        let modal = document.getElementById('pettyLoadingModal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'pettyLoadingModal';
        modal.className = 'pc-loading-modal';
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML =
            '<div class="pc-loading-dialog" role="status" aria-live="polite" aria-atomic="true">' +
                '<div class="loader" aria-hidden="true"></div>' +
                '<div class="pc-loading-title" data-loading-title>Saving...</div>' +
                '<div class="pc-loading-copy" data-loading-copy>Please wait a moment.</div>' +
            '</div>';
        document.body.appendChild(modal);
        return modal;
    }

    function pettyShowLoadingModal(title, copy){
        const modal = ensureLoadingModal();
        const titleNode = modal.querySelector('[data-loading-title]');
        const copyNode = modal.querySelector('[data-loading-copy]');

        if (titleNode) {
            titleNode.textContent = String(title || 'Saving...');
        }
        if (copyNode) {
            copyNode.textContent = String(copy || 'Please wait a moment.');
        }

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        window.requestAnimationFrame(function () {
            modal.classList.add('is-open');
        });
    }

    function pettyHideLoadingModal(){
        const modal = document.getElementById('pettyLoadingModal');
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        window.setTimeout(function () {
            if (!modal.classList.contains('is-open')) {
                modal.hidden = true;
            }
        }, 180);
    }

    window.pettyShowLoadingModal = pettyShowLoadingModal;
    window.pettyHideLoadingModal = pettyHideLoadingModal;
    window.addEventListener('pageshow', pettyHideLoadingModal);

    function pettyResolveLoadingLabel(button, fallbackLabel){
        if (!(button instanceof HTMLElement)) {
            return String(fallbackLabel || 'Loading...');
        }

        const explicitLabel = String(button.getAttribute('data-loading-label') || '').trim();
        if (explicitLabel !== '') {
            return explicitLabel;
        }

        const rawText = button.tagName === 'INPUT'
            ? String(button.value || '').trim()
            : String(button.textContent || '').replace(/\s+/g, ' ').trim();

        if (rawText !== '') {
            return /[.!?…]$/.test(rawText) ? rawText : (rawText + '...');
        }

        return String(fallbackLabel || 'Loading...');
    }

    window.pettyResolveLoadingLabel = pettyResolveLoadingLabel;

    function closeActionMenus(exceptMenu){
        document.querySelectorAll('details.action-menu[open]').forEach(function (menu) {
            if (exceptMenu && menu === exceptMenu) return;
            menu.removeAttribute('open');
        });
    }

    document.addEventListener('toggle', function (event) {
        const menu = event.target;
        if (!(menu instanceof HTMLDetailsElement)) return;
        if (!menu.matches('details.action-menu')) return;
        if (menu.open) closeActionMenus(menu);
    }, true);

    document.addEventListener('click', function (event) {
        const clickedMenu = event.target.closest('details.action-menu');
        if (!clickedMenu) {
            closeActionMenus();
            return;
        }

        const clickedAction = event.target.closest('.action-menu-item');
        if (clickedAction && clickedAction.getAttribute('aria-disabled') !== 'true') {
            window.setTimeout(function () {
                clickedMenu.removeAttribute('open');
            }, 0);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeActionMenus();
        }
    });

    function toggleProfileMenu(event){
        event.stopPropagation();
        const menu = document.getElementById('pettyProfileMenu');
        const dropdown = document.getElementById('pettyProfileDropdown');
        const trigger = menu ? menu.querySelector('.avatar-btn') : null;
        if (!dropdown || !trigger) return;

        const isOpen = !dropdown.hasAttribute('hidden');
        if (isOpen) {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
        } else {
            dropdown.removeAttribute('hidden');
            trigger.setAttribute('aria-expanded', 'true');
        }
    }

    function closeProfileMenu(){
        const menu = document.getElementById('pettyProfileMenu');
        const dropdown = document.getElementById('pettyProfileDropdown');
        const trigger = menu ? menu.querySelector('.avatar-btn') : null;
        if (!dropdown || !trigger) return;
        dropdown.setAttribute('hidden', '');
        trigger.setAttribute('aria-expanded', 'false');
    }

    document.addEventListener('click', function (event) {
        const menu = document.getElementById('pettyProfileMenu');
        if (!menu || menu.contains(event.target)) return;
        closeProfileMenu();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileMenu();
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const message = String(form.getAttribute('data-confirm') || '').trim();
        if (message === '') return;

        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }

        form.dataset.confirmed = '1';
    }, true);

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-confirm]:not(form)');
        if (!(trigger instanceof HTMLElement)) return;

        const form = trigger.closest('form');
        if (form instanceof HTMLFormElement) return;

        const message = String(trigger.getAttribute('data-confirm') || '').trim();
        if (message === '') return;

        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    function pettyWorkflowIsVisible(node){
        if (!(node instanceof HTMLElement)) return false;
        if (node.closest('[hidden]')) return false;
        if (window.getComputedStyle(node).display === 'none') return false;
        return node.getClientRects().length > 0;
    }

    function pettyWorkflowValidateStep(step){
        if (!(step instanceof HTMLElement)) return true;

        const fields = Array.from(step.querySelectorAll('input, select, textarea'));
        for (const field of fields) {
            if (!(field instanceof HTMLElement)) continue;
            if (field.hasAttribute('disabled')) continue;
            if (field.getAttribute('type') === 'hidden') continue;
            if (!pettyWorkflowIsVisible(field)) continue;
            if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                if (typeof field.reportValidity === 'function') {
                    field.reportValidity();
                }
                return false;
            }
        }

        return true;
    }

    function pettySetButtonLoadingState(button, loadingLabel){
        if (!(button instanceof HTMLElement)) return;
        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = button.tagName === 'INPUT'
                ? String(button.value || '')
                : String(button.innerHTML || '');
        }

        const label = pettyResolveLoadingLabel(button, loadingLabel || 'Loading...');
        button.disabled = true;
        if (button.tagName === 'INPUT') {
            button.value = label;
        } else {
            button.innerHTML = '<span>' + label + '</span>';
        }

        pettyShowLoadingModal(label, button.getAttribute('data-loading-copy') || 'Please wait a moment.');
    }

    function pettySetButtonSavedState(button, savedLabel){
        pettyHideLoadingModal();
        if (!(button instanceof HTMLElement)) return;
        const label = String(savedLabel || 'Saved');
        button.disabled = true;
        if (button.tagName === 'INPUT') {
            button.value = label;
            return;
        }

        button.innerHTML = '<span>' + label + '</span>';
    }

    function pettyRestoreButtonState(button){
        pettyHideLoadingModal();
        if (!(button instanceof HTMLElement)) return;
        const originalLabel = String(button.dataset.originalLabel || '');
        button.disabled = false;
        if (button.tagName === 'INPUT') {
            button.value = originalLabel || button.value || 'Save';
            return;
        }

        if (originalLabel !== '') {
            button.innerHTML = originalLabel;
        }
    }

    window.pettySetButtonLoadingState = pettySetButtonLoadingState;
    window.pettySetButtonSavedState = pettySetButtonSavedState;
    window.pettyRestoreButtonState = pettyRestoreButtonState;

    function pettyInitWorkflows(scope){
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;
        const workflows = root.querySelectorAll('[data-workflow]');

        workflows.forEach(function (workflow) {
            if (!(workflow instanceof HTMLElement)) return;
            if (workflow.dataset.workflowReady === '1') return;
            workflow.dataset.workflowReady = '1';

            const steps = Array.from(workflow.querySelectorAll('[data-step]')).filter(function (step) {
                return step instanceof HTMLElement;
            });
            if (!steps.length) return;

            const unlockAll = workflow.dataset.workflowUnlockAll === '1';
            const openNone = workflow.dataset.workflowOpenNone === '1';
            const forcedOpenStep = String(workflow.dataset.workflowOpenStep || '').trim();

            const firstUnlockedIndex = Math.max(0, steps.findIndex(function (step) {
                return step.dataset.stepUnlocked === '1';
            }));
            let openIndex = steps.findIndex(function (step) {
                return step.dataset.stepOpen === '1';
            });
            if (openIndex < 0) openIndex = firstUnlockedIndex;
            if (openNone) openIndex = -1;

            if (unlockAll) {
                steps.forEach(function (step) {
                    step.dataset.stepUnlocked = '1';
                });
            }

            if (forcedOpenStep !== '') {
                const forcedIndex = steps.findIndex(function (step) {
                    return String(step.dataset.step || '') === forcedOpenStep;
                });
                if (forcedIndex >= 0) {
                    steps[forcedIndex].dataset.stepUnlocked = '1';
                    openIndex = forcedIndex;
                }
            }

            const errorIndex = steps.findIndex(function (step) {
                return Boolean(step.querySelector('.err, .invalid-feedback, [aria-invalid="true"]'));
            });

            if (errorIndex >= 0) {
                openIndex = errorIndex;
            }

            steps.forEach(function (step, index) {
                if (index === 0 || index < firstUnlockedIndex) {
                    step.dataset.stepUnlocked = '1';
                }
                if (errorIndex >= 0 && index <= errorIndex) {
                    step.dataset.stepUnlocked = '1';
                }
            });

            function syncStep(step, index){
                const body = step.querySelector('[data-step-body]');
                const trigger = step.querySelector('[data-step-toggle]');
                const meta = step.querySelector('[data-step-meta]');
                const unlocked = step.dataset.stepUnlocked === '1' || index === 0;
                const complete = step.dataset.stepComplete === '1';
                const open = openIndex === index && unlocked;

                step.classList.toggle('is-locked', !unlocked);
                step.classList.toggle('is-complete', complete);
                step.classList.toggle('is-open', open);

                if (trigger instanceof HTMLButtonElement) {
                    trigger.disabled = !unlocked;
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                }

                if (body instanceof HTMLElement) {
                    body.hidden = !open;
                }

                if (meta instanceof HTMLElement) {
                    meta.textContent = !unlocked ? 'Locked' : (complete ? 'Done' : (open ? 'Open' : 'Ready'));
                }
            }

            function syncAll(){
                steps.forEach(syncStep);
            }

            workflow.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-step-toggle]');
                if (toggle) {
                    const step = toggle.closest('[data-step]');
                    const index = step ? steps.indexOf(step) : -1;
                    if (index >= 0 && step.dataset.stepUnlocked === '1') {
                        openIndex = openIndex === index ? -1 : index;
                        syncAll();
                    }
                    return;
                }

                const nextButton = event.target.closest('[data-step-next]');
                if (nextButton) {
                    const step = nextButton.closest('[data-step]');
                    const index = step ? steps.indexOf(step) : -1;
                    if (index < 0) return;
                    if (!pettyWorkflowValidateStep(step)) return;

                    step.dataset.stepComplete = '1';
                    const nextStep = steps[index + 1];
                    if (nextStep) {
                        nextStep.dataset.stepUnlocked = '1';
                        openIndex = index + 1;
                    }
                    syncAll();
                    return;
                }

                const openButton = event.target.closest('[data-step-open]');
                if (openButton) {
                    const targetStep = String(openButton.getAttribute('data-step-open') || '');
                    const targetIndex = steps.findIndex(function (step) {
                        return String(step.dataset.step || '') === targetStep;
                    });
                    if (targetIndex >= 0 && steps[targetIndex].dataset.stepUnlocked === '1') {
                        openIndex = targetIndex;
                        syncAll();
                    }
                }
            });

            syncAll();
        });
    }

    window.pettyInitWorkflows = pettyInitWorkflows;
    pettyInitWorkflows(document);

    function pettyInitFilterPanels(scope){
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;
        root.querySelectorAll('.pc-filter-panel').forEach(function (panel) {
            if (!(panel instanceof HTMLDetailsElement)) return;
            panel.open = true;
            if (panel.dataset.filterPinned === '1' || panel.hasAttribute('data-filter-pinned')) {
                panel.addEventListener('toggle', function () {
                    if (!panel.open) panel.open = true;
                });
            }
        });
    }

    function pettyResolveListRoot(source){
        if (!(source instanceof HTMLElement)) return null;

        const explicitRootId = String(source.getAttribute('data-pc-list-root-id') || '').trim();
        if (explicitRootId !== '') {
            return document.querySelector('[data-pc-list-root="' + explicitRootId + '"]');
        }

        return source.closest('[data-pc-list-root]');
    }

    function pettySwapListRoot(currentRoot, incomingRoot){
        if (!(currentRoot instanceof HTMLElement) || !(incomingRoot instanceof HTMLElement)) return;

        currentRoot.replaceWith(incomingRoot);
        pettyInitFilterPanels(document);
        pettyInitAutoFilters(document);
        pettyInitAjaxPagination(document);
        pettyInitWorkflows(document);
        document.dispatchEvent(new CustomEvent('petty:list:replaced', {
            detail: {
                rootId: String(incomingRoot.getAttribute('data-pc-list-root') || ''),
            },
        }));
    }

    async function pettyFetchListHtml(url, currentRoot){
        const response = await window.fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-PettyCash-List': '1',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('List refresh failed.');
        }

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const rootId = String(currentRoot.getAttribute('data-pc-list-root') || '');
        const incomingRoot = rootId !== ''
            ? doc.querySelector('[data-pc-list-root="' + rootId + '"]')
            : null;

        if (!(incomingRoot instanceof HTMLElement)) {
            throw new Error('Updated list content was not found.');
        }

        return incomingRoot;
    }

    function pettyBuildFormUrl(form){
        const action = form.getAttribute('action') || window.location.href;
        const method = String(form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'GET') return action;

        const target = new URL(action, window.location.origin);
        const params = new URLSearchParams(new FormData(form));
        params.forEach(function (value, key) {
            if (String(value).trim() !== '') return;
            params.delete(key);
        });
        target.search = params.toString();
        return target.toString();
    }

    async function pettyRefreshList(url, root, options){
        if (!(root instanceof HTMLElement)) {
            window.location.href = url;
            return;
        }

        root.classList.add('is-loading');

        try {
            const incomingRoot = await pettyFetchListHtml(url, root);
            pettySwapListRoot(root, incomingRoot);
            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, '', url);
            }
        } catch (error) {
            window.location.href = url;
        } finally {
            root.classList.remove('is-loading');
            if (options && options.hideModal !== false) {
                pettyHideLoadingModal();
            }
        }
    }

    function pettyInitAutoFilters(scope){
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;
        const debouncers = window.__pettyFilterDebouncers || new WeakMap();
        window.__pettyFilterDebouncers = debouncers;

        root.querySelectorAll('form[data-pc-auto-filter="1"]').forEach(function (form) {
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.pcFilterReady === '1') return;
            form.dataset.pcFilterReady = '1';

            const submitForm = function () {
                if (form.dataset.submitPending === '1') return;

                const listRoot = pettyResolveListRoot(form);
                const wantsAjax = listRoot instanceof HTMLElement
                    && String(listRoot.getAttribute('data-pc-ajax') || '0') === '1';
                const url = pettyBuildFormUrl(form);

                if (wantsAjax) {
                    pettyRefreshList(url, listRoot, { hideModal: true });
                    return;
                }

                pettyShowLoadingModal(
                    form.getAttribute('data-busy-title') || 'Refreshing...',
                    form.getAttribute('data-busy-copy') || 'Loading the latest filtered records.'
                );
                window.location.href = url;
            };

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitForm();
            });

            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                if (!(field instanceof HTMLElement)) return;
                const tagName = field.tagName.toLowerCase();
                const type = tagName === 'input' ? String(field.getAttribute('type') || 'text').toLowerCase() : tagName;
                const usesDebounce = ['search', 'text', 'email', 'tel', 'textarea'].includes(type) || tagName === 'textarea';

                const schedule = function () {
                    if (!usesDebounce) {
                        submitForm();
                        return;
                    }

                    if (debouncers.has(form)) {
                        window.clearTimeout(debouncers.get(form));
                    }

                    const timeoutId = window.setTimeout(submitForm, 280);
                    debouncers.set(form, timeoutId);
                };

                field.addEventListener(usesDebounce ? 'input' : 'change', schedule);
            });
        });
    }

    function pettyInitAjaxPagination(scope){
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;

        root.querySelectorAll('[data-pc-list-root][data-pc-ajax="1"]').forEach(function (listRoot) {
            if (!(listRoot instanceof HTMLElement)) return;
            if (listRoot.dataset.pcPagerReady === '1') return;
            listRoot.dataset.pcPagerReady = '1';

            listRoot.addEventListener('click', function (event) {
                const link = event.target.closest('.petty-pager a, a[data-pc-ajax-link="1"]');
                if (!(link instanceof HTMLAnchorElement)) return;

                const href = link.href;
                if (!href) return;

                event.preventDefault();
                pettyRefreshList(href, listRoot, { hideModal: true });
            });
        });
    }

    pettyInitFilterPanels(document);
    pettyInitAutoFilters(document);
    pettyInitAjaxPagination(document);

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (event.defaultPrevented) return;
        if (form.dataset.jsonSubmit === '1') return;
        if (form.dataset.submitPending === '1') {
            event.preventDefault();
            return;
        }

        const submitter = event.submitter instanceof HTMLElement
            ? event.submitter
            : form.querySelector('button[type="submit"], input[type="submit"]');

        form.dataset.submitPending = '1';
        if (submitter instanceof HTMLElement) {
            pettySetButtonLoadingState(submitter, pettyResolveLoadingLabel(submitter, 'Loading...'));
        }
    }, true);
</script>

@include('partials.back_iconize')

@stack('scripts')
</body>
</html>
