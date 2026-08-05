<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\CastStaffAssignment;
use App\Models\CastSupportRequest;
use App\Models\Customer;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase5CommsTest extends TestCase
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

    /** @return array{0:User,1:StaffProfile} */
    private function makeStaff(string $loginId): array
    {
        $user = $this->makeUser(RoleKey::Staff, $loginId);
        $staff = StaffProfile::create(['store_id' => $this->store->id, 'user_id' => $user->id, 'display_name' => $loginId, 'position' => '黒服', 'status' => 'active']);

        return [$user, $staff];
    }

    private function assign(Cast $cast, StaffProfile $staff): void
    {
        CastStaffAssignment::create(['store_id' => $this->store->id, 'cast_id' => $cast->id, 'staff_id' => $staff->id, 'assigned_at' => now()]);
    }

    public function test_manager_announcement_is_seen_and_read_by_cast(): void
    {
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        [$cu, $cast] = $this->makeCast('yui');

        $this->actingAs($manager)->post(route('manager.announcements.store'), [
            'category' => 'tip', 'title' => '花火大会の話題', 'body' => '花火の話から誘ってみましょう', 'importance' => 'normal',
        ])->assertRedirect(route('manager.announcements.index'));

        $ann = \App\Models\Announcement::first();
        $this->actingAs($cu)->get(route('announcements.index'))->assertOk()->assertSee('花火大会の話題');
        $this->actingAs($cu)->post(route('announcements.read', $ann))->assertRedirect();
        $this->assertDatabaseHas('announcement_reads', ['announcement_id' => $ann->id, 'user_id' => $cu->id]);
    }

    public function test_cast_cannot_manage_announcements(): void
    {
        [$cu] = $this->makeCast('yui');
        $this->actingAs($cu)->get(route('manager.announcements.index'))->assertForbidden();
    }

    public function test_support_to_manager_visible_to_manager_not_staff(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        [$su, $staff] = $this->makeStaff('kuro');
        $this->assign($cast, $staff);
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        $this->actingAs($cu)->post(route('cast.support.store'), [
            'audience' => 'manager', 'condition' => 'talk_manager', 'body' => '担当変更の相談',
        ])->assertRedirect();

        // 責任者には見える
        $this->actingAs($manager)->get(route('inbox.index'))->assertOk()->assertSee('担当変更の相談');
        // 担当黒服には見えない（公開先=責任者のみ）
        $this->actingAs($su)->get(route('inbox.index'))->assertOk()->assertDontSee('担当変更の相談');
    }

    public function test_support_to_staff_visible_to_assigned_staff_not_manager(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        [$su, $staff] = $this->makeStaff('kuro');
        $this->assign($cast, $staff);
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');

        $this->actingAs($cu)->post(route('cast.support.store'), [
            'audience' => 'staff', 'body' => '今日は少しフォローしてほしい',
        ])->assertRedirect();

        $this->actingAs($su)->get(route('inbox.index'))->assertOk()->assertSee('今日は少しフォローしてほしい');
        $this->actingAs($manager)->get(route('inbox.index'))->assertOk()->assertDontSee('今日は少しフォローしてほしい');
    }

    public function test_staff_cannot_acknowledge_manager_audience_support(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        [$su, $staff] = $this->makeStaff('kuro');
        $this->assign($cast, $staff);

        $support = CastSupportRequest::create([
            'store_id' => $this->store->id, 'cast_id' => $cast->id, 'audience' => 'manager', 'body' => 'x', 'status' => 'open',
        ]);

        $this->actingAs($su)->post(route('inbox.support.ack', $support))->assertForbidden();
    }

    public function test_cast_staff_request_flows_to_assigned_staff_inbox(): void
    {
        [$cu, $cast] = $this->makeCast('yui');
        [$su, $staff] = $this->makeStaff('kuro');
        $this->assign($cast, $staff);
        $customer = Customer::create(['store_id' => $this->store->id]);
        $rel = CastCustomerRelationship::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'customer_name' => '客', 'status' => 'line_only']);

        $this->actingAs($cu)->post(route('cast.customers.staff-request.store', $rel), [
            'type' => 'ボトル確認', 'body' => '山崎の残量確認して',
        ])->assertRedirect();

        $req = \App\Models\StaffRequest::first();
        $this->assertSame($staff->id, $req->staff_id);
        $this->actingAs($su)->get(route('inbox.index'))->assertOk()->assertSee('山崎の残量確認して');

        // 黒服が状態更新（未確認→対応中）
        $this->actingAs($su)->post(route('inbox.requests.status', $req), ['status' => 'in_progress'])->assertRedirect();
        $this->assertSame('in_progress', $req->fresh()->status->value);
        $this->assertNotNull($req->fresh()->confirmed_at);
    }

    public function test_cast_cannot_access_inbox(): void
    {
        [$cu] = $this->makeCast('yui');
        $this->actingAs($cu)->get(route('inbox.index'))->assertForbidden();
    }

    public function test_key_pages_render(): void
    {
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        [$cu] = $this->makeCast('yui');

        $this->actingAs($manager)->get(route('manager.announcements.index'))->assertOk();
        $this->actingAs($manager)->get(route('manager.announcements.create'))->assertOk()->assertSee('お知らせを配信');
        $this->actingAs($cu)->get(route('cast.support.index'))->assertOk()->assertSee('相談・コンディション');
    }
}
