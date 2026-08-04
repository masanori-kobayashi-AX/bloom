@extends('layouts.app')
@section('title', 'パスワード変更')

@section('content')
    <h1>パスワードの変更</h1>
    @if($forced)
        <div class="flash err">初回ログインです。安全のため、新しいパスワードを設定してください。</div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf
            @method('PUT')

            <label for="current_password">現在のパスワード</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

            <label for="password">新しいパスワード（8文字以上・英字と数字を含む）</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="password_confirmation">新しいパスワード（確認）</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <div style="height:16px"></div>
            <button class="btn" type="submit">変更する</button>
        </form>
    </div>
@endsection
