@extends('layouts.app')
@section('title', 'ホーム')

@section('content')
    <h1>こんにちは、{{ $user->name }}さん</h1>

    @php($roleValue = $role?->value)

    @if($roleValue === 'cast')
        <div class="card">
            <h2>あなたのホーム</h2>
            <p class="muted">顧客管理・次のアクション・来店予定は Phase 2 以降でここに表示されます。</p>
            <div class="notice">今は基盤（ログイン・権限・店舗設定）の段階です。まずは安心してお使いいただける土台を作っています。</div>
        </div>
    @elseif($roleValue === 'staff')
        <div class="card">
            <h2>担当黒服ホーム</h2>
            <p class="muted">来店中の顧客・担当キャスト・依頼事項は Phase 3 のタブレット画面で表示されます。</p>
        </div>
    @elseif(in_array($roleValue, ['manager','admin']))
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
