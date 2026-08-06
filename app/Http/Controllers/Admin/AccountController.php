<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleKey;
use App\Http\Controllers\Controller;
use App\Models\Cast;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\UserStoreMembership;
use App\Services\AuditLogger;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * アカウント管理（作成・停止・再開・PW再設定）。責任者・管理者のみ。
 * すべて現在店舗（store_id）スコープ内で完結する。
 */
class AccountController extends Controller
{
    /** 作成者が付与できるロール。 */
    private function assignableRoles(): array
    {
        $actor = Auth::user();
        // 管理者: cast/staff/manager、責任者: cast/staff のみ
        return $actor->isAdmin()
            ? [RoleKey::Cast, RoleKey::Staff, RoleKey::Manager]
            : [RoleKey::Cast, RoleKey::Staff];
    }

    /** 操作者が対象ロールのアカウントを管理（停止/PW再設定等）できるか。 */
    private function canManageRole(RoleKey $targetRole): bool
    {
        $actor = Auth::user();
        if ($actor->isAdmin()) {
            return $targetRole !== RoleKey::Admin; // 管理者アカウント同士は相互操作しない
        }
        // 責任者(店長)は cast / staff のみ。manager・admin は不可（権限昇格防止）
        return $actor->isManager() && in_array($targetRole, [RoleKey::Cast, RoleKey::Staff], true);
    }

    public function index(Request $request)
    {
        $actor = Auth::user();
        $storeId = CurrentStore::id();
        $role = $request->input('role', ''); // 役割で絞り込み（cast/staff/manager）

        $members = UserStoreMembership::with(['user', 'role'])
            ->where('store_id', $storeId)
            ->when($role !== '', fn ($q) => $q->whereHas('role', fn ($r) => $r->where('key', $role)))
            ->join('roles', 'roles.id', '=', 'user_store_memberships.role_id')
            ->orderBy('roles.level') // キャスト→黒服→店長→管理者の順
            ->orderByDesc('user_store_memberships.created_at')
            ->select('user_store_memberships.*')
            ->get()
            ->each(function ($m) {
                $m->manageable = $m->role ? $this->canManageRole(RoleKey::from($m->role->key)) : false;
            });

        // 店長にはシステム管理者アカウントを一覧に出さない（最上位・不可視）
        if ($actor->isManager()) {
            $members = $members->reject(fn ($m) => $m->role?->key === RoleKey::Admin->value)->values();
        }

        // 絞り込み用の役割（店長は管理者を選べない）
        $roleOptions = [RoleKey::Cast, RoleKey::Staff, RoleKey::Manager];
        if ($actor->isAdmin()) {
            $roleOptions[] = RoleKey::Admin;
        }

        return view('admin.accounts.index', compact('members', 'role', 'roleOptions'));
    }

    /** 対象アカウントを操作できるか検証（サーバー側の防御）。 */
    private function authorizeManage(User $user): void
    {
        $storeId = CurrentStore::id();
        $membership = UserStoreMembership::with('role')->where('store_id', $storeId)->where('user_id', $user->id)->firstOrFail();
        abort_unless($membership->role && $this->canManageRole(RoleKey::from($membership->role->key)), 403, 'このアカウントを操作する権限がありません。');
    }

    public function create()
    {
        return view('admin.accounts.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowed = array_map(fn (RoleKey $r) => $r->value, $this->assignableRoles());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'login_id')],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in($allowed)],
            'display_name' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:50'],
        ]);

        $roleKey = RoleKey::from($data['role']);
        $role = Role::where('key', $roleKey->value)->firstOrFail();
        $storeId = CurrentStore::id();
        $tempPassword = Str::password(10);

