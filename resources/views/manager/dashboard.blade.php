@extends('layouts.app')
@section('title', 'ダッシュボード')
@section('wrapClass', 'wide')

@section('content')
    <h1>ダッシュボード <span class="muted" style="font-size:14px">{{ $month }}</span></h1>

    {{-- 店舗全体指標 --}}
    <h2>店舗全体（今月）</h2>
    <div class="stat-grid">
        @php($tiles = [
            ['在籍キャスト', $summary['activeCasts']],
            ['稼働キャスト', $summary['workingCasts']],
            ['新規顧客', $summary['newCustomers']],
            ['LINE交換', $summary['lineExchanges']],
            ['来店顧客', $summary['visitedCustomers']],
            ['来店数', $summary['visitCount']],
            ['場内指名', $summary['zainaiVisits']],
            ['本指名', $summary['honshimeiVisits']],
            ['指名顧客数', $summary['nominatedCustomers']],
            ['休眠顧客', $summary['dormantCustomers']],
        ])
        @foreach($tiles as [$label, $val])
            <div class="stat">
                <div class="stat-num">{{ number_format($val) }}</div>
                <div class="stat-label">{{ $label }}</div>
            </div>
        @endforeach
        <div class="stat" style="grid-column:span 2">
            <div class="stat-num">¥{{ number_format($summary['totalSales']) }}</div>
            <div class="stat-label">店舗全体売上（今月）</div>
        </div>
    </div>

    {{-- キャスト別指標 --}}
    <h2 style="margin-top:24px">キャスト別（今月）</h2>
    <div class="card">
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>キャスト</th><th>登録顧客</th><th>新規LINE</th><th>場内</th><th>本指名</th>
                    <th>本指名率</th><th>コア</th><th>来店数</th><th>売上</th><th>最終ログイン</th>
                </tr>
            </thead>
            <tbody>
            @foreach($casts as $c)
                <tr>
                    <td><strong>{{ $c['cast']->display_name }}</strong></td>
                    <td>{{ $c['customers'] }}</td>
                    <td>{{ $c['newLine'] }}</td>
                    <td>{{ $c['zainai'] }}</td>
                    <td>{{ $c['honshimei'] }}</td>
                    <td>{{ $c['conversionRate'] }}%</td>
                    <td>{{ $c['core'] }}</td>
                    <td>{{ $c['visits'] }}</td>
                    <td>¥{{ number_format($c['sales']) }}</td>
                    <td class="muted" style="font-size:12px">{{ $c['lastLoginAt']?->format('n/j') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div class="notice">
            数値は現場を支援するための材料です。出勤日数・新規卓・顧客層など母数が違うため、
            <strong>単純比較で「サボっている」と判断しないでください</strong>（§6-2）。
        </div>
    </div>

    {{-- 顧客資産 --}}
    <h2 style="margin-top:24px">店舗の顧客資産</h2>
    <div class="row" style="gap:12px;align-items:stretch;flex-wrap:wrap">
        <div class="card" style="flex:1;min-width:260px;margin:0">
            <h2 style="font-size:15px">複数キャストを指名した顧客</h2>
            <div class="stat-num">{{ $assets['multiCastCount'] }}<span style="font-size:14px" class="muted"> 名</span></div>
            <div class="notice">同じお客様が複数キャストと関係を持つケース。名寄せ確認の対象になります。</div>
        </div>
        <div class="card" style="flex:2;min-width:280px;margin:0">
            <h2 style="font-size:15px">長期未来店の指名顧客（60日以上）</h2>
            @forelse($assets['dormant'] as $d)
                <div style="border-bottom:1px solid var(--line);padding:6px 0">
                    {{ $d->customer_name }} <span class="muted" style="font-size:12px">／ 担当キャスト：{{ $d->cast?->display_name }}・{{ $d->status?->label() }}</span>
                </div>
            @empty
                <p class="muted">該当なし。</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2 style="font-size:15px">高売上顧客（累計・上位）</h2>
        @forelse($assets['topCustomers'] as $t)
            <div class="row" style="border-bottom:1px solid var(--line);padding:6px 0">
                <span>顧客 #{{ $t->customer_id }}</span>
                <span style="flex:1"></span>
                <strong>¥{{ number_format($t->total) }}</strong>
            </div>
        @empty
            <p class="muted">売上データがまだありません。</p>
        @endforelse
    </div>

    <div class="notice" style="margin-top:12px">
        顧客との関係はキャスト個人への信頼に強く依存します。退店者の顧客を自動的に別キャストへ配分する運用はしません（§6-3）。
    </div>
@endsection
