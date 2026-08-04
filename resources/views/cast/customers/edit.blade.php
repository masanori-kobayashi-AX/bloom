@extends('layouts.app')
@section('title', '顧客を編集')

@section('content')
    @php($c = $rel->customer)
    <h1>顧客を編集</h1>

    <div class="card">
        <form method="POST" action="{{ route('cast.customers.update', $rel) }}">
            @csrf
            @method('PUT')

            <label for="avatar_emoji">アイコン（絵文字1つ・任意）</label>
            <input id="avatar_emoji" name="avatar_emoji" value="{{ old('avatar_emoji', $rel->avatar_emoji) }}" placeholder="例：🌸 ⭐ 🍶" maxlength="8">

            <label for="customer_name">呼んでいる名前 <span style="color:var(--warn)">*</span></label>
            <input id="customer_name" name="customer_name" value="{{ old('customer_name', $rel->customer_name) }}" required>

            <label for="line_display_name">LINEの表示名</label>
            <input id="line_display_name" name="line_display_name" value="{{ old('line_display_name', $rel->line_display_name) }}">

            <label for="importance">顧客区分（重要度）</label>
            <select id="importance" name="importance">
                <option value="">未設定</option>
                @foreach($importances as $val=>$label)
                    <option value="{{ $val }}" @selected(old('importance', $rel->importance?->value)===$val)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="favorite_drink">好きな飲み物</label>
            <input id="favorite_drink" name="favorite_drink" value="{{ old('favorite_drink', $rel->favorite_drink) }}">

            <label for="hobby">趣味・関心</label>
            <input id="hobby" name="hobby" value="{{ old('hobby', $rel->hobby) }}">

            <label for="usual_weekday">よく来る曜日</label>
            <input id="usual_weekday" name="usual_weekday" value="{{ old('usual_weekday', $rel->usual_weekday) }}">

            <label for="visit_expectation">来店見込み</label>
            <input id="visit_expectation" name="visit_expectation" value="{{ old('visit_expectation', $rel->visit_expectation) }}">

            <label for="next_talk">次に話したいこと</label>
            <input id="next_talk" name="next_talk" value="{{ old('next_talk', $rel->next_talk) }}">

            <hr style="border:none;border-top:1px solid var(--line);margin:18px 0">
            <div class="muted" style="font-size:12px">以下は顧客の共通情報（名寄せ・検索に使います）</div>

            <label for="kana">読み方</label>
            <input id="kana" name="kana" value="{{ old('kana', $c->kana) }}">

            <label for="age_range">年代</label>
            <input id="age_range" name="age_range" value="{{ old('age_range', $c->age_range) }}" placeholder="例：30代">

            <label for="occupation">職業</label>
            <input id="occupation" name="occupation" value="{{ old('occupation', $c->occupation) }}">

            <label for="area">居住エリア</label>
            <input id="area" name="area" value="{{ old('area', $c->area) }}">

            <label for="birthday">誕生日</label>
            <input id="birthday" name="birthday" type="date" value="{{ old('birthday', $c->birthday?->format('Y-m-d')) }}">

            <div style="height:16px"></div>
            <button class="btn" type="submit">保存する</button>
            <a class="btn ghost" href="{{ route('cast.customers.show', $rel) }}" style="margin-top:8px">戻る</a>
        </form>
    </div>
@endsection
