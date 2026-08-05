@extends('layouts.app')
@section('title', 'アフター管理')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <a href="{{ route('staff.work.index') }}" class="muted" style="font-size:13px">← 来店中</a>
        <span style="flex:1"></span>
    </div>
    <h1>アフター管理</h1>
    <p class="muted" style="font-size:13px">どのキャストがどのお客様とアフターに行く／行ったかを把握します。</p>

    <form method="GET" action="{{ route('staff.after') }}" style="margin:12px 0">
        <label for="date">日付</label>
        <input id="date" name="date" type="date" value="{{ $date }}" onchange="this.form.submit()">
    </form>

    <div class="card">
        <h2>{{ \Illuminate\Support\Carbon::parse($date)->format('n月j日') }} のアフター見込み・状況</h2>
        @if($visits->isEmpty())
            <p class="muted">この日のアフター申告はありません。</p>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>キャスト</th><th>お客様</th><th>状況</th><th>補足</th></tr></thead>
            <tbody>
            @foreach($visits as $v)
                <tr>
                    <td>{{ $v->primaryCast?->display_name ?? '—' }}</td>
                    <td>{{ $v->customer->relationships->firstWhere('cast_id', $v->primary_cast_id)?->customer_name ?? ('顧客#'.$v->customer_id) }}</td>
                    <td><span class="tag" style="border-color:{{ $v->after_status->badgeColor() }};color:{{ $v->after_status->badgeColor() }}">{{ $v->after_status->label() }}</span></td>
                    <td class="muted">{{ $v->after_status_note }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    @if($recent->isNotEmpty())
    <div class="card">
        <details>
            <summary style="cursor:pointer;font-weight:600">直近7日の履歴（行けそう/確定）</summary>
            <table style="margin-top:8px">
                <thead><tr><th>日</th><th>キャスト</th><th>お客様</th><th>状況</th></tr></thead>
                <tbody>
                @foreach($recent as $v)
                    <tr>
                        <td class="muted">{{ $v->arrived_at->format('n/j') }}</td>
                        <td>{{ $v->primaryCast?->display_name ?? '—' }}</td>
                        <td>{{ $v->customer->relationships->firstWhere('cast_id', $v->primary_cast_id)?->customer_name ?? ('顧客#'.$v->customer_id) }}</td>
                        <td>{{ $v->after_status?->label() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </details>
    </div>
    @endif
@endsection
