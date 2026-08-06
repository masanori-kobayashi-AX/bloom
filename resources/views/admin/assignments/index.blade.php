@extends('layouts.app')
@section('title', '担当の紐付け')
@section('wrapClass', 'wide')

@section('content')
    <h1>キャストと担当黒服の紐付け</h1>

    <div class="card">
        <h2>担当を設定</h2>
        <form method="POST" action="{{ route('admin.assignments.store') }}">
            @csrf
            <label for="cast_id">キャスト</label>
            <select id="cast_id" name="cast_id" required>
                <option value="">選択してください</option>
                @foreach($casts as $c)
                    <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                @endforeach
            </select>

            <label for="staff_id">担当黒服</label>
            <select id="staff_id" name="staff_id" required>
                <option value="">選択してください</option>
                @foreach($staff as $s)
                    <option value="{{ $s->id }}">{{ $s->display_name }}（{{ $s->position }}）</option>
                @endforeach
            </select>

            <div style="height:16px"></div>
            <button class="btn" type="submit">紐付ける</button>
        </form>
        <div class="notice">1キャストにつき担当は1名を基本とします。新しく設定すると以前の担当は自動で解除されます。</div>
    </div>

    <div class="card">
        <h2>現在の担当一覧</h2>
        @php($hasAny = $casts->flatMap->activeAssignments->isNotEmpty())
        @if(!$hasAny)
            <p class="muted">まだ担当が設定されていません。</p>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>キャスト</th><th>担当黒服</th><th></th></tr></thead>
            <tbody>
            @foreach($casts as $c)
                @foreach($c->activeAssignments as $a)
                    <tr>
                        <td>{{ $c->display_name }}</td>
                        <td>{{ $a->staff?->display_name }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('admin.assignments.release', $a) }}"
                                  onsubmit="return confirm('担当を解除しますか？')">
                                @csrf
                                <button class="btn sm ghost" type="submit">解除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    {{-- 黒服ごとの担当キャスト --}}
    <div class="card">
        <h2>黒服ごとの担当キャスト</h2>
        <div class="grid-cards">
            @foreach($staff as $s)
                <div class="card" style="margin:0">
                    <div class="row">
                        <strong>{{ $s->display_name }}</strong>
                        <span class="muted" style="font-size:12px">（{{ $s->position }}）</span>
                        <span style="flex:1"></span>
                        <span class="tag">{{ $s->assignedCasts->count() }}名担当</span>
                    </div>
                    <div style="margin-top:6px">
                        @forelse($s->assignedCasts as $c)
                            <span class="tag" style="margin:2px">{{ $c->display_name }}</span>
                        @empty
                            <span class="muted" style="font-size:12px">担当キャストなし</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
