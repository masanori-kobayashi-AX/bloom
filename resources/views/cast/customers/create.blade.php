@extends('layouts.app')
@section('title', '顧客を追加')

@section('content')
    <h1>顧客を追加</h1>

    @if($dup)
        <div class="flash err" style="background:#fff8e6;border-color:var(--gold);color:#8a6d1a">
            <strong>似た顧客がすでにいるかもしれません。</strong>
            @if($dupOwn->isNotEmpty())
                <div style="margin-top:8px">あなたが登録済みの似た顧客：</div>
                @foreach($dupOwn as $d)
                    <a href="{{ route('cast.customers.show', $d) }}" style="display:block;margin:4px 0">
                        → {{ $d->avatar_emoji ?? '👤' }} {{ $d->customer_name }}
                        @if($d->line_display_name)（LINE: {{ $d->line_display_name }}）@endif を開く
                    </a>
                @endforeach
            @endif
            @if(($dup['otherCount'] ?? 0) > 0)
                <div style="margin-top:8px">ほかの在籍キャストにも似た表示名の登録が <strong>{{ $dup['otherCount'] }}件</strong> あります（同一人物かは責任者が確認します）。</div>
            @endif
            <div class="muted" style="margin-top:8px">別の人であれば、このまま下の「新規として登録」を押してください。</div>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('cast.customers.store') }}">
            @csrf
            {{-- 名寄せ警告後は confirmed=1 で新規作成を確定 --}}
            <input type="hidden" name="confirmed" value="{{ $dup ? 1 : 0 }}">

            <label for="customer_name">呼んでいる名前 <span style="color:var(--warn)">*</span></label>
            <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" autofocus required>

            <label for="line_display_name">LINEの表示名</label>
            <input id="line_display_name" name="line_display_name" value="{{ old('line_display_name') }}">

            <label for="status">今の状況 <span style="color:var(--warn)">*</span></label>
            <select id="status" name="status" required>
                @foreach($statuses as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', 'line_only')===$val)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="met_context">出会った状況</label>
            <input id="met_context" name="met_context" value="{{ old('met_context') }}" placeholder="例：フリーで来店 / 同伴 / 紹介">

            <label for="line_exchanged_on">LINE交換日</label>
            <input id="line_exchanged_on" name="line_exchanged_on" type="date" value="{{ old('line_exchanged_on') }}">

            <label for="first_met_on">初めて会った日</label>
            <input id="first_met_on" name="first_met_on" type="date" value="{{ old('first_met_on') }}">

            <label for="kana">読み方（任意）</label>
            <input id="kana" name="kana" value="{{ old('kana') }}" placeholder="名寄せ・検索に使います">

            <label for="first_note">ひとことメモ（自分用・任意）</label>
            <input id="first_note" name="first_note" value="{{ old('first_note') }}" placeholder="会話した内容など">

            <div style="height:16px"></div>
            <button class="btn" type="submit">{{ $dup ? '新規として登録する' : '登録する' }}</button>
            <a class="btn ghost" href="{{ route('cast.customers.index') }}" style="margin-top:8px">戻る</a>
        </form>
        <div class="notice">必須は「名前」と「状況」だけ。あとから追記できます。</div>
    </div>
@endsection
