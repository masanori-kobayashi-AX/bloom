@extends('layouts.app')
@section('title', '顧客一覧（全体）')
@section('wrapClass', 'full')

@section('content')
    <div class="row">
        <h1 style="margin:0">顧客一覧（全体）</h1>
        <span style="flex:1"></span>
        <form method="GET" action="{{ route('admin.customers') }}">
            <select name="cast_id" onchange="this.form.submit()">
                <option value="">全キャスト</option>
                @foreach($casts as $c)
                    <option value="{{ $c->id }}" @selected((string)$castId===(string)$c->id)>{{ $c->display_name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($canEdit)
        <div class="notice" style="margin:8px 0">全キャストの顧客を横スクロールで管理できます。<strong>状況・区分はセルで直接変更→即保存</strong>。本人コメント（🌸私だけのメモ）も表示（この閲覧・編集は監査ログに記録されます）。</div>
    @else
        <div class="notice" style="margin:8px 0">全キャストの顧客状況を確認できます（閲覧のみ）。キャスト本人の「私だけのメモ」は表示されません（オーナーのみ）。</div>
    @endif

    <div class="xls">
        <table>
            <thead>
                <tr>
                    <th class="sticky-col">キャスト</th>
                    <th class="sticky-col" style="left:64px">呼び名</th>
                    <th>LINE名</th>
                    <th>状況</th>
                    <th>区分</th>
                    <th>LINE交換</th>
                    <th>最終来店</th>
                    <th>来店</th>
                    <th>累計売上</th>
                    <th>次のアクション</th>
                    <th>重大注意</th>
                    <th class="wrap-cell">お店と共有（最新）</th>
                    <th>キープボトル</th>
                    @if($showPrivate)<th class="wrap-cell priv">🌸 私だけのメモ（本人コメント）</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    @php($last = $r->last_visit_at ? \Illuminate\Support\Carbon::parse($r->last_visit_at) : null)
                    @php($nextAction = $r->nextActions->where('completed', false)->sortBy('due_on')->first())
                    @php($shared = $r->sharedNotes->first())
                    <tr>
                        <td class="sticky-col">{{ $r->cast?->display_name }}</td>
                        <td class="sticky-col" style="left:64px"><strong>{{ $r->customer_name }}</strong></td>
                        <td>{{ $r->line_display_name ?: '—' }}</td>
                        <td>
                            @if($canEdit)
                                <form method="POST" action="{{ route('admin.customers.cell', $r) }}" style="margin:0">@csrf
                                    <input type="hidden" name="field" value="status">
                                    <select name="value" onchange="this.form.submit()" style="padding:4px 6px;font-size:12px;min-width:96px">
                                        @foreach($statuses as $val=>$label)<option value="{{ $val }}" @selected($r->status?->value===$val)>{{ $label }}</option>@endforeach
                                    </select>
                                </form>
                            @else{{ $r->status?->label() }}@endif
                        </td>
                        <td>
                            @if($canEdit)
                                <form method="POST" action="{{ route('admin.customers.cell', $r) }}" style="margin:0">@csrf
                                    <input type="hidden" name="field" value="importance">
                                    <select name="value" onchange="this.form.submit()" style="padding:4px 6px;font-size:12px;min-width:96px">
                                        <option value="" @selected(!$r->importance)>未設定</option>
                                        @foreach($importances as $val=>$label)<option value="{{ $val }}" @selected($r->importance?->value===$val)>{{ $label }}</option>@endforeach
                                    </select>
                                </form>
                            @else{{ $r->importance?->label() ?: '—' }}@endif
                        </td>
                        <td>{{ $r->line_exchanged_on?->format('n/j') ?: '—' }}</td>
                        <td>{{ $last ? $last->format('n/j').'（'.(int)$last->diffInDays(now()).'日前）' : '未来店' }}</td>
                        <td style="text-align:right">{{ $r->visit_count }}回</td>
                        <td style="text-align:right">¥{{ number_format((int) $r->total_sales) }}</td>
                        <td>{{ $nextAction ? $nextAction->kind->label().'：'.$nextAction->content : '—' }}</td>
                        <td>
                            @forelse($r->customer->alerts as $al)
                                <div style="color:var(--warn)">⚠️{{ $al->category?->label() }}</div>
                            @empty — @endforelse
                        </td>
                        <td class="wrap-cell">{{ $shared?->body ?: '—' }}</td>
                        <td>{{ $r->customer->keptBottles->pluck('name')->implode(' / ') ?: '—' }}</td>
                        @if($showPrivate)
                            <td class="wrap-cell priv">{{ $r->notes->first()?->body ?: '—' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="14" style="text-align:center;color:var(--muted);padding:20px">顧客がいません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="muted" style="font-size:12px;margin-top:8px">← → 横にスクロールすると全項目が見られます（PC/タブレット推奨）。</div>
@endsection
