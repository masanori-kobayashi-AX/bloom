@extends('layouts.app')
@section('title', 'アカウント管理')
@section('wrapClass', 'wide')

@section('content')
    <div class="row">
        <h1 style="margin:0">アカウント管理</h1>
        <span class="spacer" style="flex:1"></span>
        <a class="btn sm ghost" href="{{ route('admin.assignments.index') }}">🔗 担当の紐付け</a>
        <a class="btn sm" href="{{ route('admin.accounts.create') }}">＋ 新規作成</a>
    </div>

    <form method="GET" action="{{ route('admin.accounts.index') }}" style="margin:12px 0">
        <select name="role" onchange="this.form.submit()">
            <option value="" @selected($role==='')>すべての役割</option>
            @foreach($roleOptions as $r)
                <option value="{{ $r->value }}" @selected($role===$r->value)>{{ $r->label() }}</option>
            @endforeach
        </select>
    </form>

    <div class="card">
        @if($members->isEmpty())
            <p class="muted">該当するアカウントがありません。</p>
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
                            <span class="tag on">在籍</span>
                        @else
                            <span class="tag off">クローズ（退店）</span>
                        @endif
                    </td>
                    <td>
                        @if($m->manageable)
                        <div class="row" style="justify-content:flex-end">
                            <form method="POST" action="{{ route('admin.accounts.reset-password', $m->user) }}"
                                  onsubmit="return confirm('パスワードを再設定します。よろしいですか？')">
                                @csrf
                                <button class="btn sm ghost" type="submit">PW再設定</button>
                            </form>
                            @if($m->status === 'active')
                                <form method="POST" action="{{ route('admin.accounts.suspend', $m->user) }}"
                                      onsubmit="return confirm('このアカウントを退店（クローズ）にします。ログインできなくなり、担当や一覧からも外れます。データは保持され、後から復帰できます。よろしいですか？')">
                                    @csrf
                                    <button class="btn sm danger" type="submit">退店（クローズ）</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.accounts.reactivate', $m->user) }}">
                                    @csrf
                                    <button class="btn sm" type="submit">復帰</button>
                                </form>
                            @endif
                        </div>
                        @else
                            <span class="muted" style="font-size:12px">操作不可</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
    <div class="notice">退店（クローズ）はログインを即座に無効化し、担当の紐付けや各種一覧（アフター・担当・検索候補）からも外します。顧客・来店履歴・メモなどのデータは削除されません。後から「復帰」で戻せます（担当は手動で再設定）。<br>退店者が持っていた<strong>顧客情報は <a href="{{ route('admin.customers') }}">顧客一覧（全体）</a> の「退店者（クローズ）」から店長・管理者だけが確認</strong>できます。※システム管理者は最上位で、他の役割からは操作できません。</div>
@endsection
