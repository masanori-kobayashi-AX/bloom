@extends('layouts.app')
@section('title', '今月の目標')

@section('content')
    <h1>今月の目標</h1>
    <div class="card">
        <form method="POST" action="{{ route('cast.goal.update') }}">@csrf @method('PUT')
            <label for="target_amount">売上目標（円）</label>
            <input id="target_amount" name="target_amount" type="number" inputmode="numeric" min="0" value="{{ old('target_amount', $goal?->target_amount) }}" required autofocus placeholder="例：1000000">

            <label for="note">ひとこと（任意）</label>
            <input id="note" name="note" value="{{ old('note', $goal?->note) }}" placeholder="今月の意気込みなど">

            <div style="height:16px"></div>
            <button class="btn" type="submit">目標を保存</button>
            <a class="btn ghost" href="{{ route('home') }}" style="margin-top:8px">戻る</a>
        </form>
        <div class="notice">目標はあなた自身のためのもの。いつでも変更できます。担当黒服も一緒に応援できるよう共有されますが、評価・順位付けには使いません。</div>
    </div>
@endsection
