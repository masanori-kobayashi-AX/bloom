@extends('layouts.app')
@section('title', 'お知らせ配信')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <h1 style="margin:0">お知らせ配信</h1>
        <span style="flex:1"></span>
        <a class="btn sm" href="{{ route('manager.announcements.create') }}">＋ 新規配信</a>
    </div>

    @if($announcements->isEmpty())
        <div class="card"><p class="muted">まだ配信はありません。</p></div>
    @else
        @foreach($announcements as $a)
            <div class="card">
                <div class="row">
                    <span class="tag">{{ $a->category->label() }}</span>
                    @if($a->importance==='high')<span class="tag" style="background:#fdeeeb;color:var(--warn);border-color:#f3ccc4">重要</span>@endif
                    <span style="flex:1"></span>
                    <span class="muted" style="font-size:12px">既読 {{ $a->reads_count }} / {{ $castCount }}</span>
                </div>
                <h2 style="margin:8px 0 4px">{{ $a->title }}</h2>
                <div class="muted" style="white-space:pre-wrap;font-size:14px">{{ \Illuminate\Support\Str::limit($a->body, 120) }}</div>
                <div class="row" style="margin-top:10px">
                    <span class="muted" style="font-size:12px">{{ $a->published_at?->format('n/j H:i') }}@if($a->expires_on) ・ 〜{{ $a->expires_on->format('n/j') }}@endif</span>
                    <span style="flex:1"></span>
                    <form method="POST" action="{{ route('manager.announcements.destroy', $a) }}" onsubmit="return confirm('このお知らせを取り下げますか？')">
                        @csrf @method('DELETE')
                        <button class="btn sm ghost" type="submit">取り下げ</button>
                    </form>
                </div>
            </div>
        @endforeach
        <div>{{ $announcements->links() }}</div>
    @endif
@endsection
