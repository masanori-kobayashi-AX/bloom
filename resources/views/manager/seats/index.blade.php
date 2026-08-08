@extends('layouts.app')
@section('title', '席の管理')
@section('wrapClass', 'wide')

@section('content')
    <h1>席の管理・席割り</h1>

    {{-- 今の席割り --}}
    <div class="card">
        <h2>今の席割り（来店中）</h2>
        @if($seats->isEmpty() && empty($occupancy))
            <p class="muted">席がまだ登録されていません。下の「席を追加」から登録してください。</p>
        @else
        <div class="grid-cards">
            @foreach($seats as $seat)
                @php($here = $occupancy[$seat->name] ?? [])
                <div class="card" style="margin:0;{{ $here ? 'border-color:var(--rose)' : '' }}">
                    <div class="row">
                        <span class="seat-badge">{{ $seat->name }}</span>
                        <span style="flex:1"></span>
                        @if($here)<span class="tag on">使用中</span>@else<span class="tag off">空き</span>@endif
                    </div>
                    @foreach($here as $o)
                        <div style="margin-top:6px">
                            @if($o['rel'])<a href="{{ route('admin.customer.show', $o['rel']) }}" style="color:var(--rose-deep);font-weight:600">{{ $o['name'] }}</a>@else{{ $o['name'] }}@endif
                            <span class="muted" style="font-size:12px">／ 指名：{{ $o['visit']->primaryCast?->display_name ?? '—' }}・{{ $o['visit']->arrived_at->format('H:i') }}〜</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        @endif
        @php($unknown = collect($occupancy)->keys()->reject(fn ($k) => $seats->pluck('name')->contains($k))->filter())
        @if($unknown->isNotEmpty())
            <div class="notice">未登録の席にお客様がいます：{{ $unknown->implode('、') }}（席マスタに追加すると席割りに並びます）</div>
        @endif
    </div>

    {{-- 席マスタ --}}
    <div class="card">
        <h2>席の一覧</h2>
        @forelse($seats as $seat)
            <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                <span class="tag">{{ $seat->name }}</span>
                <span style="flex:1"></span>
                <form method="POST" action="{{ route('admin.seats.destroy', $seat) }}" data-confirm="席「{{ $seat->name }}」を削除しますか？">
                    @csrf @method('DELETE')
                    <button class="btn sm ghost" type="submit">削除</button>
                </form>
            </div>
        @empty
            <p class="muted">席がありません。</p>
        @endforelse

        <form method="POST" action="{{ route('admin.seats.store') }}" style="margin-top:12px">@csrf
            <div class="row" style="gap:8px">
                <input name="name" placeholder="席名（例：VIP1 / テーブルA / 1卓）" required style="flex:2">
                <button class="btn sm" type="submit">席を追加</button>
            </div>
        </form>
        <div class="notice">追加した順に並びます。席名は自由に付けられます（VIP1・テーブルA・1卓 など）。</div>
    </div>
@endsection
