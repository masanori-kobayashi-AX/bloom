@extends('layouts.app')
@section('title', 'アフター見守り')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <a href="{{ route('staff.work.index') }}" class="muted" style="font-size:13px">← 来店中</a>
        <span style="flex:1"></span>
    </div>
    <h1>アフター見守り</h1>
    <div class="notice" style="margin-top:0">キャストが<strong>どの店へ・何時から</strong>行ったかを記録し、<strong>帰宅連絡が来るまで見守ります</strong>。無事に帰宅の連絡が来たら「帰宅連絡あり」を押してください。</div>

    {{-- 見守り中：帰宅連絡がまだ来ていない（最優先） --}}
    <div class="card" style="border-color:#d9822b">
        <div class="row">
            <h2 style="margin:0">🌙 見守り中（帰宅連絡待ち）</h2>
            <span style="flex:1"></span>
            <span class="tag" style="border-color:#d9822b;color:#d9822b">{{ $watching->count() }}名</span>
        </div>
        @forelse($watching as $log)
            <div style="border-bottom:1px solid var(--line);padding:12px 0">
                <div class="row">
                    <strong style="font-size:16px">{{ $log->cast->display_name }}</strong>
                    @if($log->destination)<span class="tag">🏙 {{ $log->destination }}</span>@endif
                    @if($log->companion)<span class="muted" style="font-size:12px">同伴：{{ $log->companion }}</span>@endif
                </div>
                <div class="row" style="margin-top:4px">
                    <span class="muted" style="font-size:13px">
                        {{ $log->departed_at?->format('H:i') }} 出発
                        @if($log->departed_at)・<strong style="color:#d9822b">{{ $log->departed_at->diffForHumans(null, true) }}経過</strong>@endif
                    </span>
                </div>
                @if($log->note)<div class="muted" style="font-size:12px;margin-top:4px">📝 {{ $log->note }}</div>@endif
                <div class="row" style="margin-top:8px;gap:8px">
                    <form method="POST" action="{{ route('staff.after.home', $log) }}">@csrf
                        <button class="btn" type="submit" style="background:#2f855a">✓ 帰宅連絡あり</button>
                    </form>
                    <form method="POST" action="{{ route('staff.after.destroy', $log) }}" onsubmit="return confirm('この記録を取り消しますか？')">@csrf @method('DELETE')
                        <button class="btn-ghost" type="submit" style="font-size:12px">取消</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="muted">いま見守り中のアフターはありません。</p>
        @endforelse
    </div>

    {{-- アフターを記録 --}}
    <div class="card">
        <h2>アフターを記録する</h2>
        <form method="POST" action="{{ route('staff.after.store') }}">@csrf
            <label>キャスト</label>
            <select name="cast_id" required>
                <option value="">選択してください</option>
                @foreach($casts as $c)
                    <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                @endforeach
            </select>
            <div class="row" style="gap:10px">
                <div style="flex:1">
                    <label>行き先の店</label>
                    <input name="destination" type="text" placeholder="例：BAR〇〇 / ラーメン">
                </div>
                <div style="flex:1">
                    <label>同伴のお客様（任意）</label>
                    <input name="companion" type="text" placeholder="例：たっくん">
                </div>
            </div>
            <label>出発時刻</label>
            <input name="departed_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}">
            <label>メモ（任意）</label>
            <input name="note" type="text" placeholder="連絡先・タクシー方向など">
            <button class="btn" type="submit" style="margin-top:10px">🌙 見守り開始を記録</button>
        </form>
        @if($casts->isEmpty())
            <p class="muted" style="font-size:12px;margin-top:8px">担当キャストが登録されていません。店長に担当の紐付けを依頼してください。</p>
        @endif
    </div>

    {{-- 指定日の記録（帰宅済みも含む） --}}
    <div class="card">
        <div class="row">
            <h2 style="margin:0">{{ \Illuminate\Support\Carbon::parse($date)->format('n月j日') }} の記録</h2>
            <span style="flex:1"></span>
            <form method="GET" action="{{ route('staff.after') }}">
                <input name="date" type="date" value="{{ $date }}" onchange="this.form.submit()">
            </form>
        </div>

        @php($home = $logs->where('status', 'home'))
        @php($stillOut = $logs->where('status', 'out'))
        <div class="row" style="gap:12px;margin:8px 0">
            <div class="stat" style="flex:1"><div class="stat-num">{{ $logs->count() }}</div><div class="stat-label">アフター件数</div></div>
            <div class="stat" style="flex:1"><div class="stat-num">{{ $home->count() }}</div><div class="stat-label">帰宅確認済み</div></div>
            <div class="stat" style="flex:1"><div class="stat-num" style="color:{{ $stillOut->count() ? '#d9822b' : 'inherit' }}">{{ $stillOut->count() }}</div><div class="stat-label">帰宅待ち</div></div>
        </div>

        @forelse($logs as $log)
            <div class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
                @if($log->isHome())
                    <span class="tag" style="border-color:#2f855a;color:#2f855a">✓ 帰宅</span>
                @else
                    <span class="tag" style="border-color:#d9822b;color:#d9822b">🌙 見守り中</span>
                @endif
                <strong>{{ $log->cast->display_name }}</strong>
                @if($log->destination)<span class="muted">→ {{ $log->destination }}</span>@endif
                <span style="flex:1"></span>
                <span class="muted" style="font-size:12px">
                    {{ $log->departed_at?->format('H:i') }}
                    @if($log->home_reported_at)〜{{ $log->home_reported_at->format('H:i') }}帰宅@endif
                </span>
                @if($log->isHome())
                    <form method="POST" action="{{ route('staff.after.reopen', $log) }}" style="margin-left:8px">@csrf
                        <button class="btn-ghost" type="submit" style="font-size:11px">戻す</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="muted">この日のアフター記録はありません。</p>
        @endforelse
    </div>
@endsection
