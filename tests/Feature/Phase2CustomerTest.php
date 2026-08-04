<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2CustomerTest extends TestCase
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
        $cast = Cast::create([
            'store_id' => $this->store->id, 'user_id' => $user->id,
            'display_name' => $loginId, 'status' => 'active',
        ]);

        return [$user, $cast];
    }

    private function makeRelationship(Cast $cast, string $name, ?string $line = null): CastCustomerRelationship
    {
        $customer = Customer::create(['store_id' => $this->store->id]);

        return CastCustomerRelationship::create([
            'store_id' => $this->store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id,
            'customer_name' => $name, 'line_display_name' => $line, 'status' => 'line_only',
        ]);
    }

    public function test_cast_registers_customer_with_minimal_fields(): void
    {
        [$user, $cast] = $this->makeCast('yui');

        $res = $this->actingAs($user)->post(route('cast.customers.store'), [
            'customer_name' => 'たろうさん', 'status' => 'line_only', 'confirmed' => 0,
        ]);

        $rel = CastCustomerRelationship::where('cast_id', $cast->id)->first();
        $this->assertNotNull($rel);
        $res->assertRedirect(route('cast.customers.show', $rel));
        $this->assertDatabaseHas('customers', ['id' => $rel->customer_id, 'store_id' => $this->store->id]);
        $this->assertDatabaseHas('customer_status_histories', ['relationship_id' => $rel->id, 'to_status' => 'line_only']);
    }

    public function test_cast_sees_only_own_customers(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        [$u2, $c2] = $this->makeCast('rin');
        $this->makeRelationship($c1, 'ワイの客');
        $this->makeRelationship($c2, 'リンの客');

        $res = $this->actingAs($u1)->get(route('cast.customers.index'));
        $res->assertOk()->assertSee('ワイの客')->assertDontSee('リンの客');
    }

    public function test_cast_cannot_view_another_casts_customer(): void
    {
        [$u1] = $this->makeCast('yui');
        [$u2, $c2] = $this->makeCast('rin');
        $relOther = $this->makeRelationship($c2, 'リンの客');

        $this->actingAs($u1)->get(route('cast.customers.show', $relOther))->assertForbidden();
    }

    public function test_private_note_route_blocked_for_manager_and_staff(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        $rel = $this->makeRelationship($c1, '客');
        $manager = $this->makeUser(RoleKey::Manager, 'tencho');
        $staff = $this->makeUser(RoleKey::Staff, 'kuro');

        // role:cast,admin により、店長・黒服はこの画面自体にアクセスできない
        $this->actingAs($manager)->get(route('cast.customers.show', $rel))->assertForbidden();
        $this->actingAs($staff)->get(route('cast.customers.show', $rel))->assertForbidden();
    }

    public function test_owner_can_view_private_notes_and_is_audited(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        $rel = $this->makeRelationship($c1, '客');
        $rel->notes()->create(['store_id' => $this->store->id, 'cast_id' => $c1->id, 'body' => 'ないしょのメモ']);
        $owner = $this->makeUser(RoleKey::Admin, 'admin');

        $res = $this->actingAs($owner)->get(route('cast.customers.show', $rel));

        $res->assertOk()->assertSee('ないしょのメモ');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'view.private_note', 'auditable_id' => $rel->id, 'user_id' => $owner->id,
        ]);
    }

    public function test_shared_note_can_be_added(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        $rel = $this->makeRelationship($c1, '客');

        $this->actingAs($u1)->post(route('cast.customers.notes.shared', $rel), [
            'category' => 'ボトル', 'body' => '山崎ボトルキープ',
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_shared_notes', [
            'relationship_id' => $rel->id, 'body' => '山崎ボトルキープ', 'category' => 'ボトル',
        ]);
    }

    public function test_next_action_create_and_complete(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        $rel = $this->makeRelationship($c1, '客');

        $this->actingAs($u1)->post(route('cast.customers.actions.store', $rel), [
            'content' => 'お礼LINE', 'kind' => 'thanks',
        ])->assertRedirect();

        $action = $rel->nextActions()->first();
        $this->assertNotNull($action);
        $this->assertFalse($action->completed);

        $this->actingAs($u1)->post(route('cast.actions.complete', $action))->assertRedirect();
        $this->assertTrue($action->fresh()->completed);
    }

    public function test_duplicate_candidate_warns_before_creating(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        $this->makeRelationship($c1, 'たろう', 'たろちゃん');

        // 同じLINE表示名で未確認登録 → 名寄せ警告で戻る
        $res = $this->actingAs($u1)->post(route('cast.customers.store'), [
            'customer_name' => 'たろう2', 'line_display_name' => 'たろちゃん', 'status' => 'line_only', 'confirmed' => 0,
        ]);
        $res->assertRedirect(route('cast.customers.create'));
        $res->assertSessionHas('dup_check');
        $this->assertSame(1, CastCustomerRelationship::where('cast_id', $c1->id)->count());

        // confirmed=1 で新規作成
        $this->actingAs($u1)->post(route('cast.customers.store'), [
            'customer_name' => 'たろう2', 'line_display_name' => 'たろちゃん', 'status' => 'line_only', 'confirmed' => 1,
        ])->assertRedirect();
        $this->assertSame(2, CastCustomerRelationship::where('cast_id', $c1->id)->count());
    }

    public function test_status_change_records_history(): void
    {
        [$u1, $c1] = $this->makeCast('yui');
        $rel = $this->makeRelationship($c1, '客');

        $this->actingAs($u1)->post(route('cast.customers.status', $rel), [
            'status' => 'honshimei', 'note' => '本指名になった',
        ])->assertRedirect();

        $this->assertSame('honshimei', $rel->fresh()->status->value);
        $this->assertDatabaseHas('customer_status_histories', [
            'relationship_id' => $rel->id, 'from_status' => 'line_only', 'to_status' => 'honshimei',
        ]);
    }
}
