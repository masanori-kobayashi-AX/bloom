@extends('layouts.app')
@section('title', $rel->customer_name)

@section('content')
    @php($c = $rel->customer)
    <div class="row">
        <a href="{{ route('cast.customers.index') }}" class="muted" style="font-size:13px">← 顧客一覧</a>
        <span style="flex:1"></span>
        @if($canEdit)<a class="btn sm ghost" href="{{ route('cast.customers.edit', $rel) }}">編集</a>@endif
    </div>

    {{-- 重要表示 --}}
    <div class="card">
        <div class="row">
            <span style="font-size:34px">{{ $rel->avatar_emoji ?? '👤' }}</span>
            <div style="min-width:0">
                <div style="font-size:20px;font-weight:700">{{ $rel->customer_name }}</div>
                <div class="muted" style="font-size:13px">
                    @if($rel->line_display_name)LINE: {{ $rel->line_display_name }}@endif
                    @if($c->kana) ・ {{ $c->kana }}@endif
                </div>
            </div>
            <span style="flex:1"></span>
            <div style="text-align:right">
                <span class="tag on">{{ $rel->status?->label() }}</span>
                @if($rel->importance)<div class="muted" style="font-size:11px;margin-top:4px">{{ $rel->importance->label() }}</div>@endif
            </div>
        </div>

        @if($c->alerts->where('resolved', false)->isNotEmpty())
            <div class="flash err" style="margin-top:12px">
                ⚠️ 注意：
                @foreach($c->alerts->where('resolved', false) as $al)
                    <div>・[{{ $al->category?->label() }}] {{ \Illuminate\Support\Str::limit($al->fact, 40) }}</div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ステータス変更（本人のみ） --}}
    @if($canEdit)
    <div class="card">
        <details>
            <summary style="cursor:pointer;font-weight:600">ステータスを変える</summary>
            <form method="POST" action="{{ route('cast.customers.status', $rel) }}" style="margin-top:10px">
                @csrf
                <select name="status">
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}" @selected($rel->status?->value===$val)>{{ $label }}</option>
                    @endforeach
                </select>
                <input name="note" placeholder="ひとことメモ（任意）" style="margin-top:8px">
                <button class="btn sm" type="submit" style="margin-top:10px">変更する</button>
            </form>
        </details>
    </div>
    @endif

    {{-- 基本情報 --}}
    <div class="card">
        <h2>基本情報</h2>
        <table>
            <tr><th style="width:34%">好きな飲み物</th><td>{{ $rel->favorite_drink ?: '—' }}</td></tr>
            <tr><th>趣味・関心</th><td>{{ $rel->hobby ?: '—' }}</td></tr>
            <tr><th>よく来る曜日</th><td>{{ $rel->usual_weekday ?: '—' }}</td></tr>
            <tr><th>来店見込み</th><td>{{ $rel->visit_expectation ?: '—' }}</td></tr>
            <tr><th>年代/職業/エリア</th><td>{{ collect([$c->age_range,$c->occupation,$c->area])->filter()->implode(' / ') ?: '—' }}</td></tr>
            <tr><th>誕生日</th><td>{{ $c->birthday?->format('Y-m-d') ?: '—' }}</td></tr>
            <tr><th>LINE交換日</th><td>{{ $rel->line_exchanged_on?->format('Y-m-d') ?: '—' }}</td></tr>
            <tr><th>本指名化日</th><td>{{ $rel->honshimei_first_on?->format('Y-m-d') ?: '—' }}</td></tr>
            <tr><th>次に話したいこと</th><td>{{ $rel->next_talk ?: '—' }}</td></tr>
        </table>
    </div>

    {{-- 自分用メモ（本人＋オーナーのみ） --}}
    <div class="card" id="private-notes">
        <h2>自分だけのメモ</h2>
        @if($canViewPrivate)
            @forelse($privateNotes as $n)
                <div style="border-bottom:1px solid var(--line);padding:8px 0">
                    <div>{{ $n->body }}</div>
                    <div class="muted" style="font-size:11px">{{ $n->created_at->format('n/j H:i') }}</div>
                </div>
            @empty
                <p class="muted">まだメモはありません。</p>
            @endforelse
            @if($canEdit)
            <form method="POST" action="{{ route('cast.customers.notes.private', $rel) }}" style="margin-top:10px">
                @csrf
                <input name="body" placeholder="会話・趣味・次の話題などを自由に" required>
                <button class="btn sm" type="submit" style="margin-top:8px">自分用メモを追加</button>
            </form>
            @endif
            <div class="notice">このメモは担当黒服・店長には表示されません。</div>
        @else
            <p class="muted">閲覧権限がありません。</p>
        @endif
    </div>

    {{-- 店舗共有事項（黒服共有） --}}
    <div class="card" id="shared-notes">
        <h2>店舗共有事項（黒服・店長に伝える）</h2>
        @forelse($rel->sharedNotes as $s)
            <div style="border-bottom:1px solid var(--line);padding:8px 0">
                @if($s->category)<span class="tag">{{ $s->category }}</span> @endif{{ $s->body }}
                <div class="muted" style="font-size:11px">{{ $s->created_at->format('n/j H:i') }}</div>
            </div>
        @empty
            <p class="muted">まだ共有事項はありません。</p>
        @endforelse
        @if($canEdit)
        <form method="POST" action="{{ route('cast.customers.notes.shared', $rel) }}" style="margin-top:10px">
            @csrf
            <select name="category">
                <option value="">分類（任意）</option>
                @foreach(['対応依頼','ボトル','席の希望','接客の注意','苦手な対応','準備してほしい','会計・案内'] as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            <input name="body" placeholder="担当黒服に伝えたいこと" required style="margin-top:8px">
            <button class="btn sm" type="submit" style="margin-top:8px">共有事項を追加</button>
        </form>
        <div class="notice">これは担当黒服・店長が見られます（自分用メモとは別です）。</div>
        @endif
    </div>

    {{-- 重大注意 --}}
    <div class="card" id="alerts">
        <h2>重大注意情報</h2>
        @forelse($c->alerts as $al)
            <div style="border-bottom:1px solid var(--line);padding:8px 0">
                <span class="tag off">{{ $al->category?->label() }}</span>
                @if($al->severity==='high')<span class="tag" style="background:#fdeeeb;color:var(--warn);border-color:#f3ccc4">重大</span>@endif
                <div><strong>事実：</strong>{{ $al->fact }}</div>
                @if($al->subjective)<div class="muted"><strong>所感：</strong>{{ $al->subjective }}</div>@endif
            </div>
        @empty
            <p class="muted">記録はありません。</p>
        @endforelse
        @if($canEdit)
        <details style="margin-top:10px">
            <summary style="cursor:pointer;font-weight:600;color:var(--warn)">注意情報を記録する</summary>
            <form method="POST" action="{{ route('cast.customers.alerts.store', $rel) }}" style="margin-top:10px">
                @csrf
                <select name="category" required>
                    @foreach(\App\Enums\AlertCategory::options() as $val=>$label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
                <label for="fact">事実（起きたこと）</label>
                <input id="fact" name="fact" required>
                <label for="subjective">所感（任意・主観）</label>
                <input id="subjective" name="subjective">
                <label for="severity">重大度</label>
                <select name="severity">
                    <option value="low">低</option>
                    <option value="mid" selected>中</option>
                    <option value="high">高</option>
                </select>
                <button class="btn sm danger" type="submit" style="margin-top:10px">記録する</button>
            </form>
            <div class="notice">「面倒な客」等の曖昧・侮辱的な表現ではなく、事実を分けて記録してください。</div>
        </details>
        @endif
    </div>

    {{-- 次のアクション --}}
    <div class="card" id="actions">
        <h2>次のアクション</h2>
        @forelse($rel->nextActions as $a)
            <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                <div>
                    <span class="tag">{{ $a->kind->label() }}</span>
                    <span style="{{ $a->completed ? 'text-decoration:line-through;color:var(--muted)' : '' }}">{{ $a->content }}</span>
                    @if($a->due_on)<span class="muted" style="font-size:11px"> 〜{{ $a->due_on->format('n/j') }}</span>@endif
                </div>
                <span style="flex:1"></span>
                @if(!$a->completed && $canEdit)
                    <form method="POST" action="{{ route('cast.actions.complete', $a) }}">@csrf
                        <button class="btn sm ghost" type="submit">完了</button>
                    </form>
                @elseif($a->completed)
                    <span class="tag on">完了</span>
                @endif
            </div>
        @empty
            <p class="muted">アクションはありません。</p>
        @endforelse
        @if($canEdit)
        <form method="POST" action="{{ route('cast.customers.actions.store', $rel) }}" style="margin-top:10px">
            @csrf
            <select name="kind">
                @foreach(\App\Enums\NextActionKind::options() as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <input name="content" placeholder="やること（例：来週お礼LINE）" required style="margin-top:8px">
            <input name="due_on" type="date" style="margin-top:8px">
            <button class="btn sm" type="submit" style="margin-top:8px">アクションを追加</button>
        </form>
        @endif
    </div>

    {{-- 更新履歴（ステータス） --}}
    <div class="card">
        <details>
            <summary style="cursor:pointer;font-weight:600">ステータス履歴</summary>
            <table style="margin-top:8px">
                @forelse($rel->statusHistories as $h)
                    <tr>
                        <td class="muted" style="font-size:12px">{{ $h->created_at?->format('n/j H:i') }}</td>
                        <td>{{ $h->from_status ? (\App\Enums\CustomerStatus::tryFrom($h->from_status)?->label() ?? $h->from_status).' → ' : '' }}{{ \App\Enums\CustomerStatus::tryFrom($h->to_status)?->label() ?? $h->to_status }}</td>
                    </tr>
                @empty
                    <tr><td class="muted">履歴なし</td></tr>
                @endforelse
            </table>
        </details>
    </div>

    {{-- 来店履歴・売上（Phase 3） --}}
    <div class="card"><p class="muted">来店履歴・ボトル・売上は Phase 3（来店運用）で表示されます。</p></div>
@endsection
