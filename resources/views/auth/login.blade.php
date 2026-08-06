@extends('layouts.app')
@section('title', $roleLabel ? $roleLabel.'ログイン' : 'ログイン')

@section('content')
    <div style="text-align:center;margin:40px 0 8px">
        <div style="font-size:34px;font-weight:700;letter-spacing:.04em">Bloom<span style="color:var(--rose)">.</span></div>
        <div class="muted" style="font-size:13px">キャスト顧客管理・店舗連携</div>
        @if($roleLabel)
            <div style="margin-top:10px"><span class="tag on">{{ $roleLabel }} 専用ログイン</span></div>
        @endif
    </div>

    <div class="card">
        <form method="POST" action="{{ $role ? route('login.role.store', $role) : route('login.store') }}">
            @csrf
            <label for="login_id">ログインID</label>
            <input id="login_id" name="login_id" value="{{ old('login_id') }}" autocomplete="username" autofocus required>

            <label for="password">パスワード</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <div style="height:16px"></div>
            <button class="btn" type="submit">ログイン</button>
        </form>
        <div class="row" style="justify-content:center;margin-top:12px">
            <a href="{{ route('password.request') }}" class="muted" style="font-size:13px">パスワードをお忘れの方（メールで再設定）</a>
        </div>
        <div class="notice">メール未登録の方（キャストなど）は、パスワードを忘れた場合、店舗責任者にお伝えください。責任者が再設定します。</div>
    </div>
@endsection
