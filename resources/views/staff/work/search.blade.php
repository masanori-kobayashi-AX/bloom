@extends('layouts.app')
@section('title', '顧客検索')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <a href="{{ route('staff.work.index') }}" class="muted" style="font-size:13px">← 来店中</a>
        <span style="flex:1"></span>
    </div>
    <h1>顧客検索・来店開始</h1>

    <form method="GET" action="{{ route('staff.search') }}" style="margin:12px 0">
        <input name="q" value="{{ $q }}" placeholder="顧客名・LINE名・旧LINE名・ボトル名・指名キャストで検索" autofocus inputmode="search">
    </form>
    @if($limited ?? false)
        <div class="notice" style="margin-top:0">個人情報保護のため、検索は「本日来店・来店予定・担当キャストの顧客」に限定されています。全顧客の検索は店長にご依頼ください。</div>
    @endif

    @if($q !== '' && $results->isEmpty())
        <div class="card"><p class="muted">「{{ $q }}」に一致する顧客が見つかりません。</p></div>
    @elseif($results->isNotEmpty())
    <div class="grid-cards">
        @foreach($results as $r)
            <div class="card" style="margin:0">
                <div class="row">
                    <div>
                        <strong>{{ $r->customer_name }}</strong>
                        <div class="muted" style="font-size:12px">
                            @if($r->line_display_name)LINE: {{ $r->line_display_name }} ・ @endif指名：{{ $r->cast?->display_name }}
                        </div>
                    </div>
                    <span style="flex:1"></span>
                </div>
                @if($r->customer->keptBottles->isNotEmpty())
                    <div style="font-size:13px;margin-top:4px">🍾 {{ $r->customer->keptBottles->pluck('name')->implode(' / ') }}</div>
                @endif
                <form method="POST" action="{{ route('staff.visits.start') }}" style="margin-top:10px">@csrf
                    <input type="hidden" name="customer_id" value="{{ $r->customer_id }}">
                    <input type="hidden" name="cast_id" value="{{ $r->cast_id }}">
                    <input name="seat" placeholder="席（任意）" style="margin-bottom:8px">
                    <button class="btn sm" type="submit">{{ $r->cast?->display_name ? '来店開始（指名：'.$r->cast->display_name.'）' : '来店開始' }}</button>
                </form>
            </div>
        @endforeach
    </div>
    @endif
@endsection
