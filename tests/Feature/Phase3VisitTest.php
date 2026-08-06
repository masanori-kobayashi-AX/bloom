<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use App\Models\Visit;
use App\Models\VisitPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3VisitTest extends TestCase
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
        $user = User::create([
            'name' => $loginId, 'login_id' => $loginId, 'password' => Hash::make('password'),
            'must_change_password' => false, 'is_active' => true,
        ]);
        UserStoreMembership::create([
            'store_id' => $this->store->id, 'user_id' => $user->id,
            'role_id' => Role::where('key', $role->value)->value('id'),
            'status' => 'active', 'joined_at' => now(),
        ]);

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

    private function makeRelationship(Cast $cast, string $name, ?string $line = null): CastCustomerRelationship
    {
        $customer = Customer::create(['store_id' => $this->store->id]);

        return CastCustomerRelationship::create([
            'store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id,
            'customer_name' => $name, 'line_display_name' => $line, 'status' => 'line_only',
        ]);
    }

    public function test_cast_creates_visit_plan_and_staff_sees_it(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');

        $this->actingAs($cu)->post(route('cast.customers.plans.store', $rel), [
            'planned_date' => now()->toDateString(), 'note' => 'VIP対応で',
        ])->assertRedirect();

        $this->assertDatabaseHas('visit_plans', ['customer_id' => $rel->customer_id, 'cast_id' => $cast->id, 'status' => 'pending']);
        $this->actingAs($staff)->get(route('staff.plans'))->assertOk()->assertSee('たろう');
    }

    public function test_staff_starts_visit_from_plan(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');
        $plan = VisitPlan::create([
            'store_id' => $this->store->id, 'customer_id' => $rel->customer_id, 'cast_id' => $cast->id,
            'planned_date' => now()->toDateString(), 'status' => 'pending',
        ]);

        $this->actingAs($staff)->post(route('staff.visits.start'), ['visit_plan_id' => $plan->id])->assertRedirect();

        $visit = Visit::where('customer_id', $rel->customer_id)->first();
        $this->assertNotNull($visit);
        $this->assertSame('present', $visit->status->value);
        $this->assertSame($cast->id, $visit->primary_cast_id);
        $this->assertSame('arrived', $plan->fresh()->status->value);
        $this->assertDatabaseHas('visit_casts', ['visit_id' => $visit->id, 'cast_id' => $cast->id, 'role' => 'nominated']);
    }

    public function test_start_visit_is_idempotent(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');

        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $rel->customer_id]);
        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $rel->customer_id]);

        $this->assertSame(1, Visit::where('customer_id', $rel->customer_id)->present()->count());
    }

    public function test_staff_leaves_visit_and_records_sales(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');
        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $rel->customer_id]);
        $visit = Visit::where('customer_id', $rel->customer_id)->first();

        $this->actingAs($staff)->post(route('staff.visits.leave', $visit), [
            'amount' => 50000, 'nomination_type' => 'honshimei', 'is_honshimei' => 1,
        ])->assertRedirect(route('staff.work.index'));

        $visit->refresh();
        $this->assertSame('left', $visit->status->value);
        $this->assertSame(50000, $visit->amount);
        $this->assertTrue($visit->is_honshimei);
        $this->assertDatabaseHas('sales_records', ['visit_id' => $visit->id, 'amount' => 50000]);
    }

    public function test_daily_handover_shows_on_staff_work(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');

        $this->actingAs($cu)->post(route('cast.customers.handovers.store', $rel), ['body' => 'ボトル入れてくれそう']);
        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $rel->customer_id]);

        $this->actingAs($staff)->get(route('staff.work.index'))->assertOk()->assertSee('ボトル入れてくれそう');
    }

    public function test_shared_note_shows_on_staff_work_as_base_info(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');

        $this->actingAs($cu)->post(route('cast.customers.notes.shared', $rel), ['body' => '通されると喜ぶ']);
        // 指名キャスト付きで来店開始（大元共有はそのキャストの関係から取得）
        $plan = VisitPlan::create(['store_id' => $this->store->id, 'customer_id' => $rel->customer_id, 'cast_id' => $cast->id, 'planned_date' => now()->toDateString(), 'status' => 'pending']);
        $this->actingAs($staff)->post(route('staff.visits.start'), ['visit_plan_id' => $plan->id]);

        $this->actingAs($staff)->get(route('staff.work.index'))->assertOk()->assertSee('通されると喜ぶ');
    }

    public function test_cast_can_declare_after_likelihood_on_visit(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');

        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $rel->customer_id]);
        $this->actingAs($cu)->post(route('cast.customers.after.update', $rel), ['after_status' => 'likely'])->assertRedirect();

        $visit = Visit::where('customer_id', $rel->customer_id)->first();
        $this->assertSame('likely', $visit->after_status->value);
        $this->assertSame($cast->id, $visit->primary_cast_id); // 誰が行くかが紐づく
    }

    public function test_after_watch_log_flow(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        // 黒服/店長がアフター開始（どのキャストが・どの店へ）を記録
        $this->actingAs($manager)->post(route('staff.after.store'), [
            'cast_id' => $cast->id,
            'destination' => 'BAR月',
            'companion' => 'たろう',
        ])->assertRedirect();

        $log = \App\Models\AfterLog::first();
        $this->assertNotNull($log);
        $this->assertSame('out', $log->status);           // 帰宅連絡待ち
        $this->assertNotNull($log->departed_at);

        // 見守り画面にキャスト名と行き先が出る
        $this->actingAs($manager)->get(route('staff.after'))->assertOk()->assertSee('yui')->assertSee('BAR月');

        // 帰宅連絡が来たら見守り終了
        $this->actingAs($manager)->post(route('staff.after.home', $log))->assertRedirect();
        $this->assertSame('home', $log->fresh()->status);
        $this->assertNotNull($log->fresh()->home_reported_at);
    }

    public function test_after_overdue_is_flagged_when_expected_home_passed(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        // 予定帰宅時刻を過去にして登録＝未連絡（予定超過）
        $this->actingAs($manager)->post(route('staff.after.store'), [
            'cast_id' => $cast->id,
            'destination' => 'BAR月',
            'expected_home_at' => now()->subMinutes(30)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $log = \App\Models\AfterLog::first();
        $this->assertTrue($log->isOverdue()); // 予定を過ぎて帰宅連絡なし＝要対応

        // 画面に「未連絡（予定帰宅を過ぎています）」と対応手順が出る
        $this->actingAs($manager)->get(route('staff.after'))
            ->assertOk()->assertSee('未連絡')->assertSee('店長へ連絡');

        // 帰宅連絡が来れば超過は解消
        $this->actingAs($manager)->post(route('staff.after.home', $log))->assertRedirect();
        $this->assertFalse($log->fresh()->isOverdue());
    }

    public function test_cast_cannot_access_staff_area(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $this->actingAs($cu)->get(route('staff.work.index'))->assertForbidden();
    }

    public function test_staff_search_finds_by_bottle_name_within_scope(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro');
        // 黒服の検索は担当キャスト関連等に限定されるため、担当を紐付ける
        $staffProfile = \App\Models\StaffProfile::where('user_id', $staff->id)->first();
        \App\Models\CastStaffAssignment::create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'staff_id' => $staffProfile->id, 'assigned_at' => now()]);
        $this->actingAs($cu)->post(route('cast.customers.bottles.store', $rel), ['name' => '山崎12年']);

        $this->actingAs($staff)->get(route('staff.search', ['q' => '山崎']))->assertOk()->assertSee('たろう');
    }

    public function test_staff_search_is_scoped_away_from_unrelated_customers(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $rel = $this->makeRelationship($cast, 'たろう');
        $staff = $this->makeStaff('kuro'); // 担当紐付けなし・来店なし
        $this->actingAs($cu)->post(route('cast.customers.bottles.store', $rel), ['name' => '山崎12年']);

        // 担当外・来店予定なしの顧客は黒服の検索に出ない
        $this->actingAs($staff)->get(route('staff.search', ['q' => '山崎']))->assertOk()->assertDontSee('たろう');
    }
}
