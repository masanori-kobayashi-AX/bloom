@extends('layouts.app')
@section('title', '認証コードの入力')

@section('content')
    <div style="text-align:center;margin:40px 0 8px">
        <div style="font-size:34px;font-weight:700;letter-spacing:.04em">Bloom<span style="color:var(--rose)">.</span></div>
        <div class="muted" style="font-size:13px">2段階認証</div>
    </div>

    <div class="card">
        <h2 style="margin-top:0">認証コードを入力</h2>
        <p class="muted" style="font-size:13px">ご登録のメールに6桁のコードを送信しました。10分以内に入力してください。</p>

        @if(app()->environment('local') && session('dev_2fa_code'))
            <div class="notice" style="border-color:#d9822b">開発用表示：コードは <strong style="font-size:16px">{{ session('dev_2fa_code') }}</strong> です（本番では表示されません）。</div>
        @endif

        <form method="POST" action="{{ route('login.2fa.verify') }}">
            @csrf
            <label for="code">認証コード（6桁）</label>
            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                   style="letter-spacing:.4em;font-size:20px;text-align:center" autofocus required>
            <div style="height:16px"></div>
            <button class="btn" type="submit">ログイン</button>
        </form>

        <form method="POST" action="{{ route('login.2fa.resend') }}" style="margin-top:10px">
            @csrf
            <button class="btn-ghost" type="submit" style="font-size:13px">コードを再送する</button>
        </form>
        <div class="row" style="justify-content:center;margin-top:10px">
            <a href="{{ route('login') }}" class="muted" style="font-size:12px">← ログインに戻る</a>
        </div>
    </div>
@endsection
