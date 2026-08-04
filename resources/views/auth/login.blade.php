@extends('layouts.app')
@section('title', 'ログイン')

@section('content')
    <div style="text-align:center;margin:40px 0 8px">
        <div style="font-size:34px;font-weight:700;letter-spacing:.04em">Bloom<span style="color:var(--rose)">.</span></div>
        <div class="muted" style="font-size:13px">キャスト顧客管理・店舗連携</div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="login_id">ログインID</label>
            <input id="login_id" name="login_id" value="{{ old('login_id') }}" autocomplete="username" autofocus required>

            <label for="password">パスワード</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <div style="height:16px"></div>
            <button class="btn" type="submit">ログイン</button>
        </form>
        <div class="notice">パスワードを忘れた場合は店舗責任者にお伝えください。再設定します。</div>
    </div>
@endsection
