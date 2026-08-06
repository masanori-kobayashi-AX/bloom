@extends('layouts.app')
@section('title', '顧客')

@section('content')
    <div class="row">
        <h1 style="margin:0">顧客</h1>
        <span style="flex:1"></span>
        <a class="btn sm" href="{{ route('cast.customers.create') }}">＋ 追加</a>
    </div>

    <form method="GET" action="{{ route('cast.customers.index') }}" style="margin:12px 0">
        <input name="q" value="{{ $q }}" placeholder="名前・LINE名・旧LINE名・読み方で検索" inputmode="search">
        <div class="row" style="gap:8px;margin-top:8px">
            <select name="sort" onchange="this.form.submit()" style="flex:1">
                <option value="updated" @selected($sort==='updated')>並び：最近更新</option>
                <option value="last_visit" @selected($sort==='last_visit')>並び：最終来店が古い順</option>
                <option value="importance" @selected($sort==='importance')>並び：重要度</option>
                <option value="name" @selected($sort==='name')>並び：呼び名</option>
            </select>
            <select name="status" onchange="this.form.submit()" style="flex:1">
                <option value="" @selected($status==='')>すべての状況</option>
                <option value="__close__" @selected($status==='__close__')>⏳ クローズ検討</option>
                @foreach($statuses as $val=>$label)
                    <option value="{{ $val }}" @selected($status===$val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($status === '__close__')
        <div class="notice" style="margin-top:0">しばらく来店がない・休眠などの「追いかけをやめるか検討」候補です。続ける／対応終了／店長に相談 を各カードで選べます。</div>
    @endif

    @if($relationships->isEmpty())
        <div class="card"><p class="muted">
            @if($status==='__close__')クローズ検討の候補はいません。@elseif($q !== '')「{{ $q }}」に一致する顧客が見つかりません。@else まだ顧客がいません。「＋追加」から登録できます。@endif
        </p></div>
    @else
        @foreach($relationships as $r)
            @php($lastVisit = $r->last_visit_at ? \Illuminate\Support\Carbon::parse($r->last_visit_at) : null)
            <div class="card" style="margin:10px 0">
                <a href="{{ route('cast.customers.show', $r) }}" style="display:block;color:inherit">
                    <div class="row">
                        @include('partials.avatar', ['name' => $r->customer_name, 'emoji' => $r->avatar_emoji])
                        <div style="min-width:0">
                            <div style="font-weight:600">{{ $r->customer_name }}</div>
                            <div class="muted" style="font-size:12px">
                                最終来店：{{ $lastVisit ? $lastVisit->format('n/j') . '（' . (int) $lastVisit->diffInDays(now()) . '日前）' : '未来店' }}
                            </div>
                        </div>
                        <span style="flex:1"></span>
                        <div style="text-align:right">
                            <span class="tag">{{ $r->status?->label() }}</span>
                            @if($r->importance)<div class="muted" style="font-size:11px;margin-top:3px">{{ $r->importance->label() }}</div>@endif
                        </div>
                    </div>
                </a>
                @if($status === '__close__' && $r->status?->value !== 'closed')
                    <div class="row" style="gap:6px;margin-top:8px;border-top:1px solid var(--line);padding-top:8px">
                        <form method="POST" action="{{ route('cast.customers.status', $r) }}" onsubmit="return confirm('「{{ $r->customer_name }}」を対応終了（クローズ）にしますか？')">@csrf
                            <input type="hidden" name="status" value="closed"><input type="hidden" name="note" value="クローズ検討から終了">
                            <button class="btn sm danger" type="submit">対応終了</button>
                        </form>
                        <form method="POST" action="{{ route('cast.customers.close-consult', $r) }}">@csrf
                            <button class="btn sm ghost" type="submit">店長に相談</button>
                        </form>
                        <span class="muted" style="font-size:12px;align-self:center">続ける場合はそのまま</span>
                    </div>
                @endif
            </div>
        @endforeach
        <div style="margin-top:10px">{{ $relationships->links() }}</div>
    @endif
@endsection
