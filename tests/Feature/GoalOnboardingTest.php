<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastGoal;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use App\Models\Visit;
use App\Services\MetricsService;
use App\Support\CurrentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GoalOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (RoleKey::seed() as $row) {
            Role::updateOrCreate(['key' => $row['key']], $row);
        }
        $this->store = Store::create(['name' => '店A', 'code' => 'a', 'timezone' => 'Asia/Tokyo', 'is_active' => true]);
    }

    /** @return array{0:User,1:Cast} */
    private function makeCast(string $loginId, bool $mustChange = false): array
    {
        $user = User::create(['name' => $loginId, 'login_id' => $loginId, 'password' => Hash::make('password'), 'must_change_password' => $mustChange, 'is_active' => true]);
        UserStoreMembership::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'role_id' => Role::where('key', 'cast')->value('id'), 'status' => 'active', 'joined_at' => now()]);
        $cast = Cast::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'display_name' => '仮名', 'status' => 'active']);

        return [$user, $cast];
    }

    public function test_first_login_cast_setup_sets_name_target_and_password(): void
    {
        [$user, $cast] = $this->makeCast('newcast', mustChange: true);

        // 初回はセットアップ画面（源氏名フィールドあり）
        $this->actingAs($user)->get(route('password.change'))->assertOk()->assertSee('源氏名');

        $res = $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'bloom12345',
            'password_confirmation' => 'bloom12345',
            'display_name' => 'ユイ',
            'kana' => 'ゆい',
            'target_amount' => 1000000,
        ]);
        $res->assertRedirect(route('home'));

        $cast->refresh();
        $this->assertSame('ユイ', $cast->display_name);
        $this->assertFalse((bool) $user->fresh()->must_change_password);
        $this->assertDatabaseHas('cast_goals', ['cast_id' => $cast->id, 'target_amount' => 1000000, 'period' => CastGoal::currentPeriod()]);
    }

    public function test_cast_can_set_and_change_monthly_goal(): void
    {
        [$user, $cast] = $this->makeCast('yui');

        $this->actingAs($user)->put(route('cast.goal.update'), ['target_amount' => 500000])->assertRedirect(route('home'));
        $this->assertDatabaseHas('cast_goals', ['cast_id' => $cast->id, 'target_amount' => 500000]);

        // 変更（同月は上書き）
        $this->actingAs($user)->put(route('cast.goal.update'), ['target_amount' => 800000]);
        $this->assertSame(1, CastGoal::where('cast_id', $cast->id)->count());
        $this->assertSame(800000, (int) CastGoal::where('cast_id', $cast->id)->value('target_amount'));
    }

    public function test_progress_rate_reflects_visit_amounts(): void
    {
        CurrentStore::set($this->store->id);
        [$user, $cast] = $this->makeCast('yui');
        CastGoal::create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'period' => CastGoal::currentPeriod(), 'target_amount' => 100000]);
        $customer = Customer::create(['store_id' => $this->store->id]);
        Visit::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'primary_cast_id' => $cast->id, 'arrived_at' => now(), 'status' => 'left', 'amount' => 40000]);

        $progress = (new MetricsService())->castProgress($cast->id);
        CurrentStore::clear();

        $this->assertSame(100000, $progress['target']);
        $this->assertSame(40000, $progress['actual']);
        $this->assertSame(40, $progress['rate']);
        $this->assertTrue($progress['hasGoal']);
    }

    public function test_home_shows_goal_progress(): void
    {
        [$user, $cast] = $this->makeCast('yui');
        CastGoal::create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'period' => CastGoal::currentPeriod(), 'target_amount' => 100000]);

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('今月の目標');
    }
}
