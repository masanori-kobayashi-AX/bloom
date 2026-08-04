<?php

namespace Database\Seeders;

use App\Enums\RoleKey;
use App\Models\Cast;
use App\Models\CastStaffAssignment;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStoreMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ロール
        foreach (RoleKey::seed() as $row) {
            Role::updateOrCreate(['key' => $row['key']], $row);
        }
        $roleId = fn (RoleKey $k) => Role::where('key', $k->value)->value('id');

        // 店舗（ラフテル）
        $store = Store::firstOrCreate(
            ['code' => 'raftel'],
            ['name' => 'ラフテル', 'timezone' => 'Asia/Tokyo', 'is_active' => true]
        );

        // 各ロールのユーザーを作成するヘルパ
        $makeUser = function (string $loginId, string $name, RoleKey $role) use ($store, $roleId) {
            $user = User::updateOrCreate(
                ['login_id' => $loginId],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'must_change_password' => false, // 開発シード用（本番は true 運用）
                    'is_active' => true,
                ]
            );
            UserStoreMembership::updateOrCreate(
                ['user_id' => $user->id, 'store_id' => $store->id],
                ['role_id' => $roleId($role), 'status' => 'active', 'joined_at' => now()]
            );

            return $user;
        };

        // 管理者・店長
        $makeUser('admin', 'システム管理者', RoleKey::Admin);
        $manager = $makeUser('tencho', '店長 太郎', RoleKey::Manager);
        StaffProfile::updateOrCreate(
            ['user_id' => $manager->id],
            ['store_id' => $store->id, 'display_name' => '店長', 'position' => '店長', 'status' => 'active']
        );

        // 黒服
        $staffUser = $makeUser('kurofuku1', '黒服 一郎', RoleKey::Staff);
        $staff = StaffProfile::updateOrCreate(
            ['user_id' => $staffUser->id],
            ['store_id' => $store->id, 'display_name' => 'イチロー', 'position' => '黒服', 'status' => 'active']
        );

        // キャスト
        $castDefs = [['yui', 'ユイ'], ['rin', 'リン']];
        foreach ($castDefs as [$loginId, $displayName]) {
            $castUser = $makeUser($loginId, $displayName . '（本名）', RoleKey::Cast);
            $cast = Cast::updateOrCreate(
                ['user_id' => $castUser->id],
                ['store_id' => $store->id, 'display_name' => $displayName, 'status' => 'active', 'joined_on' => now()->toDateString()]
            );

            // 黒服イチローを担当に紐付け（重複回避）
            $exists = CastStaffAssignment::where('store_id', $store->id)
                ->where('cast_id', $cast->id)->whereNull('released_at')->exists();
            if (! $exists) {
                CastStaffAssignment::create([
                    'store_id' => $store->id, 'cast_id' => $cast->id,
                    'staff_id' => $staff->id, 'assigned_at' => now(),
                ]);
            }
        }
    }
}
