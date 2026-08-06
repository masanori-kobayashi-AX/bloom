@extends('layouts.app')
@section('title', 'パスワード再設定')

@section('content')
    <div style="text-align:center;margin:40px 0 8px">
        <div style="font-size:34px;font-weight:700;letter-spacing:.04em">Bloom<span style="color:var(--rose)">.</span></div>
        <div class="muted" style="font-size:13px">パスワードの再設定</div>
    </div>

    <div class="card">
        <h2 style="margin-top:0">メールで再設定</h2>
        <p class="muted" style="font-size:13px">ご登録のメールアドレスを入力してください。再設定用のリンクをお送りします。</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">メールアドレス</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
            <div style="height:16px"></div>
            <button class="btn" type="submit">再設定リンクを送る</button>
        </form>
        <div class="notice">メールを登録していない方（キャストなど）は、店舗責任者にお伝えください。責任者がパスワードを再設定します。</div>
        <div class="row" style="justify-content:center;margin-top:10px">
            <a href="{{ route('login') }}" class="muted" style="font-size:12px">← ログインに戻る</a>
        </div>
    </div>
@endsection
