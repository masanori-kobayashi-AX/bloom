@extends('layouts.app')
@section('title', 'ホーム')

@section('content')
    <h1>こんにちは、{{ $user->name }}さん</h1>

    @php($roleValue = $role?->value)

    @if($roleValue === 'cast')
        <div class="row" style="gap:10px">
            <div class="card" style="flex:1;text-align:center;margin:8px 0">
                <div style="font-size:28px;font-weight:700">{{ $cast['customerCount'] }}</div>
                <div class="muted" style="font-size:12px">登録顧客</div>
            </div>
            <div class="card" style="flex:1;text-align:center;margin:8px 0">
                <div style="font-size:28px;font-weight:700;color:{{ $cast['overdueActions']>0 ? 'var(--warn)' : 'var(--ink)' }}">{{ $cast['openActions'] }}</div>
                <div class="muted" style="font-size:12px">未対応アクション</div>
            </div>
        </div>

        @if($cast['overdueActions'] > 0)
            <div class="flash err">期限が過ぎたアクションが {{ $cast['overdueActions'] }} 件あります。連絡を忘れていないか確認しましょう。</div>
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
                    <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                        <span>{{ $r->avatar_emoji ?? '👤' }} {{ $r->customer_name }}</span>
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
                <a class="btn sm" href="{{ route('admin.accounts.index') }}">👥 アカウント管理</a>
                <a class="btn sm ghost" href="{{ route('admin.assignments.index') }}">🔗 担当の紐付け</a>
            </div>
        </div>
        <div class="card">
            <h2>店舗全体の集計</h2>
            <p class="muted">在籍数・来店・本指名転換などの指標は Phase 4 でここに表示されます。</p>
        </div>
    @else
        <div class="card">
            <p class="muted">この店舗での役割が設定されていません。店舗責任者にご連絡ください。</p>
        </div>
    @endif
@endsection
