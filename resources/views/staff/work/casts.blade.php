@extends('layouts.app')
@section('title', '担当キャスト')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <a href="{{ route('staff.work.index') }}" class="muted" style="font-size:13px">← 来店中</a>
        <span style="flex:1"></span>
    </div>
    <h1>担当キャスト</h1>

    <div class="card">
        @if($rows->isEmpty())
            <p class="muted">担当キャストがいません。</p>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>キャスト</th><th>今月の目標進捗</th><th>登録顧客</th><th>今月の来店</th><th>今月の本指名</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['cast']->display_name }}</td>
                    <td style="min-width:140px">
                        @if($row['progress']['hasGoal'])
                            <div class="bar"><span style="width:{{ $row['progress']['rate'] }}%"></span></div>
                            <div class="muted" style="font-size:11px">{{ $row['progress']['rate'] }}%（¥{{ number_format($row['progress']['actual']) }}/¥{{ number_format($row['progress']['target']) }}）</div>
                        @else
                            <span class="muted" style="font-size:12px">未設定</span>
                        @endif
                    </td>
                    <td>{{ $row['customers'] }}</td>
                    <td>{{ $row['visits'] }}</td>
                    <td>{{ $row['honshimei'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
@endsection
