@extends('layouts.app')
@section('title', 'アカウント新規作成')

@section('content')
    <h1>アカウント新規作成</h1>

    <div class="card">
        <form method="POST" action="{{ route('admin.accounts.store') }}">
            @csrf
            <label for="role">役割</label>
            <select id="role" name="role" required>
                @foreach($roles as $r)
                    <option value="{{ $r->value }}" @selected(old('role')===$r->value)>{{ $r->label() }}</option>
                @endforeach
            </select>

            <label for="display_name">表示名（源氏名 / スタッフ名）</label>
            <input id="display_name" name="display_name" value="{{ old('display_name') }}" required>

            <label for="name">氏名（本名・管理用）</label>
            <input id="name" name="name" value="{{ old('name') }}" required>

            <label for="login_id">ログインID（英数字・記号 - _ 可）</label>
            <input id="login_id" name="login_id" value="{{ old('login_id') }}" autocapitalize="none" required>

            <label for="position">肩書（黒服・責任者などの任意メモ）</label>
            <input id="position" name="position" value="{{ old('position') }}" placeholder="任意">

            <label for="email">メール（任意）</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="任意">

            <div style="height:16px"></div>
            <button class="btn" type="submit">作成する</button>
            <a class="btn ghost" href="{{ route('admin.accounts.index') }}" style="margin-top:8px">戻る</a>
        </form>
        <div class="notice">作成後に初回パスワードが一度だけ表示されます。本人に伝え、初回ログイン時に本人が変更します。</div>
    </div>
@endsection
