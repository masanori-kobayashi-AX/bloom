@extends('layouts.app')
@section('title', 'お知らせを配信')

@section('content')
    <h1>お知らせを配信</h1>
    <div class="card">
        <form method="POST" action="{{ route('manager.announcements.store') }}">@csrf
            <label for="category">種類</label>
            <select id="category" name="category" required>
                @foreach($categories as $val=>$label)
                    <option value="{{ $val }}" @selected(old('category')===$val)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="title">タイトル</label>
            <input id="title" name="title" value="{{ old('title') }}" required>

            <label for="body">本文</label>
            <textarea id="body" name="body" rows="6" required style="width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:12px;font-size:16px">{{ old('body') }}</textarea>

            <label for="importance">重要度</label>
            <select id="importance" name="importance">
                <option value="low" @selected(old('importance')==='low')>低</option>
                <option value="normal" @selected(old('importance','normal')==='normal')>通常</option>
                <option value="high" @selected(old('importance')==='high')>重要</option>
            </select>

            <label for="expires_on">掲載期限（任意）</label>
            <input id="expires_on" name="expires_on" type="date" value="{{ old('expires_on') }}">

            <div style="height:16px"></div>
            <button class="btn" type="submit">配信する</button>
            <a class="btn ghost" href="{{ route('manager.announcements.index') }}" style="margin-top:8px">戻る</a>
        </form>
        <div class="notice">通知過多を避けるため、重要度と掲載期限を適切に設定してください（§5-13）。営業ヒント・成功事例はキャストの学びに役立ちます。</div>
    </div>
@endsection
