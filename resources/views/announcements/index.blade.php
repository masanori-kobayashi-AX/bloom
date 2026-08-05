@extends('layouts.app')
@section('title', 'お知らせ')

@section('content')
    <h1>お知らせ・営業ヒント</h1>

    @forelse($announcements as $a)
        @php($isRead = in_array($a->id, $readIds))
        <div class="card" style="{{ $a->importance==='high' ? 'border-color:var(--rose)' : '' }}">
            <div class="row">
                <span class="tag {{ $a->category->value==='tip' ? 'on' : '' }}">{{ $a->category->label() }}</span>
                @if($a->importance==='high')<span class="tag" style="background:#fdeeeb;color:var(--warn);border-color:#f3ccc4">重要</span>@endif
                <span style="flex:1"></span>
                @if($isRead)<span class="muted" style="font-size:12px">既読</span>@else<span class="tag" style="background:#fff0f4;color:var(--rose-deep);border-color:#f3d7df">未読</span>@endif
            </div>
            <h2 style="margin:8px 0 4px">{{ $a->title }}</h2>
            <div style="white-space:pre-wrap">{{ $a->body }}</div>
            <div class="muted" style="font-size:12px;margin-top:8px">
                {{ $a->published_at?->format('n/j H:i') }}
                @if($a->expires_on) ・ 掲載〜{{ $a->expires_on->format('n/j') }}@endif
            </div>
            @unless($isRead)
                <form method="POST" action="{{ route('announcements.read', $a) }}" style="margin-top:10px">@csrf
                    <button class="btn sm ghost" type="submit">既読にする</button>
                </form>
            @endunless
        </div>
    @empty
        <div class="card"><p class="muted">現在お知らせはありません。</p></div>
    @endforelse
@endsection
