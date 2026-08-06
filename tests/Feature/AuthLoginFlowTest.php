<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (RoleKey::seed() as $row) {
            Role::updateOrCreate(['key' => $row['key']], $row);
        }
        $this->store = Store::create(['name' => '店', 'code' => 'a', 'timezone' => 'Asia/Tokyo', 'is_active' => true]);
    }

    private function makeUser(RoleKey $role, string $loginId, ?string $email = null): User
    {
        $user = User::create([
            'name' => $loginId, 'login_id' => $loginId, 'email' => $email,
            'password' => Hash::make('password'), 'must_change_password' => false, 'is_active' => true,
        ]);
        UserStoreMembership::create([
            'store_id' => $this->store->id, 'user_id' => $user->id,
            'role_id' => Role::where('key', $role->value)->value('id'), 'status' => 'active', 'joined_at' => now(),
        ]);

        return $user;
    }

    public function test_manager_with_email_requires_two_factor(): void
    {
        $this->makeUser(RoleKey::Manager, 'tencho', 'tencho@example.com');

        $res = $this->post('/login', ['login_id' => 'tencho', 'password' => 'password']);

        // まだログインは完了せず、コード入力へ
        $res->assertRedirect(route('login.2fa'));
        $this->assertGuest();

        // テスト環境ではコードがフラッシュされる → それで確定
        $code = session('dev_2fa_code');
        $this->assertNotEmpty($code);

        $this->post('/login-verify', ['code' => $code])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_wrong_two_factor_code_is_rejected(): void
    {
        $this->makeUser(RoleKey::Admin, 'owner', 'owner@example.com');
        $this->post('/login', ['login_id' => 'owner', 'password' => 'password'])->assertRedirect(route('login.2fa'));

        $this->post('/login-verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_cast_without_email_logs_in_without_two_factor(): void
    {
        $this->makeUser(RoleKey::Cast, 'yui'); // メールなし

        $this->post('/login', ['login_id' => 'yui', 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_manager_without_email_skips_two_factor(): void
    {
        $this->makeUser(RoleKey::Manager, 'tencho'); // メール未登録は届かないのでPWのみ

        $this->post('/login', ['login_id' => 'tencho', 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_role_specific_login_rejects_other_roles(): void
    {
        $this->makeUser(RoleKey::Cast, 'yui');

        // キャストが店長専用入口からは入れない
        $res = $this->post('/login/manager', ['login_id' => 'yui', 'password' => 'password']);
        $res->assertSessionHasErrors('login_id');
        $this->assertGuest();

        // 自分の入口なら入れる
        $this->post('/login/cast', ['login_id' => 'yui', 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_role_login_pages_render(): void
    {
        foreach (['cast', 'staff', 'manager', 'owner'] as $role) {
            $this->get("/login/{$role}")->assertOk()->assertSee('専用ログイン');
        }
        $this->get('/login/unknown')->assertNotFound();
    }

    public function test_forgot_password_sends_reset_link_for_registered_email(): void
    {
        Notification::fake();
        $user = $this->makeUser(RoleKey::Manager, 'tencho', 'tencho@example.com');

        $this->post('/password/forgot', ['email' => 'tencho@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_updates_password(): void
    {
        $user = $this->makeUser(RoleKey::Manager, 'tencho', 'tencho@example.com');
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => 'tencho@example.com',
            'password' => 'newsecret8',
            'password_confirmation' => 'newsecret8',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('newsecret8', $user->fresh()->password));
    }
}
