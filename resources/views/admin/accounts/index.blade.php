@extends('layouts.app')
@section('title', 'アカウント管理')

@section('content')
    <div class="row">
        <h1 style="margin:0">アカウント管理</h1>
        <span class="spacer" style="flex:1"></span>
        <a class="btn sm" href="{{ route('admin.accounts.create') }}">＋ 新規作成</a>
    </div>

    <div class="card">
        @if($members->isEmpty())
            <p class="muted">まだアカウントがありません。「＋ 新規作成」から追加してください。</p>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr><th>氏名 / ログインID</th><th>役割</th><th>状態</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($members as $m)
                <tr>
                    <td>
                        <strong>{{ $m->user->name }}</strong><br>
                        <span class="muted">{{ $m->user->login_id }}</span>
                    </td>
                    <td>{{ $m->role?->name }}</td>
                    <td>
                        @if($m->status === 'active')
                            <span class="tag on">有効</span>
                        @else
                            <span class="tag off">停止</span>
                        @endif
                    </td>
                    <td>
                        <div class="row" style="justify-content:flex-end">
                            <form method="POST" action="{{ route('admin.accounts.reset-password', $m->user) }}"
                                  onsubmit="return confirm('パスワードを再設定します。よろしいですか？')">
                                @csrf
                                <button class="btn sm ghost" type="submit">PW再設定</button>
                            </form>
                            @if($m->status === 'active')
                                <form method="POST" action="{{ route('admin.accounts.suspend', $m->user) }}"
                                      onsubmit="return confirm('このアカウントを利用停止にします（データは保持されます）。よろしいですか？')">
                                    @csrf
                                    <button class="btn sm danger" type="submit">停止</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.accounts.reactivate', $m->user) }}">
                                    @csrf
                                    <button class="btn sm" type="submit">再開</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
    <div class="notice">利用停止はログインを即座に無効化します。履歴・データは削除されません（退店者の記録は保持）。</div>
@endsection
