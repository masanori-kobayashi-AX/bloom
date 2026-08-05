@extends('layouts.app')
@section('title', ($isCastSetup ?? false) ? 'はじめての設定' : 'パスワード変更')

@section('content')
    <h1>{{ ($isCastSetup ?? false) ? 'はじめての設定' : 'パスワードの変更' }}</h1>
    @if($forced && !($isCastSetup ?? false))
        <div class="flash err">初回ログインです。安全のため、新しいパスワードを設定してください。</div>
    @endif
    @if($isCastSetup ?? false)
        <div class="flash ok">ようこそ！源氏名と今月の目標を設定して、パスワードを変えたら完了です。</div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf @method('PUT')

            @if($isCastSetup ?? false)
                <label for="display_name">源氏名（お店での名前）<span style="color:var(--warn)">*</span></label>
                <input id="display_name" name="display_name" value="{{ old('display_name', $castProfile?->display_name) }}" required autofocus>

                <label for="kana">よみ（任意）</label>
                <input id="kana" name="kana" value="{{ old('kana', $castProfile?->kana) }}" placeholder="検索に使います">

                <label for="target_amount">今月の売上目標（任意・円）</label>
                <input id="target_amount" name="target_amount" type="number" inputmode="numeric" min="0" value="{{ old('target_amount') }}" placeholder="例：1000000">
                <div class="notice" style="margin-top:6px">目標はあなた自身のためのものです。いつでも変更できます。評価には使いません。</div>

                <hr style="border:none;border-top:1px solid var(--line);margin:16px 0">
            @endif

            <label for="current_password">現在のパスワード</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

            <label for="password">新しいパスワード（8文字以上・英字と数字）</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="password_confirmation">新しいパスワード（確認）</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <div style="height:16px"></div>
            <button class="btn" type="submit">{{ ($isCastSetup ?? false) ? '設定を完了する' : '変更する' }}</button>
        </form>
    </div>
@endsection
