<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bloom')｜{{ config('app.name') }}</title>
    <style>
        :root{
            --rose:#d6889b; --rose-deep:#b25c72; --gold:#c9a227;
            --ink:#2b2430; --muted:#8a8090; --line:#efe6ea;
            --bg:#fbf7f8; --card:#ffffff; --ok:#2f8f6b; --warn:#c1462f;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:-apple-system,"Hiragino Sans","Noto Sans JP",sans-serif;
            background:var(--bg);color:var(--ink);line-height:1.6;-webkit-text-size-adjust:100%}
        a{color:var(--rose-deep);text-decoration:none}
        .wrap{max-width:640px;margin:0 auto;padding:16px;padding-bottom:96px}
        header.topbar{position:sticky;top:0;z-index:10;background:rgba(251,247,248,.92);
            backdrop-filter:blur(6px);border-bottom:1px solid var(--line)}
        .topbar .inner{max-width:640px;margin:0 auto;padding:12px 16px;display:flex;align-items:center;gap:10px}
        .brand{font-weight:700;letter-spacing:.04em;font-size:18px}
        .brand .dot{color:var(--rose)}
        .spacer{flex:1}
        .role-badge{font-size:12px;background:#fff0f4;color:var(--rose-deep);border:1px solid #f3d7df;
            padding:3px 10px;border-radius:999px}
        .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px;margin:14px 0;
            box-shadow:0 1px 2px rgba(43,36,48,.03)}
        h1{font-size:22px;margin:.2em 0 .6em}
        h2{font-size:17px;margin:1.2em 0 .5em}
        label{display:block;font-size:13px;color:var(--muted);margin:12px 0 4px}
        input,select{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:12px;font-size:16px;background:#fff}
        input:focus,select:focus{outline:none;border-color:var(--rose)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%;
            padding:13px 16px;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;
            background:var(--rose);color:#fff}
        .btn:active{transform:translateY(1px)}
        .btn.gold{background:var(--gold)}
        .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
        .btn.danger{background:var(--warn)}
        .btn.sm{width:auto;padding:8px 14px;font-size:14px;border-radius:10px}
        .muted{color:var(--muted)}
        .flash{padding:12px 14px;border-radius:12px;margin:10px 0;font-size:14px}
        .flash.ok{background:#eafaf3;border:1px solid #bfe8d6;color:var(--ok)}
        .flash.err{background:#fdeeeb;border:1px solid #f3ccc4;color:var(--warn)}
        .temp{background:#fff8e6;border:1px dashed var(--gold);border-radius:12px;padding:12px 14px;margin:10px 0}
        .temp code{font-size:20px;font-weight:700;letter-spacing:.08em}
        table{width:100%;border-collapse:collapse;font-size:14px}
        th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);vertical-align:middle}
        th{color:var(--muted);font-weight:600;font-size:12px}
        .tag{display:inline-block;font-size:12px;padding:2px 8px;border-radius:999px;border:1px solid var(--line)}
        .tag.on{background:#eafaf3;color:var(--ok);border-color:#bfe8d6}
        .tag.off{background:#f3f0f2;color:var(--muted)}
        .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .nav{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid var(--line);
            display:flex;justify-content:space-around;padding:8px 4px 14px;z-index:20}
        .nav a{flex:1;text-align:center;font-size:11px;color:var(--muted)}
        .nav a.active{color:var(--rose-deep);font-weight:700}
        .nav a .ic{display:block;font-size:20px;line-height:1.4}
        .err-list{color:var(--warn);font-size:13px;margin:8px 0;padding-left:18px}
        .notice{font-size:12px;color:var(--muted);background:#f7f3f5;border-radius:10px;padding:10px 12px;margin-top:8px}
    </style>
</head>
<body>
@auth
    <header class="topbar">
        <div class="inner">
            <span class="brand">Bloom<span class="dot">.</span></span>
            <span class="spacer"></span>
            @if(auth()->user()->role())
                <span class="role-badge">{{ auth()->user()->role()->label() }}</span>
            @endif
        </div>
    </header>
@endauth

<div class="wrap">
    @if(session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif
    @if(session('temp_password'))
        <div class="temp">初回パスワード（この場でご本人に伝えてください。以後は表示されません）：<br><code>{{ session('temp_password') }}</code></div>
    @endif
    @if($errors->any())
        <div class="flash err">
            <ul class="err-list" style="margin:0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</div>

@auth
    @php($r = auth()->user()->role())
    @if($r && in_array($r->value, ['manager','admin']))
        <nav class="nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><span class="ic">🏠</span>ホーム</a>
            <a href="{{ route('admin.accounts.index') }}" class="{{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}"><span class="ic">👥</span>アカウント</a>
            <a href="{{ route('admin.assignments.index') }}" class="{{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}"><span class="ic">🔗</span>担当</a>
            <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><span class="ic">↩︎</span>ログアウト</a>
        </nav>
    @else
        <nav class="nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><span class="ic">🏠</span>ホーム</a>
            <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><span class="ic">↩︎</span>ログアウト</a>
        </nav>
    @endif
    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
@endauth
</body>
</html>
