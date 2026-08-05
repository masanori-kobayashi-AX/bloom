@extends('layouts.app')
@section('title', '相談')

@section('content')
    <h1>相談・コンディション</h1>

    <div class="card">
        <h2>今日のコンディション・相談を伝える</h2>
        <form method="POST" action="{{ route('cast.support.store') }}">@csrf
            <label for="audience">誰に伝える？</label>
            <select id="audience" name="audience" required>
                @foreach($audiences as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>

            <label for="condition">今の状態（任意）</label>
            <select id="condition" name="condition">
                <option value="">選ばない</option>
                @foreach($conditions as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>

            <label for="body">内容（任意）</label>
            <input id="body" name="body" placeholder="話したいこと・相談したいこと">

            <button class="btn sm" type="submit" style="margin-top:12px">共有する</button>
        </form>
        <div class="notice">担当黒服のみ／責任者のみ、伝える相手をあなたが選べます。ここでの内容は人事評価には使いません。</div>
    </div>

    <div class="card">
        <h2>伝えた相談</h2>
        @forelse($supports as $s)
            <div style="border-bottom:1px solid var(--line);padding:8px 0">
                <span class="tag">{{ $s->audience->label() }}</span>
                @if($s->condition)<span class="tag off">{{ $s->condition->label() }}</span>@endif
                @if($s->status==='resolved')<span class="tag on">対応済</span>@elseif($s->status==='acknowledged')<span class="tag">確認済</span>@endif
                @if($s->body)<div style="margin-top:4px">{{ $s->body }}</div>@endif
                <div class="muted" style="font-size:11px">{{ $s->created_at->format('n/j H:i') }}</div>
            </div>
        @empty
            <p class="muted">まだありません。</p>
        @endforelse
    </div>

    <div class="card">
        <h2>担当黒服への連絡</h2>
        @forelse($requests as $r)
            <div style="border-bottom:1px solid var(--line);padding:8px 0">
                <span class="tag">{{ $r->type }}</span>
                <span class="tag {{ $r->status->value==='done' ? 'on' : 'off' }}">{{ $r->status->label() }}</span>
                @if($r->customer)<span class="muted" style="font-size:12px"> {{ $r->customer_id ? '顧客あり' : '' }}</span>@endif
                <div style="margin-top:4px">{{ $r->body }}</div>
                <div class="muted" style="font-size:11px">{{ $r->created_at->format('n/j H:i') }}</div>
            </div>
        @empty
            <p class="muted">まだありません。顧客カードから担当黒服に依頼できます。</p>
        @endforelse
    </div>
@endsection
