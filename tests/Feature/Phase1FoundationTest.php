<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\LoginHistory;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase1FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (RoleKey::seed() as $row) {
            Role::updateOrCreate(['key' => $row['key']], $row);
        }
    }

    private function makeStore(string $code): Store
    {
        return Store::create(['name' => "店舗{$code}", 'code' => $code, 'timezone' => 'Asia/Tokyo', 'is_active' => true]);
    }

    private function makeUser(Store $store, RoleKey $role, string $loginId, bool $active = true, bool $mustChange = false): User
    {
        $user = User::create([
            'name' => $loginId, 'login_id' => $loginId,
            'password' => Hash::make('password'),
            'must_change_password' => $mustChange, 'is_active' => $active,
        ]);
        UserStoreMembership::create([
            'store_id' => $store->id, 'user_id' => $user->id,
            'role_id' => Role::where('key', $role->value)->value('id'),
            'status' => $active ? 'active' : 'suspended', 'joined_at' => now(),
        ]);

        return $user;
    }

    public function test_login_success_redirects_home_and_records_history(): void
    {
        $store = $this->makeStore('a');
        $this->makeUser($store, RoleKey::Manager, 'tencho');

        $res = $this->post('/login', ['login_id' => 'tencho', 'password' => 'password']);

        $res->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('login_histories', ['login_id_attempted' => 'tencho', 'succeeded' => true]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $store = $this->makeStore('a');
        $this->makeUser($store, RoleKey::Cast, 'yui');

        $res = $this->from('/login')->post('/login', ['login_id' => 'yui', 'password' => 'wrong']);

        $res->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('login_histories', ['login_id_attempted' => 'yui', 'succeeded' => false]);
    }

    public function test_suspended_account_cannot_login(): void
    {
        $store = $this->makeStore('a');
        $this->makeUser($store, RoleKey::Cast, 'left_cast', active: false);

        $res = $this->post('/login', ['login_id' => 'left_cast', 'password' => 'password']);

        $this->assertGuest();
        $res->assertSessionHasErrors('login_id');
    }

    public function test_forced_password_change_redirects(): void
    {
        $store = $this->makeStore('a');
        $user = $this->makeUser($store, RoleKey::Cast, 'newcast', mustChange: true);

        $this->actingAs($user)->get('/')->assertRedirect(route('password.change'));
    }

    public function test_cast_cannot_access_admin_routes(): void
    {
        $store = $this->makeStore('a');
        $cast = $this->makeUser($store, RoleKey::Cast, 'yui');

        $this->actingAs($cast)->get('/admin/accounts')->assertForbidden();
    }

    public function test_manager_can_access_admin_accounts(): void
    {
        $store = $this->makeStore('a');
        $manager = $this->makeUser($store, RoleKey::Manager, 'tencho');

        $this->actingAs($manager)->get('/admin/accounts')->assertOk();
    }

    public function test_store_isolation_manager_sees_only_own_store_members(): void
    {
        $storeA = $this->makeStore('a');
        $storeB = $this->makeStore('b');
        $managerA = $this->makeUser($storeA, RoleKey::Manager, 'tencho_a');
        $this->makeUser($storeB, RoleKey::Cast, 'cast_b_only');

        $res = $this->actingAs($managerA)->get('/admin/accounts');

        $res->assertOk();
        $res->assertSee('tencho_a');
        $res->assertDontSee('cast_b_only');
    }

    public function test_manager_cannot_suspend_user_from_other_store(): void
    {
        $storeA = $this->makeStore('a');
        $storeB = $this->makeStore('b');
        $managerA = $this->makeUser($storeA, RoleKey::Manager, 'tencho_a');
        $castB = $this->makeUser($storeB, RoleKey::Cast, 'cast_b');

        $this->actingAs($managerA)
            ->post("/admin/accounts/{$castB->id}/suspend", ['reason' => 'x'])
            ->assertNotFound();

        $this->assertDatabaseHas('user_store_memberships', [
            'user_id' => $castB->id, 'store_id' => $storeB->id, 'status' => 'active',
        ]);
    }

    public function test_suspend_blocks_active_session_immediately(): void
    {
        $store = $this->makeStore('a');
        $manager = $this->makeUser($store, RoleKey::Manager, 'tencho');
        $cast = $this->makeUser($store, RoleKey::Cast, 'yui');

        // 責任者が停止
        $this->actingAs($manager)->post("/admin/accounts/{$cast->id}/suspend", ['reason' => '退店']);

        // 停止されたキャストのセッションは即座に締め出される
        $this->actingAs($cast->fresh())->get('/')->assertRedirect(route('login'));
    }

    public function test_account_create_generates_login_and_audit(): void
    {
        $store = $this->makeStore('a');
        $manager = $this->makeUser($store, RoleKey::Manager, 'tencho');

        $res = $this->actingAs($manager)->post('/admin/accounts', [
            'name' => '新人 花子', 'login_id' => 'hanako', 'role' => 'cast', 'display_name' => 'ハナ',
        ]);

        $res->assertRedirect(route('admin.accounts.index'));
        $this->assertDatabaseHas('users', ['login_id' => 'hanako', 'must_change_password' => true]);
        $this->assertDatabaseHas('casts', ['display_name' => 'ハナ', 'store_id' => $store->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.create', 'store_id' => $store->id]);
    }

    public function test_manager_cannot_create_admin_or_manager_is_rejected(): void
    {
        $store = $this->makeStore('a');
        $manager = $this->makeUser($store, RoleKey::Manager, 'tencho');

        // 責任者は manager ロールを付与できない（assignableRoles=cast/staff）
        $this->actingAs($manager)->from('/admin/accounts/create')->post('/admin/accounts', [
            'name' => 'x', 'login_id' => 'x2', 'role' => 'manager', 'display_name' => 'x',
        ])->assertSessionHasErrors('role');
    }
}
