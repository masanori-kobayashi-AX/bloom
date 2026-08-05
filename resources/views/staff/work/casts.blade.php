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
            <thead><tr><th>キャスト</th><th>登録顧客</th><th>今月の共有</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['cast']->display_name }}</td>
                    <td>{{ $row['customers'] }}</td>
                    <td>{{ $row['openShared'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
@endsection
