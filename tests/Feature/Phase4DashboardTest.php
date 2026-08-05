<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\Role;
use App\Models\SalesRecord;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use App\Models\Visit;
use App\Services\MetricsService;
use App\Support\CurrentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase4DashboardTest extends TestCase
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

    private function makeUser(RoleKey $role, string $loginId): User
    {
        $user = User::create(['name' => $loginId, 'login_id' => $loginId, 'password' => Hash::make('password'), 'must_change_password' => false, 'is_active' => true]);
        UserStoreMembership::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'role_id' => Role::where('key', $role->value)->value('id'), 'status' => 'active', 'joined_at' => now()]);

        return $user;
    }

    /** @return array{0:User,1:Cast} */
    private function makeCast(string $loginId): array
    {
        $user = $this->makeUser(RoleKey::Cast, $loginId);
        $cast = Cast::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'display_name' => $loginId, 'status' => 'active']);

        return [$user, $cast];
    }

    private function makeRel(Cast $cast, string $name, array $attrs = []): CastCustomerRelationship
    {
        $customer = Customer::create(['store_id' => $this->store->id]);

        return CastCustomerRelationship::create(array_merge([
            'store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id,
            'customer_name' => $name, 'status' => 'line_only',
        ], $attrs));
    }

    public function test_cast_and_staff_cannot_access_dashboard(): void
    {
        [$cu] = $this->makeCast('yui');
        $staff = $this->makeUser(RoleKey::Staff, 'kuro');

        $this->actingAs($cu)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($staff)->get(route('dashboard'))->assertForbidden();
    }

    public function test_manager_sees_dashboard_with_cast_and_warning_note(): void
    {
        [$cu, $cast] = $this->makeCast('ユイ');
        $this->makeRel($cast, '客A', ['status' => 'honshimei']);
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        $res = $this->actingAs($manager)->get(route('dashboard'));
        $res->assertOk()
            ->assertSee('ユイ')
            ->assertSee('店舗全体')
            ->assertSee('単純比較で'); // §6-2 の注意書き
    }

    public function test_store_summary_reflects_data(): void
    {
        CurrentStore::set($this->store->id);
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRel($cast, '客A', ['line_exchanged_on' => now()->toDateString()]);

        $visit = Visit::create([
            'store_id' => $this->store->id, 'customer_id' => $rel->customer_id, 'primary_cast_id' => $cast->id,
            'arrived_at' => now(), 'status' => 'left', 'is_honshimei' => true, 'amount' => 30000,
        ]);
        SalesRecord::create(['store_id' => $this->store->id, 'visit_id' => $visit->id, 'customer_id' => $rel->customer_id, 'cast_id' => $cast->id, 'amount' => 30000, 'recorded_at' => now()]);

        $summary = (new MetricsService())->storeSummary();
        CurrentStore::clear();

        $this->assertSame(1, $summary['activeCasts']);
        $this->assertSame(1, $summary['newCustomers']);
        $this->assertSame(1, $summary['lineExchanges']);
        $this->assertSame(1, $summary['visitedCustomers']);
        $this->assertSame(1, $summary['honshimeiVisits']);
        $this->assertSame(30000, $summary['totalSales']);
    }

    public function test_cast_home_flags_important_not_visited_and_long_absent(): void
    {
        CurrentStore::set($this->store->id);
        [$cu, $cast] = $this->makeCast('yui');

        // 重要顧客・今月来店なし
        $this->makeRel($cast, '重要客', ['importance' => 'core', 'status' => 'important']);

        // 前回来店から40日（長期未来店）
        $absent = $this->makeRel($cast, '疎遠客', ['status' => 'continuing']);
        Visit::create(['store_id' => $this->store->id, 'customer_id' => $absent->customer_id, 'primary_cast_id' => $cast->id, 'arrived_at' => now()->subDays(40), 'status' => 'left']);

        $home = (new MetricsService())->castHome($cast->id);
        CurrentStore::clear();

        $this->assertSame(1, $home['importantNotVisited']->count());
        $this->assertSame('重要客', $home['importantNotVisited']->first()->customer_name);
        $this->assertSame(1, $home['longAbsent']->count());
        $this->assertSame('疎遠客', $home['longAbsent']->first()->customer_name);
    }
}
