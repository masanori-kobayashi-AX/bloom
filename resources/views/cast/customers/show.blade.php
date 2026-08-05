@extends('layouts.app')
@section('title', $rel->customer_name)

@section('content')
    @php($c = $rel->customer)
    @php($openAlerts = $c->alerts->where('resolved', false))
    @php($lastVisit = $pastVisits->first())
    @php($nextPlan = $upcomingPlans->first())
    @php($topAction = $rel->nextActions->where('completed', false)->sortBy('due_on')->first())

    <div class="row">
        <a href="{{ route('cast.customers.index') }}" class="muted" style="font-size:13px">← 顧客一覧</a>
        <span style="flex:1"></span>
        @if($canEdit)<a class="btn sm ghost" href="{{ route('cast.customers.edit', $rel) }}">編集</a>@endif
    </div>

    {{-- 次の判断サマリ（常時上部・営業中はここだけ見れば足りる） --}}
    <div class="card">
        <div class="row">
            <span style="font-size:32px">{{ $rel->avatar_emoji ?? '👤' }}</span>
            <div style="min-width:0">
                <div style="font-size:20px;font-weight:700">{{ $rel->customer_name }}</div>
                <div class="muted" style="font-size:12px">
                    @if($rel->line_display_name)LINE: {{ $rel->line_display_name }}@endif
                </div>
            </div>
            <span style="flex:1"></span>
            <div style="text-align:right">
                <span class="tag on">{{ $rel->status?->label() }}</span>
                @if($rel->importance)<div class="muted" style="font-size:11px;margin-top:4px">{{ $rel->importance->label() }}</div>@endif
            </div>
        </div>

        @if($openAlerts->isNotEmpty())
            <div class="ch ch-danger" style="margin:12px 0 0;padding:10px 12px">
                @foreach($openAlerts as $al)<div>⚠️ [{{ $al->category?->label() }}] {{ \Illuminate\Support\Str::limit($al->fact, 40) }}</div>@endforeach
            </div>
        @endif

        <table class="summary" style="margin-top:10px">
            <tr><th>最終来店</th><td>{{ $lastVisit ? $lastVisit->arrived_at->format('n/j') : '—' }}</td></tr>
            <tr><th>次回来店予定</th><td>{{ $nextPlan ? $nextPlan->planned_date->format('n/j') : '—' }}</td></tr>
            <tr><th>次のアクション</th><td>{{ $topAction ? ($topAction->kind->label().'：'.$topAction->content) : '—' }}</td></tr>
        </table>

        @if($canEdit)
        <details style="margin-top:8px">
            <summary style="cursor:pointer;font-size:13px;color:var(--rose-deep)">ステータスを変える</summary>
            <form method="POST" action="{{ route('cast.customers.status', $rel) }}" style="margin-top:8px">@csrf
                <div class="chip-row">
                    @foreach($statuses as $val => $label)
                        <button class="chip" name="status" value="{{ $val }}" type="submit" style="{{ $rel->status?->value===$val ? 'background:var(--rose);color:#fff;border-color:var(--rose)' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </form>
        </details>
        @endif
    </div>

    {{-- 🌸 私だけのメモ（本人＋オーナーのみ） --}}
    <div class="ch ch-private" id="private">
        <h2>🌸 私だけのメモ</h2>
        @if($canViewPrivate)
            @forelse($privateNotes as $n)
                <div class="item">{{ $n->body }}<div class="muted" style="font-size:11px">{{ $n->created_at->format('n/j H:i') }}</div></div>
            @empty
                <p class="muted" style="margin:4px 0">まだありません。あなた（と運営）だけが見られます。</p>
            @endforelse
            @if($canEdit)
            <form method="POST" action="{{ route('cast.customers.notes.private', $rel) }}" style="margin-top:8px">@csrf
                <input name="body" placeholder="会話・趣味・次の話題など" required>
                <button class="btn sm" type="submit" style="margin-top:8px">私だけのメモに追加</button>
            </form>
            @endif
        @else
            <p class="muted">閲覧権限がありません。</p>
        @endif
    </div>

    {{-- 💙 お店と共有（黒服・店長に見える） --}}
    <div class="ch ch-shared" id="share">
        <h2>💙 お店と共有</h2>
        @foreach($todayHandovers as $h)
            <div class="item">🗒 <span class="tag" style="border-color:#cfe0f5;color:#2f6bb0">今日だけ</span> {{ $h->body }}</div>
        @endforeach
        @forelse($rel->sharedNotes as $s)
            <div class="item">@if($s->category)<span class="tag">{{ $s->category }}</span> @endif{{ $s->body }}<div class="muted" style="font-size:11px">{{ $s->created_at->format('n/j H:i') }}</div></div>
        @empty
            @if($todayHandovers->isEmpty())<p class="muted" style="margin:4px 0">まだありません。黒服・店長に伝えたいことを共有できます。</p>@endif
        @endforelse
        @if($canEdit)
        <form method="POST" action="{{ route('cast.customers.notes.shared', $rel) }}" style="margin-top:8px">@csrf
            <div class="chip-row" style="margin-bottom:8px">
                @foreach(['対応依頼','ボトル','席の希望','接客の注意','苦手','準備','会計'] as $cat)
                    <label class="chip"><input type="radio" name="category" value="{{ $cat }}" style="display:none" onchange="this.closest('.chip-row').querySelectorAll('.chip').forEach(c=>c.style.background='#fff');this.closest('.chip').style.background='#dbeafe'"> {{ $cat }}</label>
                @endforeach
            </div>
            <input name="body" placeholder="黒服・店長に伝えたいこと" required>
            <label style="display:flex;align-items:center;gap:6px;margin:8px 0;font-size:13px"><input type="checkbox" name="today_only" value="1" style="width:auto"> 今日だけの申し送りにする</label>
            <button class="btn sm" type="submit" style="background:#2f6bb0">お店と共有</button>
        </form>
        @endif
    </div>

    {{-- ⚠️ 重大注意（安全・金銭・トラブルのみ） --}}
    <details class="sec" id="alerts">
        <summary>⚠️ 重大注意（安全・金銭・トラブル）@if($c->alerts->isNotEmpty())<span class="tag ch-danger" style="color:var(--warn)">{{ $c->alerts->count() }}</span>@endif</summary>
        <div style="padding:4px 2px 12px">
            @forelse($c->alerts as $al)
                <div class="ch ch-danger" style="margin:6px 0;padding:10px 12px">
                    <span class="tag" style="background:#fff;color:var(--warn);border-color:#f0c4bb">{{ $al->category?->label() }}</span>
                    <div><strong>事実：</strong>{{ $al->fact }}</div>
                    @if($al->subjective)<div class="muted"><strong>所感：</strong>{{ $al->subjective }}</div>@endif
                </div>
            @empty
                <p class="muted">記録はありません。トラブルや安全上の懸念だけを記録してください。</p>
            @endforelse
            @if($canEdit)
            <form method="POST" action="{{ route('cast.customers.alerts.store', $rel) }}" style="margin-top:8px">@csrf
                <select name="category" required>
                    @foreach(\App\Enums\AlertCategory::options() as $val=>$label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                </select>
                <input name="fact" placeholder="事実（起きたこと）" required style="margin-top:8px">
                <input name="subjective" placeholder="所感（任意）" style="margin-top:8px">
                <select name="severity" style="margin-top:8px"><option value="low">低</option><option value="mid" selected>中</option><option value="high">高</option></select>
                <button class="btn sm danger" type="submit" style="margin-top:8px">記録する</button>
            </form>
            @endif
        </div>
    </details>

    {{-- 次のアクション --}}
    <details class="sec" open id="actions">
        <summary>✓ 次のアクション</summary>
        <div style="padding:4px 2px 12px">
            @forelse($rel->nextActions as $a)
                <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                    <div>
                        <span class="tag">{{ $a->kind->label() }}</span>
                        <span style="{{ $a->completed ? 'text-decoration:line-through;color:var(--muted)' : '' }}">{{ $a->content }}</span>
                        @if($a->due_on)<span class="muted" style="font-size:11px"> 〜{{ $a->due_on->format('n/j') }}</span>@endif
                    </div>
                    <span style="flex:1"></span>
                    @if(!$a->completed && $canEdit)
                        <form method="POST" action="{{ route('cast.actions.complete', $a) }}">@csrf<button class="btn sm ghost" type="submit">完了</button></form>
                    @elseif($a->completed)<span class="tag on">完了</span>@endif
                </div>
            @empty
                <p class="muted">アクションはありません。</p>
            @endforelse
            @if($canEdit)
            <form method="POST" action="{{ route('cast.customers.actions.store', $rel) }}" style="margin-top:8px">@csrf
                <select name="kind">@foreach(\App\Enums\NextActionKind::options() as $val=>$label)<option value="{{ $val }}">{{ $label }}</option>@endforeach</select>
                <input name="content" placeholder="やること（例：来週お礼LINE）" required style="margin-top:8px">
                <div class="chip-row" style="margin-top:8px">
                    <label class="chip"><input type="radio" name="due_choice" value="today" style="display:none" onclick="document.getElementById('due_on').value='{{ now()->toDateString() }}'"> 今日</label>
                    <label class="chip"><input type="radio" name="due_choice" value="tomorrow" style="display:none" onclick="document.getElementById('due_on').value='{{ now()->addDay()->toDateString() }}'"> 明日</label>
                    <label class="chip"><input type="radio" name="due_choice" value="week" style="display:none" onclick="document.getElementById('due_on').value='{{ now()->addWeek()->toDateString() }}'"> 今週中</label>
                    <label class="chip"><input type="radio" name="due_choice" value="none" style="display:none" onclick="document.getElementById('due_on').value=''"> 未定</label>
                </div>
                <input id="due_on" name="due_on" type="date" style="margin-top:8px">
                <button class="btn sm" type="submit" style="margin-top:8px">アクションを追加</button>
            </form>
            @endif
        </div>
    </details>

    {{-- 基本情報 --}}
    <details class="sec">
        <summary>📇 基本情報</summary>
        <table style="padding:4px 0 12px">
            <tr><th>好きな飲み物</th><td>{{ $rel->favorite_drink ?: '—' }}</td></tr>
            <tr><th>趣味・関心</th><td>{{ $rel->hobby ?: '—' }}</td></tr>
            <tr><th>よく来る曜日</th><td>{{ $rel->usual_weekday ?: '—' }}</td></tr>
            <tr><th>年代・職業</th><td>{{ collect([$c->age_range,$c->occupation,$c->area])->filter()->implode('・') ?: '—' }}</td></tr>
            <tr><th>誕生日</th><td>{{ $c->birthday?->format('Y-m-d') ?: '—' }}</td></tr>
            <tr><th>LINE交換日</th><td>{{ $rel->line_exchanged_on?->format('Y-m-d') ?: '—' }}</td></tr>
            <tr><th>次に話したいこと</th><td>{{ $rel->next_talk ?: '—' }}</td></tr>
        </table>
    </details>

    {{-- キープボトル --}}
    <details class="sec">
        <summary>🍾 キープボトル @if($c->keptBottles->isNotEmpty())<span class="tag">{{ $c->keptBottles->count() }}</span>@endif</summary>
        <div style="padding:4px 2px 12px">
            @forelse($c->keptBottles as $b)
                <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                    <span>🍾 {{ $b->name }}@if($b->opened_on)<span class="muted" style="font-size:11px"> （{{ $b->opened_on->format('n/j') }}〜）</span>@endif</span>
                    <span style="flex:1"></span>
                    @if($canEdit)<form method="POST" action="{{ route('cast.bottles.empty', $b) }}">@csrf<button class="btn sm ghost" type="submit">空き</button></form>@endif
                </div>
            @empty
                <p class="muted">キープボトルはありません。</p>
            @endforelse
            @if($canEdit)
            <form method="POST" action="{{ route('cast.customers.bottles.store', $rel) }}" style="margin-top:8px">@csrf
                <input name="name" placeholder="ボトル名" required>
                <button class="btn sm" type="submit" style="margin-top:8px">ボトルを登録</button>
            </form>
            @endif
        </div>
    </details>

    {{-- 来店予定 --}}
    <details class="sec">
        <summary>📅 来店予定 @if($upcomingPlans->isNotEmpty())<span class="tag">{{ $upcomingPlans->count() }}</span>@endif</summary>
        <div style="padding:4px 2px 12px">
            @forelse($upcomingPlans as $p)
                <div style="border-bottom:1px solid var(--line);padding:8px 0">
                    {{ $p->planned_date->format('n/j') }}{{ $p->planned_time ? ' '.\Illuminate\Support\Str::substr($p->planned_time,0,5) : '' }}
                    @if($p->dohan) ・同伴@endif <span class="tag {{ $p->status->value==='confirmed' ? 'on' : '' }}">{{ $p->status->label() }}</span>
                    @if($p->note)<div class="muted" style="font-size:12px">{{ $p->note }}</div>@endif
                </div>
            @empty
                <p class="muted">来店予定はありません。</p>
            @endforelse
            @if($canEdit)
            <form method="POST" action="{{ route('cast.customers.plans.store', $rel) }}" style="margin-top:8px">@csrf
                <input name="planned_date" type="date" required>
                <input name="planned_time" type="time" style="margin-top:8px">
                <label style="display:flex;align-items:center;gap:6px;margin:8px 0;font-size:13px"><input type="checkbox" name="dohan" value="1" style="width:auto"> 同伴</label>
                <input name="note" placeholder="黒服への依頼・注意（任意）">
                <button class="btn sm" type="submit" style="margin-top:8px">来店予定を登録</button>
            </form>
            @endif
        </div>
    </details>

    {{-- 来店履歴 --}}
    <details class="sec">
        <summary>🕘 来店履歴 @if($pastVisits->isNotEmpty())<span class="tag">{{ $pastVisits->count() }}</span>@endif</summary>
        <div style="padding:4px 2px 12px">
            @forelse($pastVisits as $v)
                <div style="border-bottom:1px solid var(--line);padding:8px 0">
                    <strong>{{ $v->arrived_at->format('Y/n/j') }}</strong>
                    @if($v->amount) ・ ¥{{ number_format($v->amount) }}@endif
                    @php($nom = $v->is_honshimei ? '本指名' : ($v->is_zainai ? '場内指名' : $v->nomination_type?->label()))
                    @if($nom) ・ {{ $nom }}@endif
                    @if($v->after_note)<div class="muted" style="font-size:12px">{{ $v->after_note }}</div>@endif
                </div>
            @empty
                <p class="muted">来店履歴はまだありません。</p>
            @endforelse
        </div>
    </details>

    {{-- アフター見込み（来店中のみ） --}}
    @if($canEdit && $presentVisit)
    <details class="sec" open>
        <summary>🌙 アフター見込み（来店中）</summary>
        <div style="padding:4px 2px 12px">
            @if($presentVisit->after_status)<div class="hl">現在：{{ $presentVisit->after_status->label() }}</div>@endif
            <form method="POST" action="{{ route('cast.customers.after.update', $rel) }}">@csrf
                <div class="chip-row">
                    @foreach(\App\Enums\AfterStatus::options() as $val=>$label)
                        <button class="chip" name="after_status" value="{{ $val }}" type="submit" style="{{ $presentVisit->after_status?->value===$val ? 'background:var(--rose);color:#fff;border-color:var(--rose)' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </form>
            <div class="notice">店長・黒服が「誰がどのお客様とアフターか」を把握できます。</div>
        </div>
    </details>
    @endif

    {{-- 担当黒服への連絡 --}}
    @if($canEdit)
    <details class="sec" id="staff-request">
        <summary>📥 担当黒服への連絡（業務）</summary>
        <div style="padding:4px 2px 12px">
            <form method="POST" action="{{ route('cast.customers.staff-request.store', $rel) }}">@csrf
                <select name="type">@foreach(['対応依頼','来店予定共有','ボトル確認','接客の注意','フォロー希望','その他'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select>
                <input name="body" placeholder="担当黒服にお願い・共有したいこと" required style="margin-top:8px">
                <button class="btn sm" type="submit" style="margin-top:8px">担当黒服に連絡</button>
            </form>
        </div>
    </details>
    @endif
@endsection
