<?php

namespace Tests\Feature;

use App\Enums\RoleKey;
use App\Models\Announcement;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Database\Seeders\TrialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase6OpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_seeder_populates_realistic_data(): void
    {
        foreach (RoleKey::seed() as $row) {
            Role::updateOrCreate(['key' => $row['key']], $row);
        }
        $store = Store::create(['name' => 'ラフテル', 'code' => 'raftel', 'timezone' => 'Asia/Tokyo', 'is_active' => true]);

        // tencho（お知らせ投稿者）と2キャスト
        $tencho = User::create(['name' => '店長', 'login_id' => 'tencho', 'password' => Hash::make('password'), 'must_change_password' => false, 'is_active' => true]);
        UserStoreMembership::create(['store_id' => $store->id, 'user_id' => $tencho->id, 'role_id' => Role::where('key', 'manager')->value('id'), 'status' => 'active', 'joined_at' => now()]);
        foreach (['yui', 'rin'] as $login) {
            $u = User::create(['name' => $login, 'login_id' => $login, 'password' => Hash::make('password'), 'must_change_password' => false, 'is_active' => true]);
            UserStoreMembership::create(['store_id' => $store->id, 'user_id' => $u->id, 'role_id' => Role::where('key', 'cast')->value('id'), 'status' => 'active', 'joined_at' => now()]);
            Cast::create(['store_id' => $store->id, 'user_id' => $u->id, 'display_name' => $login, 'status' => 'active']);
        }

        $this->seed(TrialSeeder::class);

        $this->assertGreaterThan(0, CastCustomerRelationship::where('store_id', $store->id)->count());
        $this->assertGreaterThanOrEqual(2, Announcement::where('store_id', $store->id)->count());

        // 冪等：再実行しても増えない
        $before = CastCustomerRelationship::count();
        $this->seed(TrialSeeder::class);
        $this->assertSame($before, CastCustomerRelationship::count());
    }
}
