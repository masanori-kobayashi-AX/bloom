@extends('layouts.app')
@section('title', '今日の来店予定')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <a href="{{ route('staff.work.index') }}" class="muted" style="font-size:13px">← 来店中</a>
        <span style="flex:1"></span>
    </div>
    <h1>今日の来店予定</h1>

    @if($plans->isEmpty())
        <div class="card"><p class="muted">今日の来店予定はありません。</p></div>
    @else
    <div class="grid-cards">
        @foreach($plans as $p)
            <div class="card" style="margin:0">
                <div class="row">
                    <div>
                        <strong>{{ $p->customer->relationships->firstWhere('cast_id', $p->cast_id)?->customer_name ?? '顧客' }}</strong>
                        <span class="muted">／ 指名：{{ $p->cast?->display_name }}</span>
                    </div>
                    <span style="flex:1"></span>
                    <span class="tag {{ $p->status->value==='confirmed' ? 'on' : '' }}">{{ $p->status->label() }}</span>
                </div>
                <div class="muted" style="font-size:13px;margin-top:4px">
                    {{ $p->planned_time ? \Illuminate\Support\Str::substr($p->planned_time,0,5) : '時刻未定' }}
                    @if($p->party_size) ・ {{ $p->party_size }}名@endif
                    @if($p->dohan) ・ 同伴@endif
                </div>
                @if($p->bottle_note)<div style="margin-top:4px">🍾 {{ $p->bottle_note }}</div>@endif
                @if($p->prep)<div class="hl">事前準備：{{ $p->prep }}</div>@endif
                @if($p->note)<div class="hl today">依頼・注意：{{ $p->note }}</div>@endif

                <div class="row" style="margin-top:10px">
                    @if($p->status->value !== 'confirmed' && $p->status->value !== 'arrived')
                        <form method="POST" action="{{ route('staff.plans.confirm', $p) }}">@csrf
                            <button class="btn sm ghost" type="submit">確認済みにする</button>
                        </form>
                    @endif
                    @if($p->status->value !== 'arrived')
                        <form method="POST" action="{{ route('staff.visits.start') }}">@csrf
                            <input type="hidden" name="visit_plan_id" value="{{ $p->id }}">
                            <button class="btn sm" type="submit">来店開始</button>
                        </form>
                    @else
                        <span class="tag on">来店済み</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif
@endsection
