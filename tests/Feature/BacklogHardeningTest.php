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

    public function test_walkin_start_with_cast_id_attributes_primary_cast(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'honshimei']);
        $staff = $this->makeStaff('kuro');

        // 検索からの来店開始（予定なし）でも cast_id を渡せば指名キャストに紐づく
        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $customer->id, 'cast_id' => $cast->id])->assertRedirect();

        $visit = Visit::where('customer_id', $customer->id)->first();
        $this->assertSame($cast->id, $visit->primary_cast_id);
        $this->assertDatabaseHas('visit_casts', ['visit_id' => $visit->id, 'cast_id' => $cast->id, 'role' => 'nominated']);
    }

    public function test_staff_can_add_help_cast_and_cannot_remove_nominated(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        [$cu2, $help] = $this->makeCast('rin');
        $customer = Customer::create(['store_id' => $this->store->id]);
        CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'honshimei']);
        $staff = $this->makeStaff('kuro');
        $this->actingAs($staff)->post(route('staff.visits.start'), ['customer_id' => $customer->id, 'cast_id' => $cast->id]);
        $visit = Visit::where('customer_id', $customer->id)->first();

        // ヘルプキャスト追加
        $this->actingAs($staff)->post(route('staff.visits.casts.add', $visit), ['cast_id' => $help->id])->assertRedirect();
        $this->assertDatabaseHas('visit_casts', ['visit_id' => $visit->id, 'cast_id' => $help->id, 'role' => 'help']);

        // 指名キャストは外せない
        $nominated = \App\Models\VisitCast::where('visit_id', $visit->id)->where('cast_id', $cast->id)->first();
        $this->actingAs($staff)->delete(route('staff.visits.casts.remove', [$visit, $nominated]))->assertStatus(422);

        // ヘルプは外せる
        $helpVc = \App\Models\VisitCast::where('visit_id', $visit->id)->where('cast_id', $help->id)->first();
        $this->actingAs($staff)->delete(route('staff.visits.casts.remove', [$visit, $helpVc]))->assertRedirect();
        $this->assertDatabaseMissing('visit_casts', ['id' => $helpVc->id]);
    }

    public function test_close_consult_creates_manager_support_request(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => 'ケンさん', 'status' => 'dormant']);

        $this->actingAs($cu)->post(route('cast.customers.close-consult', $rel))->assertRedirect();

        $this->assertDatabaseHas('cast_support_requests', ['cast_id' => $cast->id, 'audience' => 'manager', 'status' => 'open']);
    }

    public function test_customer_index_close_filter_and_sort(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $c1 = Customer::create(['store_id' => $this->store->id]);
        CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $c1->id, 'cast_id' => $cast->id, 'customer_name' => '休眠さん', 'status' => 'dormant']);
        $c2 = Customer::create(['store_id' => $this->store->id]);
        CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $c2->id, 'cast_id' => $cast->id, 'customer_name' => '元気さん', 'status' => 'honshimei']);

        // クローズ検討フィルタ：休眠は候補、本指名(来店なしだが対象外)は除外
        $this->actingAs($cu)->get(route('cast.customers.index', ['status' => '__close__']))
            ->assertOk()->assertSee('休眠さん')->assertDontSee('元気さん');

        // ソート指定でも落ちない
        $this->actingAs($cu)->get(route('cast.customers.index', ['sort' => 'last_visit']))->assertOk()->assertSee('休眠さん');
    }

    public function test_customer_overview_hides_private_from_manager_shows_to_admin(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'honshimei']);
        $rel->notes()->create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'body' => 'ないしょのコメント']);
        $rel->sharedNotes()->create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'body' => '共有メモ']);

        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        $admin = $this->makeUser(RoleKey::Admin, 'owner');

        // オーナー：本人コメントも見える＋監査記録
        $this->actingAs($admin)->get(route('admin.customers'))->assertOk()
            ->assertSee('ないしょのコメント')->assertSee('共有メモ');
        $this->assertDatabaseHas('audit_logs', ['action' => 'view.private_overview']);

        // 店長：本人コメントは見えない／共有は見える
        $this->actingAs($manager)->get(route('admin.customers'))->assertOk()
            ->assertDontSee('ないしょのコメント')->assertSee('共有メモ');

        // キャストは全体一覧に入れない
        $this->actingAs($cu)->get(route('admin.customers'))->assertForbidden();
    }

    public function test_owner_can_edit_overview_cell_manager_cannot(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'line_only']);
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        $admin = $this->makeUser(RoleKey::Admin, 'owner');

        // オーナー：状況をセル編集できる＋監査
        $this->actingAs($admin)->post(route('admin.customers.cell', $rel), ['field' => 'status', 'value' => 'honshimei'])->assertRedirect();
        $this->assertSame('honshimei', $rel->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'overview.edit', 'auditable_id' => $rel->id]);

        // 店長：編集は不可（閲覧のみ）
        $this->actingAs($manager)->post(route('admin.customers.cell', $rel), ['field' => 'status', 'value' => 'dormant'])->assertForbidden();
        $this->assertSame('honshimei', $rel->fresh()->status->value);
    }

    public function test_contact_log_records_phone_and_line(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'line_only']);

        $this->actingAs($cu)->post(route('cast.customers.contact', $rel), ['channel' => 'phone'])->assertRedirect();
        $this->actingAs($cu)->post(route('cast.customers.contact', $rel), ['channel' => 'line', 'note' => '来週の約束'])->assertRedirect();

        $this->assertDatabaseHas('contact_logs', ['relationship_id' => $rel->id, 'channel' => 'phone']);
        $this->assertDatabaseHas('contact_logs', ['relationship_id' => $rel->id, 'channel' => 'line', 'note' => '来週の約束']);
    }

    public function test_arrived_creates_today_plan_and_visit_log(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'honshimei']);

        $this->actingAs($cu)->post(route('cast.customers.arrived', $rel))->assertRedirect();

        $this->assertDatabaseHas('visit_plans', ['customer_id' => $customer->id, 'cast_id' => $cast->id]);
        $this->assertDatabaseHas('contact_logs', ['relationship_id' => $rel->id, 'channel' => 'visit']);

        // 黒服の今日の予定に出る
        $staff = $this->makeStaff('kuro');
        $this->actingAs($staff)->get(route('staff.plans'))->assertOk()->assertSee('客');
    }

    public function test_manager_cannot_manage_system_admin_account(): void
    {
        $admin = $this->makeUser(RoleKey::Admin, 'owner');
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        [$cu, $cast] = $this->makeCast('yui');

        // 店長は管理者のPW再設定・停止ができない（権限昇格防止）
        $this->actingAs($manager)->post(route('admin.accounts.reset-password', $admin))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.accounts.suspend', $admin), ['reason' => 'x'])->assertForbidden();

        // 店長の一覧に管理者は出ない
        $this->actingAs($manager)->get(route('admin.accounts.index'))->assertOk()->assertDontSee('owner');

        // 店長はキャストは管理できる
        $this->actingAs($manager)->post(route('admin.accounts.reset-password', $cu))->assertRedirect();
    }

    public function test_manager_manages_seats_and_cast_cannot(): void
    {
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        [$cu, $cast] = $this->makeCast('yui');

        $this->actingAs($manager)->post(route('admin.seats.store'), ['name' => 'VIP1'])->assertRedirect();
        $this->assertDatabaseHas('seats', ['name' => 'VIP1', 'store_id' => $this->store->id]);
        $this->actingAs($manager)->get(route('admin.seats.index'))->assertOk()->assertSee('VIP1');

        $this->actingAs($cu)->get(route('admin.seats.index'))->assertForbidden();
    }

    public function test_manager_views_customer_detail_readonly_without_private_memo(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => 'たっくん', 'status' => 'honshimei']);
        $rel->notes()->create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'body' => 'ないしょのメモ']);
        $rel->sharedNotes()->create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'body' => '共有メモ']);

        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        $res = $this->actingAs($manager)->get(route('admin.customer.show', $rel));
        $res->assertOk()->assertSee('たっくん')->assertSee('共有メモ')->assertDontSee('ないしょのメモ');

        // 黒服はこの詳細に入れない
        $staff = $this->makeStaff('kuro');
        $this->actingAs($staff)->get(route('admin.customer.show', $rel))->assertForbidden();
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

    public function test_manager_closes_cast_and_can_reopen(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $staff = $this->makeStaff('kuro');
        $staffProfile = StaffProfile::where('user_id', $staff->id)->first();
        // 担当を紐付けておく
        \App\Models\CastStaffAssignment::create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'staff_id' => $staffProfile->id, 'assigned_at' => now()]);

        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        // 退店（クローズ）
        $this->actingAs($manager)->post(route('admin.accounts.suspend', $cu), ['reason' => '退店'])->assertRedirect();

        $cast->refresh();
        $this->assertSame('left', $cast->status);            // 在籍→離任
        $this->assertNotNull($cast->left_on);
        $this->assertFalse((bool) $cu->fresh()->is_active);   // ログイン不可
        // 担当が解除されている（黒服のアフター候補などから外れる）
        $this->assertSame(0, $staffProfile->assignedCasts()->count());
        // アクティブなキャスト一覧から外れる
        $this->assertFalse(Cast::where('status', 'active')->where('id', $cast->id)->exists());

        // 復帰
        $this->actingAs($manager)->post(route('admin.accounts.reactivate', $cu))->assertRedirect();
        $cast->refresh();
        $this->assertSame('active', $cast->status);
        $this->assertNull($cast->left_on);
        $this->assertTrue((bool) $cu->fresh()->is_active);
        // 担当は自動では戻らない（手動再設定）
        $this->assertSame(0, $staffProfile->assignedCasts()->count());
    }

    public function test_former_cast_customers_visible_to_manager_via_former_filter(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        $customer = Customer::create(['store_id' => $this->store->id]);
        CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => 'たっくん', 'status' => 'honshimei']);

        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        // 退店（クローズ）
        $this->actingAs($manager)->post(route('admin.accounts.suspend', $cu), ['reason' => '退店'])->assertRedirect();

        // 全体（在籍のみ）には退店者の顧客は出ない
        $this->actingAs($manager)->get(route('admin.customers'))->assertOk()->assertDontSee('たっくん');
        // 退店者を指名すれば、店長は顧客情報を見られる
        $this->actingAs($manager)->get(route('admin.customers', ['cast_id' => $cast->id]))
            ->assertOk()->assertSee('たっくん')->assertSee('退店者');
    }
}
