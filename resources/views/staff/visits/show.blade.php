@extends('layouts.app')
@section('title', '来店対応')
@section('wrapClass', 'wide')

@section('content')
    @php($c = $visit->customer)
    @php($name = $c->relationships->firstWhere('cast_id', $visit->primary_cast_id)?->customer_name ?? $c->relationships->first()?->customer_name ?? ('顧客#'.$c->id))
    <div class="row">
        <a href="{{ route('staff.work.index') }}" class="muted" style="font-size:13px">← 来店中一覧</a>
        <span style="flex:1"></span>
        <span class="tag {{ $visit->status->value==='present' ? 'on' : 'off' }}">{{ $visit->status->label() }}</span>
    </div>

    <div class="card">
        <div style="font-size:22px;font-weight:700">{{ $name }}</div>
        <div class="muted" style="font-size:13px">
            指名：{{ $visit->primaryCast?->display_name ?? '—' }}
            @if($visit->seat) ・ 席：{{ $visit->seat }}@endif
            ・ {{ $visit->arrived_at->format('H:i') }}〜
            @if($visit->after_status) ・ <span style="color:{{ $visit->after_status->badgeColor() }};font-weight:700">🌙 {{ $visit->after_status->label() }}</span>@endif
        </div>

        @if($c->alerts->isNotEmpty())
            <div class="flash err" style="margin-top:10px">
                @foreach($c->alerts as $al)
                    <div>⚠️ [{{ $al->category?->label() }}] {{ $al->fact }}@if($al->action_plan) → <em>{{ $al->action_plan }}</em>@endif</div>
                @endforeach
            </div>
        @else
            <div class="muted" style="font-size:12px;margin-top:8px">重大注意の登録はありません（＝安全を確認済みという意味ではありません）</div>
        @endif
        @if($c->keptBottles->isNotEmpty())
            <div style="margin-top:8px"><strong>🍾 キープボトル：</strong>{{ $c->keptBottles->pluck('name')->implode(' / ') }}</div>
        @endif
    </div>

    {{-- この席のキャスト（複数対応：指名＋ヘルプ） --}}
    <div class="card">
        <h2>この席のキャスト</h2>
        <div class="row" style="flex-wrap:wrap;gap:6px">
            @forelse($visit->visitCasts as $vc)
                <span class="tag" style="padding:6px 10px">
                    {{ $vc->cast?->display_name }}
                    <span class="muted" style="font-size:11px">（{{ $vc->cast_id === $visit->primary_cast_id ? '指名' : ($vc->role === 'help' ? 'ヘルプ' : $vc->role) }}）</span>
                    @if($visit->status->value === 'present' && $vc->cast_id !== $visit->primary_cast_id)
                        <form method="POST" action="{{ route('staff.visits.casts.remove', [$visit, $vc]) }}" style="display:inline" onsubmit="return confirm('このキャストを外しますか？')">@csrf @method('DELETE')
                            <button type="submit" style="border:none;background:none;color:var(--warn);cursor:pointer">×</button>
                        </form>
                    @endif
                </span>
            @empty
                <span class="muted">まだ登録がありません。</span>
            @endforelse
        </div>
        @if($visit->status->value === 'present')
        <form method="POST" action="{{ route('staff.visits.casts.add', $visit) }}" class="row" style="gap:6px;margin-top:10px">@csrf
            <select name="cast_id" required style="flex:1">
                <option value="">ヘルプで付いたキャストを追加</option>
                @foreach($activeCasts as $ac)<option value="{{ $ac->id }}">{{ $ac->display_name }}</option>@endforeach
            </select>
            <button class="btn sm ghost" type="submit" style="width:auto">追加</button>
        </form>
        @endif
    </div>

    {{-- 今日の申し送り --}}
    @if($handovers->isNotEmpty())
    <div class="card">
        <h2>今日の申し送り（キャストから）</h2>
        @foreach($handovers as $h)<div class="hl today">{{ $h->body }}</div>@endforeach
    </div>
    @endif

    {{-- 大元の共有情報 --}}
    <div class="card">
        <h2>大元の共有情報（キャストから）</h2>
        @forelse($sharedNotes as $s)
            <div class="hl">@if($s->category)[{{ $s->category }}] @endif{{ $s->body }}
                <span class="muted" style="font-size:11px">（{{ $s->updated_at->diffForHumans(['short' => true]) }}@if($s->updated_at->diffInDays(now()) >= 30)・<span style="color:var(--warn)">30日以上未更新</span>@endif）</span>
            </div>
        @empty
            <p class="muted">共有事項の登録はありません（未確認の可能性があります）。</p>
        @endforelse
    </div>

    @if($visit->status->value === 'present')
    {{-- 対応更新 --}}
    <div class="card">
        <h2>対応メモ・席・ボトル</h2>
        <form method="POST" action="{{ route('staff.visits.update', $visit) }}">@csrf
            <div class="row" style="gap:8px">
                <div style="flex:2"><label for="seat">席（移動したら変更）</label><input id="seat" name="seat" value="{{ $visit->seat }}"></div>
                <div style="flex:1"><label for="party_size">人数</label><input id="party_size" name="party_size" type="number" min="1" inputmode="numeric" value="{{ $visit->party_size }}"></div>
            </div>
            <label for="caution">当日の注意</label>
            <input id="caution" name="caution" value="{{ $visit->caution }}">
            <label for="arrival_note">来店時メモ</label>
            <input id="arrival_note" name="arrival_note" value="{{ $visit->arrival_note }}">
            <label for="bottle_name">ボトル追加（注文・開栓）</label>
            <input id="bottle_name" name="bottle_name" placeholder="ボトル名（任意）">
            <button class="btn sm" type="submit" style="margin-top:10px">更新</button>
        </form>
        @if($visit->bottles->isNotEmpty())
            <div class="muted" style="font-size:12px;margin-top:8px">本日のボトル：{{ $visit->bottles->pluck('name')->implode(' / ') }}</div>
        @endif
    </div>

    {{-- 退店処理 --}}
    <div class="card">
        <h2>退店処理</h2>
        <form method="POST" action="{{ route('staff.visits.leave', $visit) }}">@csrf
            <label for="amount">利用金額（任意・円）</label>
            <input id="amount" name="amount" type="number" min="0" inputmode="numeric" placeholder="例：50000">
            <label for="nomination_type">指名区分</label>
            <select id="nomination_type" name="nomination_type">
                <option value="">未選択</option>
                @foreach($nominations as $val=>$label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
            </select>
            <div class="row" style="gap:16px;margin-top:10px">
                <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="is_zainai" value="1" style="width:auto"> 場内指名あり</label>
                <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="is_honshimei" value="1" style="width:auto"> 本指名あり</label>
            </div>
            <label for="after_note">接客後メモ・次につながる情報</label>
            <input id="after_note" name="after_note">
            <button class="btn" type="submit" style="margin-top:12px">退店を記録する</button>
        </form>
    </div>

    {{-- 来店取消（誤操作） --}}
    <div class="card" style="text-align:center">
        <form method="POST" action="{{ route('staff.visits.cancel', $visit) }}" onsubmit="return confirm('この来店を取り消しますか？（誤って開始した場合のみ。記録は残ります）')">@csrf
            <button class="btn sm ghost danger" type="submit" style="width:auto">誤操作：この来店を取り消す</button>
        </form>
    </div>
    @else
    <div class="card">
        <h2>来店記録</h2>
        <table>
            <tr><th>退店</th><td>{{ $visit->left_at?->format('H:i') }}</td></tr>
            <tr><th>金額</th><td>{{ $visit->amount ? '¥'.number_format($visit->amount) : '—' }}</td></tr>
            <tr><th>指名区分</th><td>{{ $visit->nomination_type?->label() ?? '—' }}{{ $visit->is_zainai ? ' / 場内' : '' }}{{ $visit->is_honshimei ? ' / 本指名' : '' }}</td></tr>
            <tr><th>接客後メモ</th><td>{{ $visit->after_note ?: '—' }}</td></tr>
        </table>
    </div>
    @endif
@endsection
