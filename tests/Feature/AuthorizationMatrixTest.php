<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 権限漏れの総点検（§13 Phase 6）。
 * 主要GETルートを全ロールで叩き、許可ロールは非403・非許可ロールは403 を機械的に確認する。
 */
class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    /** @var array<string,User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (RoleKey::seed() as $row) {
            Role::updateOrCreate(['key' => $row['key']], $row);
        }
        $this->store = Store::create(['name' => '店A', 'code' => 'a', 'timezone' => 'Asia/Tokyo', 'is_active' => true]);

        foreach ([RoleKey::Cast, RoleKey::Staff, RoleKey::Manager, RoleKey::Admin] as $role) {
            $user = User::create(['name' => $role->value, 'login_id' => $role->value, 'password' => Hash::make('password'), 'must_change_password' => false, 'is_active' => true]);
            UserStoreMembership::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'role_id' => Role::where('key', $role->value)->value('id'), 'status' => 'active', 'joined_at' => now()]);
            if ($role === RoleKey::Cast) {
                Cast::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'display_name' => 'キャスト', 'status' => 'active']);
            } elseif (in_array($role, [RoleKey::Staff, RoleKey::Manager], true)) {
                StaffProfile::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'display_name' => $role->value, 'position' => '黒服', 'status' => 'active']);
            }
            $this->users[$role->value] = $user;
        }
    }

    /**
     * @return array<string,array{route:string,allow:array<int,string>}>
     */
    private function matrix(): array
    {
        return [
            'home' => ['route' => 'home', 'allow' => ['cast', 'staff', 'manager', 'admin']],
            'announcements' => ['route' => 'announcements.index', 'allow' => ['cast', 'staff', 'manager', 'admin']],
            'customers' => ['route' => 'cast.customers.index', 'allow' => ['cast', 'admin']],
            'actions' => ['route' => 'cast.actions.index', 'allow' => ['cast']],
            'support' => ['route' => 'cast.support.index', 'allow' => ['cast']],
            'staff_work' => ['route' => 'staff.work.index', 'allow' => ['staff', 'manager', 'admin']],
            'staff_plans' => ['route' => 'staff.plans', 'allow' => ['staff', 'manager', 'admin']],
            'staff_after' => ['route' => 'staff.after', 'allow' => ['staff', 'manager', 'admin']],
            'inbox' => ['route' => 'inbox.index', 'allow' => ['staff', 'manager', 'admin']],
            'dashboard' => ['route' => 'dashboard', 'allow' => ['manager', 'admin']],
            'mgr_announce' => ['route' => 'manager.announcements.index', 'allow' => ['manager', 'admin']],
            'accounts' => ['route' => 'admin.accounts.index', 'allow' => ['manager', 'admin']],
            'assignments' => ['route' => 'admin.assignments.index', 'allow' => ['manager', 'admin']],
        ];
    }

    public function test_route_authorization_matrix(): void
    {
        foreach ($this->matrix() as $key => $spec) {
            $url = route($spec['route']);
            foreach ($this->users as $roleKey => $user) {
                $res = $this->actingAs($user)->get($url);
                if (in_array($roleKey, $spec['allow'], true)) {
                    $this->assertNotSame(403, $res->status(), "[{$key}] {$roleKey} は許可されるべき (got {$res->status()})");
                } else {
                    $this->assertSame(403, $res->status(), "[{$key}] {$roleKey} は拒否されるべき (got {$res->status()})");
                }
            }
        }
    }

    public function test_guest_is_redirected_everywhere(): void
    {
        foreach ($this->matrix() as $spec) {
            $this->get(route($spec['route']))->assertRedirect(route('login'));
        }
    }
}
