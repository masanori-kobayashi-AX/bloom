@extends('layouts.app')
@section('title', 'パスワードの再設定')

@section('content')
    <div style="text-align:center;margin:40px 0 8px">
        <div style="font-size:34px;font-weight:700;letter-spacing:.04em">Bloom<span style="color:var(--rose)">.</span></div>
        <div class="muted" style="font-size:13px">新しいパスワードの設定</div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">メールアドレス</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>

            <label for="password">新しいパスワード（8文字以上）</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="password_confirmation">新しいパスワード（確認）</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <div style="height:16px"></div>
            <button class="btn" type="submit">パスワードを更新</button>
        </form>
    </div>
@endsection
