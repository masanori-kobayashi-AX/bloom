<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\CustomerAlert;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use App\Models\Visit;
use App\Support\CurrentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BacklogHardeningTest extends TestCase
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

    private function makeStaff(string $loginId): User
    {
        $user = $this->makeUser(RoleKey::Staff, $loginId);
        StaffProfile::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'display_name' => $loginId, 'position' => '黒服', 'status' => 'active']);

        return $user;
    }

    public function test_expired_alert_is_excluded_from_active_scope(): void
    {
        CurrentStore::set($this->store->id);
        $customer = Customer::create(['store_id' => $this->store->id]);
        CustomerAlert::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'category' => 'payment_trouble', 'fact' => '古い件', 'severity' => 'mid', 'expires_on' => now()->subDay()]);
        CustomerAlert::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'category' => 'stalker', 'fact' => '現役の件', 'severity' => 'high', 'expires_on' => now()->addDays(30)]);

        $active = CustomerAlert::where('customer_id', $customer->id)->active()->get();
        CurrentStore::clear();

        $this->assertSame(1, $active->count());
        $this->assertSame('現役の件', $active->first()->fact);
    }

    public function test_cast_can_resolve_own_alert(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $alert = CustomerAlert::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'category' => 'stalker', 'fact' => 'x', 'severity' => 'high']);

        $this->actingAs($cu)->post(route('cast.alerts.resolve', $alert))->assertRedirect();
        $this->assertTrue($alert->fresh()->resolved);
        $this->assertDatabaseHas('audit_logs', ['action' => 'alert.resolve', 'auditable_id' => $alert->id]);
    }

    public function test_staff_can_cancel_present_visit(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'line_only']);
        $staff = $this->makeStaff('kuro');
        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $customer->id]);
        $visit = Visit::where('customer_id', $customer->id)->first();

        $this->actingAs($staff)->post(route('staff.visits.cancel', $visit))->assertRedirect(route('staff.work.index'));

        $this->assertSoftDeleted('visits', ['id' => $visit->id]);
        $this->assertSame(0, Visit::where('customer_id', $customer->id)->present()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.cancel', 'auditable_id' => $visit->id]);
    }

    public function test_structured_alert_stores_source_and_expiry(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'line_only']);

        $this->actingAs($cu)->post(route('cast.customers.alerts.store', $rel), [
            'category' => 'payment_trouble', 'fact' => 'カード不通で後日払い', 'source' => '会計', 'occurred_on' => now()->subDays(3)->toDateString(),
            'action_plan' => '次回は現金確認', 'expires_on' => now()->addDays(90)->toDateString(), 'severity' => 'mid',
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_alerts', ['customer_id' => $customer->id, 'source' => '会計', 'action_plan' => '次回は現金確認']);
    }
}