        $user = DB::transaction(function () use ($data, $role, $roleKey, $storeId, $tempPassword) {
            $user = User::create([
                'name' => $data['name'],
                'login_id' => $data['login_id'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($tempPassword),
                'must_change_password' => true,
                'is_active' => true,
            ]);

            UserStoreMembership::create([
                'store_id' => $storeId,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            if ($roleKey === RoleKey::Cast) {
                Cast::create([
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                    'display_name' => $data['display_name'],
                    'status' => 'active',
                    'joined_on' => now()->toDateString(),
                ]);
            } else { // staff / manager
                StaffProfile::create([
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                    'display_name' => $data['display_name'],
                    'position' => $data['position'] ?? $roleKey->label(),
                    'status' => 'active',
                ]);
            }

            AuditLogger::record('account.create', $user, "アカウント作成（{$roleKey->label()}）", after: [
                'login_id' => $user->login_id,
                'role' => $roleKey->value,
            ], storeId: $storeId);

            return $user;
        });

        return redirect()->route('admin.accounts.index')
            ->with('status', "アカウントを作成しました。ログインID: {$user->login_id}")
            ->with('temp_password', $tempPassword); // 初回パスワードを一度だけ表示
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManage($user);
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $storeId = CurrentStore::id();

        $membership = UserStoreMembership::where('store_id', $storeId)
            ->where('user_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($user, $membership, $request, $storeId) {
            $membership->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspended_reason' => $request->input('reason'),
            ]);
            // アカウント自体も無効化（データは削除しない：クローズと削除は分離）
            $user->forceFill(['is_active' => false])->saveQuietly();

            // 退店：プロフィールを離任にし、担当を解除（一覧・ドロップダウンから外す）
            $this->closeProfiles($user);

            AuditLogger::record('account.suspend', $user, '退店（クローズ）', after: [
                'reason' => $request->input('reason'),
            ], storeId: $storeId);
        });

        return redirect()->route('admin.accounts.index')
            ->with('status', "{$user->name} を退店（クローズ）にしました。ログイン・担当・一覧から外れます（データは保持されます）。");
    }

    /** 退店：キャスト/黒服のプロフィールを離任にし、現在の担当紐付けを解除する。データは削除しない。 */
    private function closeProfiles(User $user): void
    {
        $cast = Cast::where('user_id', $user->id)->first();
        if ($cast) {
            $cast->update(['status' => 'left', 'left_on' => now()->toDateString()]);
            $cast->activeAssignments()->update(['released_at' => now()]);
        }

        $staff = StaffProfile::where('user_id', $user->id)->first();
        if ($staff) {
            $staff->update(['status' => 'left']);
            $staff->activeAssignments()->update(['released_at' => now()]);
        }
    }

    /** 復帰：プロフィールを在籍に戻す。担当は自動で戻さない（店長が手動で紐付け直す）。 */
    private function reopenProfiles(User $user): void
    {
        $cast = Cast::where('user_id', $user->id)->first();
        if ($cast) {
            $cast->update(['status' => 'active', 'left_on' => null]);
        }

        $staff = StaffProfile::where('user_id', $user->id)->first();
        if ($staff) {
            $staff->update(['status' => 'active']);
        }
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorizeManage($user);
        $storeId = CurrentStore::id();
        $membership = UserStoreMembership::where('store_id', $storeId)
            ->where('user_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($user, $membership, $storeId) {
            $membership->update(['status' => 'active', 'suspended_at' => null, 'suspended_reason' => null]);
            $user->forceFill(['is_active' => true])->saveQuietly();
            // 在籍に戻す（担当は自動で戻さない：店長が手動で紐付け直す）
            $this->reopenProfiles($user);
            AuditLogger::record('account.reactivate', $user, '復帰（クローズ解除）', storeId: $storeId);
        });

        return redirect()->route('admin.accounts.index')->with('status', "{$user->name} を復帰させました。担当は必要に応じて紐付け直してください。");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorizeManage($user);
        $storeId = CurrentStore::id();
        // 店舗スコープ確認
        UserStoreMembership::where('store_id', $storeId)->where('user_id', $user->id)->firstOrFail();

        $tempPassword = Str::password(10);
        $user->forceFill([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->saveQuietly();

        AuditLogger::record('account.password_reset', $user, '管理者がパスワードを再設定', storeId: $storeId);

        return redirect()->route('admin.accounts.index')
            ->with('status', "{$user->name} のパスワードを再設定しました。")
            ->with('temp_password', $tempPassword);
    }
}
