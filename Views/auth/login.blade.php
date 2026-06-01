<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PettyCash Login</title>
    @php
        $shellLogoPath = '/root/Marcep/netbil/app/Modules/PettyCash/Skybrix_Logo.png';
        $moduleLogoPath = '/root/Marcep/netbil/app/Modules/PettyCash/skybrix_pettycash_logo.png';
        $shellLogo = is_file($shellLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($shellLogoPath)) : null;
        $moduleLogo = is_file($moduleLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($moduleLogoPath)) : null;
    @endphp
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $moduleLogo ?: asset('assets/pettycash ico.png') }}">
    <link rel="shortcut icon" href="{{ $moduleLogo ?: asset('assets/pettycash ico.png') }}">
    <link rel="apple-touch-icon" href="{{ $shellLogo ?: asset('assets/images/logo.png') }}">
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(10,123,99,.12), transparent 24%),
                radial-gradient(circle at bottom right, rgba(8,76,63,.10), transparent 22%),
                #f6f7fb;
            margin:0;
        }
        .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card {
            width:100%;
            max-width:460px;
            background:rgba(255,255,255,.95);
            border:1px solid #e7e9f2;
            border-radius:20px;
            padding:24px;
            box-shadow: 0 18px 50px rgba(16,24,40,0.12);
            backdrop-filter: blur(10px);
        }
        .brand-stack { display:flex; align-items:center; gap:14px; margin-bottom:18px; }
        .brand-chip {
            width:68px;
            height:68px;
            border-radius:20px;
            background:linear-gradient(180deg, #ffffff 0%, #eefaf6 100%);
            border:1px solid rgba(10,123,99,.16);
            box-shadow:0 14px 30px rgba(10,123,99,.15);
            padding:10px;
            flex:0 0 auto;
        }
        .brand-chip img { width:100%; height:100%; object-fit:contain; display:block; }
        .brand-shell { height:28px; width:auto; display:block; margin-bottom:6px; max-width:180px; }
        .eyebrow {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:5px 10px;
            border-radius:999px;
            background:#dff7ef;
            border:1px solid rgba(10,123,99,.14);
            color:#084c3f;
            font-size:11px;
            font-weight:800;
            margin-bottom:10px;
        }
        .eyebrow::before {
            content:"";
            width:6px;
            height:6px;
            border-radius:50%;
            background:#0a7b63;
            display:block;
        }
        h1 { font-size:22px; margin:0 0 8px; }
        p { margin:0 0 18px; color:#667085; font-size:13px; line-height:1.5; }
        label { display:block; font-size:12px; color:#344054; margin:12px 0 6px; }
        input { width:100%; padding:10px 12px; border:1px solid #d0d5dd; border-radius:10px; outline:none; }
        input:focus { border-color:#0a7b63; box-shadow:0 0 0 4px rgba(10,123,99,.12); }
        .row { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; }
        .btn { width:100%; margin-top:14px; background:#0a7b63; color:#fff; border:none; border-radius:12px; padding:12px 12px; font-weight:700; cursor:pointer; box-shadow:0 12px 24px rgba(10,123,99,.18); }
        .btn:hover { filter:brightness(0.95); }
        .err { margin-top:10px; background:#fef3f2; color:#b42318; border:1px solid #fecdca; padding:10px 12px; border-radius:10px; font-size:13px; }
        .small { font-size:12px; color:#475467; display:flex; align-items:center; gap:8px; }
        .footnote { margin-top:14px; font-size:11px; color:#667085; text-align:center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="brand-stack">
            @if($moduleLogo)
                <div class="brand-chip">
                    <img src="{{ $moduleLogo }}" alt="PettyCash">
                </div>
            @endif
            <div>
                @if($shellLogo)
                    <img class="brand-shell" src="{{ $shellLogo }}" alt="Skybrix Internet">
                @endif
                <div class="eyebrow">PettyCash workspace</div>
                <h1>Sign in to PettyCash</h1>
                <p>Manage credits, spendings, meal bills, tokens and reports from one fast operations desk.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="err">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('petty.login.submit') }}">
            @csrf

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <div class="row">
                <label class="small">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
            </div>

            <button class="btn" type="submit">Login</button>
        </form>

        <div class="footnote">Secure access for finance, field, and support teams.</div>
    </div>
</div>
</body>
</html>
