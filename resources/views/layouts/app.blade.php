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
        .wrap.wide{max-width:1180px}
        @media(min-width:900px){ .wrap.wide .grid-cards{grid-template-columns:repeat(3,1fr)} }
        .grid-cards{display:grid;grid-template-columns:1fr;gap:12px}
        @media(min-width:768px){.grid-cards{grid-template-columns:1fr 1fr}}
        .after-likely{color:#b25c72;font-weight:700}
        .hl{background:#fff8e6;border:1px solid #f0e0b0;border-radius:10px;padding:8px 10px;margin:6px 0}
        .hl.today{background:#eef6ff;border-color:#cfe0f5}
        .stat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
        @media(min-width:640px){.stat-grid{grid-template-columns:repeat(4,1fr)}}
        .stat{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;text-align:center}
        .stat-num{font-size:24px;font-weight:700;color:var(--ink)}
        .stat-label{font-size:12px;color:var(--muted);margin-top:2px}
        /* メモの2チャンネル（色で間違い防止）＋重大注意 */
        .ch{border-radius:16px;padding:16px;margin:12px 0;border:1px solid}
        .ch h2{margin:0 0 8px;font-size:16px;display:flex;align-items:center;gap:6px}
        .ch-private{background:#fff2f6;border-color:#f0cdd8}
        .ch-private h2{color:var(--rose-deep)}
        .ch-shared{background:#eef6ff;border-color:#cfe0f5}
        .ch-shared h2{color:#2f6bb0}
        .ch-danger{background:#fdeeeb;border-color:#f0c4bb}
        .ch-danger h2{color:var(--warn)}
        .ch .item{background:rgba(255,255,255,.7);border-radius:10px;padding:8px 10px;margin:6px 0}
        /* 顧客アバター（イニシャル＋色。顔写真は使わない＝プライバシー配慮） */
        .avatar{width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:17px;flex:none}
        .avatar.lg{width:52px;height:52px;font-size:22px}
        /* 席バッジ・営業中モード（黒服） */
        .seat-badge{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:44px;padding:0 10px;border-radius:12px;background:var(--ink);color:#fff;font-weight:700;font-size:18px}
        .cust-name{font-size:19px;font-weight:700}
        .grid-cards.big .card{padding:22px}
        .grid-cards.big .cust-name{font-size:28px}
        .grid-cards.big .seat-badge{min-width:64px;height:56px;font-size:24px}
        .grid-cards.big .hl{font-size:17px}
        .grid-cards.big .meta{display:none}
        /* 進捗バー（目標管理） */
        .bar{height:12px;background:#efe6ea;border-radius:999px;overflow:hidden}
        .bar > span{display:block;height:100%;background:linear-gradient(90deg,var(--rose),var(--gold));border-radius:999px}
        /* 顧客カード上部の判断サマリ */
        .summary th{width:38%;color:var(--muted);font-weight:600;font-size:12px;padding:6px 8px}
        .summary td{padding:6px 8px;font-weight:600}
        details.sec > summary{cursor:pointer;font-weight:700;padding:14px 2px;list-style:none;display:flex;align-items:center;gap:8px}
        details.sec > summary::-webkit-details-marker{display:none}
        details.sec > summary::before{content:'▸';color:var(--muted);transition:transform .15s}
        details.sec[open] > summary::before{transform:rotate(90deg)}
        details.sec{border-top:1px solid var(--line)}
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
            <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();" title="ログアウト" style="margin-left:10px;font-size:18px;text-decoration:none">↩︎</a>
        </div>
    </header>
@endauth

<div class="wrap @yield('wrapClass')">
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
            <a href="{{ route('staff.work.index') }}" class="{{ request()->routeIs('staff.work.*') || request()->routeIs('staff.plans') || request()->routeIs('staff.visits.*') ? 'active' : '' }}"><span class="ic">🍾</span>来店</a>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="ic">📊</span>集計</a>
            <a href="{{ route('inbox.index') }}" class="{{ request()->routeIs('inbox.*') ? 'active' : '' }}"><span class="ic">📥</span>受信箱</a>
            <a href="{{ route('admin.accounts.index') }}" class="{{ request()->routeIs('admin.*') || request()->routeIs('manager.announcements.*') ? 'active' : '' }}"><span class="ic">👥</span>管理</a>
        </nav>
    @elseif($r && $r->value === 'staff')
        <nav class="nav">
            <a href="{{ route('staff.work.index') }}" class="{{ request()->routeIs('staff.work.*') || request()->routeIs('staff.visits.*') ? 'active' : '' }}"><span class="ic">🍾</span>来店中</a>
            <a href="{{ route('staff.plans') }}" class="{{ request()->routeIs('staff.plans') ? 'active' : '' }}"><span class="ic">📅</span>予定</a>
            <a href="{{ route('staff.search') }}" class="{{ request()->routeIs('staff.search') ? 'active' : '' }}"><span class="ic">🔍</span>検索</a>
            <a href="{{ route('staff.after') }}" class="{{ request()->routeIs('staff.after') ? 'active' : '' }}"><span class="ic">🌙</span>アフター</a>
            <a href="{{ route('inbox.index') }}" class="{{ request()->routeIs('inbox.*') ? 'active' : '' }}"><span class="ic">📥</span>受信箱</a>
            <a href="{{ route('announcements.index') }}" class="{{ request()->routeIs('announcements.*') ? 'active' : '' }}"><span class="ic">📣</span>お知らせ</a>
        </nav>
    @elseif($r && $r->value === 'cast')
        <nav class="nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><span class="ic">🏠</span>ホーム</a>
            <a href="{{ route('cast.customers.index') }}" class="{{ request()->routeIs('cast.customers.*') ? 'active' : '' }}"><span class="ic">👤</span>顧客</a>
            <a href="{{ route('cast.customers.create') }}" class="{{ request()->routeIs('cast.customers.create') ? 'active' : '' }}"><span class="ic" style="font-size:26px;line-height:1.1">＋</span>追加</a>
            <a href="{{ route('cast.actions.index') }}" class="{{ request()->routeIs('cast.actions.*') ? 'active' : '' }}"><span class="ic">✓</span>アクション</a>
            <a href="{{ route('announcements.index') }}" class="{{ request()->routeIs('announcements.*') ? 'active' : '' }}"><span class="ic">📣</span>お知らせ</a>
            <a href="{{ route('cast.support.index') }}" class="{{ request()->routeIs('cast.support.*') ? 'active' : '' }}"><span class="ic">💬</span>相談</a>
        </nav>
    @else
        <nav class="nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><span class="ic">🏠</span>ホーム</a>
            <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><span class="ic">↩︎</span>ログアウト</a>
        </nav>
    @endif
    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
@endauth

<script>
// 音声入力補助（店内は騒がしいので補助扱い）。data-voice に対象inputのidを指定。
function bloomVoice(btn){
    var target=document.getElementById(btn.getAttribute('data-voice'));
    if(!target) return;
    var SR=window.SpeechRecognition||window.webkitSpeechRecognition;
    if(!SR){alert('この端末は音声入力に対応していません。キーボードで入力してください。');return;}
    var r=new SR(); r.lang='ja-JP'; r.interimResults=false; r.maxAlternatives=1;
    var orig=btn.textContent; btn.textContent='●'; btn.style.color='#c1462f';
    r.onresult=function(e){var t=e.results[0][0].transcript; target.value=(target.value?target.value+' ':'')+t; target.focus();};
    r.onerror=function(){};
    r.onend=function(){btn.textContent=orig; btn.style.color='';};
    try{r.start();}catch(e){btn.textContent=orig;}
}
</script>
</body>
</html>
