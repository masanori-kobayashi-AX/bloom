@extends('layouts.app')
@section('title', '受信箱')
@section('wrapClass', 'wide')

@section('content')
    <h1>受信箱</h1>

    {{-- 業務連絡（キャスト→黒服） --}}
    <div class="card">
        <h2>キャストからの業務連絡</h2>
        @forelse($requests as $r)
            <div style="border-bottom:1px solid var(--line);padding:10px 0">
                <div class="row">
                    <strong>{{ $r->cast?->display_name }}</strong>
                    <span class="tag">{{ $r->type }}</span>
                    <span class="tag {{ $r->status->value==='done' ? 'on' : 'off' }}">{{ $r->status->label() }}</span>
                    <span style="flex:1"></span>
                    <span class="muted" style="font-size:11px">{{ $r->created_at->format('n/j H:i') }}</span>
                </div>
                <div style="margin:6px 0">{{ $r->body }}</div>
                <form method="POST" action="{{ route('inbox.requests.status', $r) }}" class="row" style="gap:6px">@csrf
                    <select name="status">
                        @foreach($statuses as $val=>$label)
                            <option value="{{ $val }}" @selected($r->status->value===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn sm ghost" type="submit">更新</button>
                </form>
            </div>
        @empty
            <p class="muted">未対応の業務連絡はありません。</p>
        @endforelse
    </div>

    {{-- 相談（公開先が自分に向いているもののみ表示） --}}
    <div class="card">
        <h2>相談・コンディション</h2>
        <div class="notice" style="margin-top:0">公開先に「あなた」が選ばれた相談だけが表示されています。人事評価には使わないでください。</div>
        @forelse($supports as $s)
            <div style="border-bottom:1px solid var(--line);padding:10px 0">
                <div class="row">
                    <strong>{{ $s->cast?->display_name }}</strong>
                    @if($s->condition)<span class="tag off">{{ $s->condition->label() }}</span>@endif
                    <span class="tag">{{ $s->audience->label() }}</span>
                    @if($s->status==='acknowledged')<span class="tag">確認済</span>@endif
                    <span style="flex:1"></span>
                    <span class="muted" style="font-size:11px">{{ $s->created_at->format('n/j H:i') }}</span>
                </div>
                @if($s->body)<div style="margin:6px 0">{{ $s->body }}</div>@endif
                <div class="row" style="gap:6px">
                    @if($s->status==='open')
                    <form method="POST" action="{{ route('inbox.support.ack', $s) }}">@csrf
                        <button class="btn sm ghost" type="submit">確認済みにする</button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('inbox.support.resolve', $s) }}">@csrf
                        <button class="btn sm" type="submit">対応済みにする</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="muted">あなた宛の相談はありません。</p>
        @endforelse
    </div>
@endsection
