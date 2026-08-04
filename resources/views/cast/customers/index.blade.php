@extends('layouts.app')
@section('title', '顧客')

@section('content')
    <div class="row">
        <h1 style="margin:0">顧客</h1>
        <span style="flex:1"></span>
        <a class="btn sm" href="{{ route('cast.customers.create') }}">＋ 追加</a>
    </div>

    <form method="GET" action="{{ route('cast.customers.index') }}" style="margin:12px 0">
        <input name="q" value="{{ $q }}" placeholder="名前・LINE名・旧LINE名・読み方で検索" inputmode="search">
    </form>

    @if($relationships->isEmpty())
        <div class="card"><p class="muted">
            @if($q !== '')「{{ $q }}」に一致する顧客が見つかりません。@else まだ顧客がいません。「＋追加」から登録できます。@endif
        </p></div>
    @else
        @foreach($relationships as $r)
            <a href="{{ route('cast.customers.show', $r) }}" style="display:block;color:inherit">
                <div class="card" style="margin:10px 0">
                    <div class="row">
                        <span style="font-size:22px">{{ $r->avatar_emoji ?? '👤' }}</span>
                        <div style="min-width:0">
                            <div style="font-weight:600">{{ $r->customer_name }}</div>
                            <div class="muted" style="font-size:12px">
                                @if($r->line_display_name)LINE: {{ $r->line_display_name }}@endif
                            </div>
                        </div>
                        <span style="flex:1"></span>
                        <div style="text-align:right">
                            <span class="tag">{{ $r->status?->label() }}</span>
                            @if($r->importance)<div class="muted" style="font-size:11px;margin-top:3px">{{ $r->importance->label() }}</div>@endif
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
        <div style="margin-top:10px">{{ $relationships->links() }}</div>
    @endif
@endsection
