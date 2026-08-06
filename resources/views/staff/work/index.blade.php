@extends('layouts.app')
@section('title', '来店中')
@section('wrapClass', 'wide')

@section('content')
    <datalist id="seatlist">@foreach($seats as $seatName)<option value="{{ $seatName }}">@endforeach</datalist>
    <div class="row">
        <h1 style="margin:0">来店中のお客様</h1>
        <span style="flex:1"></span>
        <a class="btn sm {{ $big ? '' : 'ghost' }}" href="{{ route('staff.work.index', ['big' => $big ? null : 1]) }}">{{ $big ? '通常表示' : '🔍 営業中モード' }}</a>
        <a class="btn sm ghost" href="{{ route('staff.plans') }}">📅 予定（{{ $todayPlanCount }}）</a>
        <a class="btn sm ghost" href="{{ route('staff.search') }}">🔍 来店開始</a>
        <a class="btn sm ghost" href="{{ route('staff.casts') }}">👥 担当キャスト</a>
    </div>

    @if($cards->isEmpty())
        <div class="card"><p class="muted">現在、来店中のお客様はいません。「来店開始」から来店を登録できます。</p></div>
    @else
    <div class="grid-cards {{ $big ? 'big' : '' }}" style="margin-top:12px">
        @foreach($cards as $card)
            @php($v = $card['visit'])@php($c = $v->customer)
            <div class="card" style="margin:0">
                <div class="row" style="gap:12px">
                    <form method="POST" action="{{ route('staff.visits.seat', $v) }}" style="margin:0" title="席を選ぶと即保存">@csrf
                        <select name="seat" onchange="this.form.submit()" class="seat-badge" style="border:none;outline:none;padding:0 6px;cursor:pointer">
                            <option value="">席?</option>
                            @foreach($seats as $sn)<option value="{{ $sn }}" @selected($v->seat===$sn)>{{ $sn }}</option>@endforeach
                            @if($v->seat && ! $seats->contains($v->seat))<option value="{{ $v->seat }}" selected>{{ $v->seat }}</option>@endif
                        </select>
                    </form>
                    <div style="min-width:0">
                        @php($isMgr = auth()->user()->isManager() || auth()->user()->isAdmin())
                        <div class="cust-name">
                            @if($isMgr && $card['relId'])<a href="{{ route('admin.customer.show', $card['relId']) }}" style="color:inherit">{{ $card['name'] }}</a>@else{{ $card['name'] }}@endif
                        </div>
                        <div class="muted" style="font-size:12px">
                            指名：{{ $v->primaryCast?->display_name ?? '—' }} ・ {{ $v->arrived_at->format('H:i') }}〜
                        </div>
                    </div>
                    <span style="flex:1"></span>
                    @if($v->after_status)
                        <span class="tag" style="border-color:{{ $v->after_status->badgeColor() }};color:{{ $v->after_status->badgeColor() }}">🌙 {{ $v->after_status->label() }}</span>
                    @endif
                </div>

                <div class="muted meta" style="font-size:12px;margin-top:6px">来店 {{ $card['pastVisits'] }} 回目@if($card['lastVisit']) ・ 前回 {{ \Illuminate\Support\Carbon::parse($card['lastVisit'])->format('n/j') }}@endif</div>

                {{-- 重大注意（現役のみ）。未入力と「なし」を区別して誤認防止 --}}
                @if($c->alerts->isNotEmpty())
                    <div class="flash err" style="margin:8px 0">
                        @foreach($c->alerts as $al)<div>⚠️ [{{ $al->category?->label() }}] {{ \Illuminate\Support\Str::limit($al->fact, 40) }}</div>@endforeach
                    </div>
                @else
                    <div class="muted" style="font-size:12px;margin:6px 0">重大注意の登録はありません（＝安全確認済みではありません）</div>
                @endif

                {{-- キープボトル --}}
                @if($c->keptBottles->isNotEmpty())
                    <div style="margin:6px 0"><strong>🍾 ボトル：</strong>{{ $c->keptBottles->pluck('name')->implode(' / ') }}</div>
                @endif

                {{-- 今日の申し送り（当日情報） --}}
                @if($card['handovers']->isNotEmpty())
                    @foreach($card['handovers'] as $h)
                        <div class="hl today"><strong>今日の申し送り：</strong>{{ $h->body }}</div>
                    @endforeach
                @endif

                {{-- 大元の共有情報（持続的）＋鮮度表示 --}}
                @if($card['sharedNotes']->isNotEmpty())
                    @foreach($card['sharedNotes'] as $s)
                        <div class="hl">@if($s->category)[{{ $s->category }}] @endif{{ $s->body }}
                            <span class="muted" style="font-size:11px">
                                （{{ $s->updated_at->diffForHumans(['short' => true]) }}@if($s->updated_at->diffInDays(now()) >= 30)・<span style="color:var(--warn)">30日以上未更新</span>@endif）
                            </span>
                        </div>
                    @endforeach
                @endif

                <div class="row" style="margin-top:10px">
                    <a class="btn sm" href="{{ route('staff.visits.show', $v) }}">対応・退店処理</a>
                </div>
            </div>
        @endforeach
    </div>
    @endif
@endsection
