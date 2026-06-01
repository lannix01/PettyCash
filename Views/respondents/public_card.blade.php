<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Verification</title>
    <style>
        :root{
            --bg:#f4f7fb;
            --card:#ffffff;
            --ink:#101828;
            --muted:#667085;
            --line:#e4e7ec;
            --accent:#1d4ed8;
            --accent-dark:#1849a9;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:radial-gradient(circle at top,#eef4ff 0,#f4f7fb 45%,#eef2f8 100%);color:var(--ink)}
        .loader{
            position:fixed;inset:0;display:flex;align-items:center;justify-content:center;gap:12px;
            background:rgba(244,247,251,.96);z-index:40;transition:opacity .22s ease, visibility .22s ease
        }
        .loader.hidden{opacity:0;visibility:hidden}
        .spinner{
            width:22px;height:22px;border-radius:999px;border:3px solid #c7d7fe;border-top-color:var(--accent);
            animation:spin .7s linear infinite
        }
        @keyframes spin{to{transform:rotate(360deg)}}
        .app{opacity:0;transform:translateY(8px);transition:opacity .24s ease, transform .24s ease}
        .app.ready{opacity:1;transform:none}
        .wrap{min-height:100vh;padding:28px 18px;display:flex;align-items:center;justify-content:center}
        .shell{width:100%;max-width:760px}
        .hero{background:linear-gradient(135deg,#102a56,#1849a9);border-radius:28px;padding:24px;color:#fff;box-shadow:0 24px 50px rgba(16,24,40,.18)}
        .eyebrow{font-size:11px;font-weight:900;letter-spacing:.18em;text-transform:uppercase;opacity:.82}
        .title{margin-top:10px;font-size:30px;font-weight:900;line-height:1.02}
        .sub{margin-top:8px;font-size:13px;max-width:520px;color:rgba(255,255,255,.88)}
        .hero-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:16px;margin-top:16px}
        .panel{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);border-radius:22px;padding:16px}
        .person{display:flex;gap:14px;align-items:center}
        .avatar{width:88px;height:104px;border-radius:20px;overflow:hidden;background:#dbe8ff;border:1px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:900;color:#1849a9}
        .avatar img{width:100%;height:100%;object-fit:cover}
        .name{font-size:24px;font-weight:900;line-height:1.08}
        .role{margin-top:6px;font-size:14px;font-weight:700;color:rgba(255,255,255,.88)}
        .chip{display:inline-flex;margin-top:10px;padding:7px 12px;border-radius:999px;background:#dbe8ff;color:#1849a9;font-size:11px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}
        .meta{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
        .meta-card,.actions{background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px;box-shadow:0 14px 30px rgba(16,24,40,.08)}
        .k{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;font-weight:800}
        .v{margin-top:5px;font-size:15px;font-weight:800;color:var(--ink)}
        .actions{margin-top:16px}
        label{display:block;font-size:12px;font-weight:800;color:#344054;margin:14px 0 6px}
        input{width:100%;padding:12px 14px;border:1px solid #d0d5dd;border-radius:14px}
        .btn-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
        button{border:none;border-radius:14px;padding:12px 16px;font-weight:900;cursor:pointer}
        .btn-primary{background:var(--accent);color:#fff}
        .btn-secondary{background:#fff;border:1px solid #d0d5dd;color:#344054}
        .note{margin-top:10px;font-size:12px;color:var(--muted)}
        .alert{margin-top:12px;padding:12px 14px;border-radius:14px;font-size:13px}
        .alert-error{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
        @media(max-width:720px){
            .hero-grid,.meta{grid-template-columns:1fr}
            .person{align-items:flex-start}
        }
    </style>
</head>
<body>
<div class="loader" id="pageLoader">
    <div class="spinner" aria-hidden="true"></div>
    <div>Loading verification...</div>
</div>

<div class="app" id="pageApp">
    <div class="wrap">
        <div class="shell">
            <div class="hero">
                <div class="eyebrow">Staff Verification</div>
                <div class="title">{{ $respondent->name }}</div>
                <div class="sub">{{ $respondent->category ?: \App\Modules\PettyCash\Models\Respondent::CATEGORY_OTHER_STAFF }} • {{ $respondent->statusLabel() }}</div>

                <div class="hero-grid">
                    <div class="panel">
                        <div class="person">
                            <div class="avatar">
                                @if(!empty($photoPreviewDataUri))
                                    <img src="{{ $photoPreviewDataUri }}" alt="Profile photo">
                                @else
                                    {{ strtoupper(substr((string) $respondent->name, 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <div class="name">{{ $respondent->name }}</div>
                                <div class="role">{{ $respondent->profile_title ?: ($respondent->category ?: '-') }}</div>
                                <div class="chip">{{ $respondent->staff_id ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="k" style="color:rgba(255,255,255,.72)">Issued</div>
                        <div class="v" style="color:#fff">{{ optional($respondent->card_generated_at)->format('Y-m-d') ?: '-' }}</div>
                        <div class="k" style="color:rgba(255,255,255,.72);margin-top:12px">Expiry</div>
                        <div class="v" style="color:#fff">{{ optional($respondent->card_expires_at)->format('Y-m-d') ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="meta">
                <div class="meta-card">
                    <div class="k">Phone</div>
                    <div class="v">{{ $respondent->phone ?: '-' }}</div>
                </div>
                <div class="meta-card">
                    <div class="k">Date Created</div>
                    <div class="v">{{ optional($respondent->created_at)->format('Y-m-d') ?: '-' }}</div>
                </div>
            </div>

            <div class="actions">
                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first('password') }}</div>
                @endif

                <form method="POST" action="{{ route('petty.respondents.card.public.download', ['token' => $token]) }}">
                    @csrf
                    <label>Password (phone number)</label>
                    <input type="password" name="password" placeholder="e.g 07XXXXXXXX" required>
                    <div class="btn-row">
                        <button class="btn-primary" type="submit" name="format" value="pdf">Download PDF Card</button>
                        <button class="btn-secondary" type="submit" name="format" value="png">Download PNG Card</button>
                    </div>
                </form>
                <div class="note">Use the registered phone number to download the card.</div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    const loader = document.getElementById('pageLoader');
    const app = document.getElementById('pageApp');
    if (app) app.classList.add('ready');
    if (loader) {
        setTimeout(function () {
            loader.classList.add('hidden');
        }, 120);
    }
});
</script>
</body>
</html>
