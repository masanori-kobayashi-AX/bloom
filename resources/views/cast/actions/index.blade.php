@extends('layouts.app')
@section('title', 'アクション')

@section('content')
    <h1>次のアクション</h1>

    <div class="card">
        <h2>未対応（期限順）</h2>
        @forelse($open as $a)
            <div class="row" style="border-bottom:1px solid var(--line);padding:10px 0">
                <div style="min-width:0">
                    <a href="{{ route('cast.customers.show', $a->relationship_id) }}" style="color:inherit">
                        <span class="tag">{{ $a->kind->label() }}</span>
                        <strong>{{ $a->relationship?->customer_name }}</strong>
                    </a>
                    <div style="font-size:14px">{{ $a->content }}</div>
                    @if($a->due_on)
                        <div class="muted" style="font-size:11px;color:{{ $a->due_on->isPast() ? 'var(--warn)' : 'var(--muted)' }}">
                            期限 {{ $a->due_on->format('n/j') }}{{ $a->due_on->isPast() ? '（過ぎています）' : '' }}
                        </div>
                    @endif
                </div>
                <span style="flex:1"></span>
                <form method="POST" action="{{ route('cast.actions.complete', $a) }}">@csrf
                    <button class="btn sm" type="submit">完了</button>
                </form>
            </div>
        @empty
            <p class="muted">未対応のアクションはありません。よくできています。</p>
        @endforelse
    </div>

    @if($doneRecently->isNotEmpty())
    <div class="card">
        <details>
            <summary style="cursor:pointer;font-weight:600">最近完了したもの</summary>
            @foreach($doneRecently as $a)
                <div style="border-bottom:1px solid var(--line);padding:8px 0;color:var(--muted)">
                    <span style="text-decoration:line-through">{{ $a->relationship?->customer_name }}／{{ $a->content }}</span>
                    <span style="font-size:11px"> {{ $a->completed_at?->format('n/j') }}</span>
                </div>
            @endforeach
        </details>
    </div>
    @endif
@endsection
