@extends('layouts.app')
@section('title', 'お知らせ')
@section('wrapClass', 'wide')

@section('content')
    <h1>お知らせ</h1>

    {{-- お店からのお知らせ・営業ヒント --}}
    <div class="card">
        <h2>お店からのお知らせ</h2>
        @forelse($announcements as $a)
            @php($isRead = in_array($a->id, $readIds))
            <div style="border-bottom:1px solid var(--line);padding:10px 0;{{ $a->importance==='high' ? 'background:#fff8f6' : '' }}">
                <div class="row">
                    <span class="tag {{ $a->category->value==='tip' ? 'on' : '' }}">{{ $a->category->label() }}</span>
                    @if($a->importance==='high')<span class="tag" style="background:#fdeeeb;color:var(--warn);border-color:#f3ccc4">重要</span>@endif
                    <strong>{{ $a->title }}</strong>
                    <span style="flex:1"></span>
                    @if($isRead)<span class="muted" style="font-size:11px">既読</span>@else<span class="tag" style="background:#fff0f4;color:var(--rose-deep);border-color:#f3d7df">未読</span>@endif
                </div>
                <div style="white-space:pre-wrap;margin:6px 0">{{ $a->body }}</div>
                <div class="muted" style="font-size:12px">
                    {{ $a->published_at?->format('n/j H:i') }}@if($a->expires_on) ・ 掲載〜{{ $a->expires_on->format('n/j') }}@endif
                </div>
                @unless($isRead)
                    <form method="POST" action="{{ route('announcements.read', $a) }}" style="margin-top:8px">@csrf
                        <button class="btn sm ghost" type="submit">既読にする</button>
                    </form>
                @endunless
            </div>
        @empty
            <p class="muted">現在お知らせはありません。</p>
        @endforelse
    </div>

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
                @if($r->reply)
                    <div class="notice" style="margin:6px 0;font-size:13px">↩ あなたの返信：{{ $r->reply }}<span class="muted" style="font-size:11px"> （{{ $r->replied_at?->format('n/j H:i') }}）</span></div>
                @endif
                <form method="POST" action="{{ route('inbox.requests.status', $r) }}" class="row" style="gap:6px">@csrf
                    <select name="status">
                        @foreach($statuses as $val=>$label)
                            <option value="{{ $val }}" @selected($r->status->value===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn sm ghost" type="submit">更新</button>
                </form>
                <form method="POST" action="{{ route('inbox.requests.reply', $r) }}" class="row" style="gap:6px;margin-top:6px">@csrf
                    <input name="reply" type="text" placeholder="返信を書く（キャストに表示）" value="{{ $r->reply }}" style="flex:1">
                    <button class="btn sm" type="submit">返信</button>
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
                @if($s->reply)
                    <div class="notice" style="margin:6px 0;font-size:13px">↩ あなたの返信：{{ $s->reply }}<span class="muted" style="font-size:11px"> （{{ $s->replied_at?->format('n/j H:i') }}）</span></div>
                @endif
                <form method="POST" action="{{ route('inbox.support.reply', $s) }}" class="row" style="gap:6px">@csrf
                    <input name="reply" type="text" placeholder="返信を書く（キャストに表示）" value="{{ $s->reply }}" style="flex:1">
                    <button class="btn sm" type="submit">返信</button>
                </form>
                <div class="row" style="gap:6px;margin-top:6px">
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
