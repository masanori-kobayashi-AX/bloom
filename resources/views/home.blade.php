@extends('layouts.app')
@section('title', 'ホーム')

@section('content')
    @php($roleValue = $role?->value)
    <h1>こんにちは、{{ $roleValue === 'cast' ? ($castName ?? $user->name) : $user->name }}さん</h1>

    @if($roleValue === 'cast')
        {{-- 今月の目標と進捗（本人のための指標） --}}
        <div class="card">
            <div class="row"><h2 style="margin:0">今月の目標</h2><span style="flex:1"></span><a href="{{ route('cast.goal.edit') }}" style="font-size:13px">{{ $goal['hasGoal'] ? '変更' : '設定する' }}</a></div>
            @if($goal['hasGoal'])
                <div class="row" style="margin:6px 0 4px"><span class="muted" style="font-size:13px">¥{{ number_format($goal['actual']) }} / ¥{{ number_format($goal['target']) }}</span><span style="flex:1"></span><strong>{{ $goal['rate'] }}%</strong></div>
                <div class="bar"><span style="width:{{ $goal['rate'] }}%"></span></div>
                <div class="muted" style="font-size:11px;margin-top:6px">あと ¥{{ number_format(max(0, $goal['target'] - $goal['actual'])) }} で目標達成。あなたのための目標です（評価には使いません）。</div>
            @else
                <p class="muted" style="margin:6px 0">今月の売上目標を設定すると、進捗がここに表示されます。</p>
            @endif
        </div>

        {{-- 毎日の行動に直結する指標だけに絞る（登録顧客数等はダッシュボード側で） --}}
        <div class="stat-grid" style="margin:8px 0">
            <div class="stat"><div class="stat-num" style="color:{{ $cast['overdueActions']>0 ? 'var(--warn)' : 'var(--ink)' }}">{{ $cast['openActions'] }}</div><div class="stat-label">未対応アクション</div></div>
            <div class="stat"><div class="stat-num">{{ $cast['importantNotVisited']->count() }}</div><div class="stat-label">未来店の重要顧客</div></div>
            <div class="stat"><div class="stat-num">{{ $cast['visitedThisMonth'] }}</div><div class="stat-label">今月の来店</div></div>
            <div class="stat"><div class="stat-num">{{ $cast['honshimeiThisMonth'] }}</div><div class="stat-label">今月の本指名</div></div>
        </div>

        @if($cast['overdueActions'] > 0)
            <div class="flash err">期限が過ぎたアクションが {{ $cast['overdueActions'] }} 件あります。連絡を忘れていないか確認しましょう。</div>
        @endif

        {{-- 次に何をすべきか（気づき。責める表現・順位付けはしない） --}}
        @if($cast['importantNotVisited']->isNotEmpty() || $cast['longAbsent']->isNotEmpty())
        <div class="card">
            <h2>気づき</h2>
            @if($cast['importantNotVisited']->isNotEmpty())
                <div style="padding:6px 0">💡 今月まだ来店していない重要顧客が <strong>{{ $cast['importantNotVisited']->count() }}名</strong> います。
                    @foreach($cast['importantNotVisited']->take(3) as $r)
                        <a href="{{ route('cast.customers.show', $r) }}" class="tag" style="margin:2px">{{ $r->customer_name }}</a>
                    @endforeach
                </div>
            @endif
            @if($cast['longAbsent']->isNotEmpty())
                <div style="padding:6px 0">🕊 しばらく来店していない顧客が <strong>{{ $cast['longAbsent']->count() }}名</strong> います。そろそろ連絡してみては。
                    @foreach($cast['longAbsent']->take(3) as $r)
                        <a href="{{ route('cast.customers.show', $r) }}" class="tag" style="margin:2px">{{ $r->customer_name }}（前回{{ $r->last_visit_days }}日前）</a>
                    @endforeach
                    <div style="margin-top:6px"><a href="{{ route('cast.customers.index', ['status' => '__close__']) }}" style="font-size:13px">→ 追いかけをやめるか検討（クローズ検討リスト）</a></div>
                </div>
            @endif
        </div>
        @endif

        <div class="card">
            <div class="row"><h2 style="margin:0">今日やること</h2><span style="flex:1"></span><a href="{{ route('cast.actions.index') }}" style="font-size:13px">すべて見る</a></div>
            @forelse($cast['dueTodayActions'] as $a)
                <a href="{{ route('cast.customers.show', $a->relationship_id) }}" style="display:block;color:inherit">
                    <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                        <span>{{ $a->kind->label() }}：{{ $a->content }}</span>
                        <span style="flex:1"></span>
                        <span class="tag {{ $a->due_on && $a->due_on->isPast() ? 'off' : '' }}">{{ $a->relationship?->customer_name }}</span>
                    </div>
                </a>
            @empty
                <p class="muted">今日締切のアクションはありません。</p>
            @endforelse
        </div>

        <div class="card">
            <div class="row"><h2 style="margin:0">最近登録した顧客</h2><span style="flex:1"></span><a href="{{ route('cast.customers.index') }}" style="font-size:13px">顧客一覧</a></div>
            @forelse($cast['recent'] as $r)
                <a href="{{ route('cast.customers.show', $r) }}" style="display:block;color:inherit">
                    <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0;gap:8px">
                        @include('partials.avatar', ['name' => $r->customer_name, 'emoji' => $r->avatar_emoji])
                        <span>{{ $r->customer_name }}</span>
                        <span style="flex:1"></span>
                        <span class="tag">{{ $r->status?->label() }}</span>
                    </div>
                </a>
            @empty
                <p class="muted">まだ顧客がいません。右下の「＋追加」から登録できます。</p>
            @endforelse
        </div>
    @elseif($roleValue === 'staff')
        <div class="card">
            <h2>担当黒服ホーム</h2>
            <p class="muted">来店中の顧客・担当キャスト・依頼事項は Phase 3 のタブレット画面で表示されます。</p>
        </div>
    @elseif(in_array($roleValue, ['manager','admin']))
        <div class="card">
            <div class="row"><h2 style="margin:0">来店中のお客様</h2><span style="flex:1"></span><a href="{{ route('staff.work.index') }}" style="font-size:13px">来店運用ページ →</a></div>
            @forelse($presentVisits as $v)
                <a href="{{ route('staff.visits.show', $v) }}" style="display:block;color:inherit">
                    <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                        <span>{{ $v->customer->relationships->firstWhere('cast_id', $v->primary_cast_id)?->customer_name ?? ('顧客#'.$v->customer_id) }}</span>
                        <span class="muted" style="font-size:12px"> 指名：{{ $v->primaryCast?->display_name ?? '—' }}</span>
                        <span style="flex:1"></span>
                        @if($v->after_status)<span class="tag" style="border-color:{{ $v->after_status->badgeColor() }};color:{{ $v->after_status->badgeColor() }}">🌙 {{ $v->after_status->label() }}</span>@endif
                        <span class="muted" style="font-size:12px">{{ $v->arrived_at->format('H:i') }}〜</span>
                    </div>
                </a>
            @empty
                <p class="muted">現在、来店中のお客様はいません。</p>
            @endforelse
        </div>

        <div class="card">
            <h2>店舗管理ホーム</h2>
            <p class="muted">まずはスタッフのアカウントを作成し、キャストと担当黒服を紐付けてください。</p>
            <div class="row" style="margin-top:12px">
                <a class="btn sm" href="{{ route('admin.customers') }}">📋 顧客一覧（全体）</a>
                <a class="btn sm ghost" href="{{ route('admin.accounts.index') }}">👥 アカウント管理</a>
                <a class="btn sm ghost" href="{{ route('admin.assignments.index') }}">🔗 担当の紐付け</a>
                <a class="btn sm ghost" href="{{ route('manager.announcements.index') }}">📣 お知らせ配信</a>
                <a class="btn sm ghost" href="{{ route('inbox.index') }}">📥 受信箱</a>
            </div>
        </div>
        <div class="card">
            <div class="row"><h2 style="margin:0">店舗全体の集計</h2><span style="flex:1"></span><a class="btn sm" href="{{ route('dashboard') }}">📊 ダッシュボードを開く</a></div>
            <p class="muted">在籍数・来店・場内/本指名・売上・キャスト別・顧客資産をまとめて確認できます。</p>
        </div>
    @else
        <div class="card">
            <p class="muted">この店舗での役割が設定されていません。店舗責任者にご連絡ください。</p>
        </div>
    @endif
@endsection
