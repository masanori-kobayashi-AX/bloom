<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\CustomerAlias;
use App\Models\CustomerBottle;
use App\Models\NextAction;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitCast;
use Illuminate\Database\Seeder;

/**
 * 試験運用向けのサンプルデータ（§13 Phase 6）。DatabaseSeeder の後に実行する。
 *   php artisan db:seed --class=Database\\Seeders\\TrialSeeder
 * 何度実行しても顧客が増えすぎないよう、既にデータがあればスキップする。
 */
class TrialSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('code', 'raftel')->first();
        if (! $store) {
            $this->command?->warn('先に DatabaseSeeder を実行してください。');

            return;
        }
        if (CastCustomerRelationship::where('store_id', $store->id)->exists()) {
            $this->command?->info('既に試験データがあります。スキップしました。');

            return;
        }

        $casts = Cast::where('store_id', $store->id)->get();
        $sample = [
            ['たっくん', 'Taku', 'honshimei', 'core', '響17年'],
            ['社長', 'K社長', 'honshimei', 'core', 'ドンペリ白'],
            ['ケンさん', 'ken_1985', 'zainai', 'nurturing', null],
            ['まさや', 'masaya', 'continuing', 'continuing', '山崎12年'],
            ['のむらさん', 'nomura', 'line_only', 'light', null],
            ['ゆうき', 'yuki.g', 'free', 'nurturing', null],
        ];

        foreach ($casts as $i => $cast) {
            foreach ($sample as $j => [$name, $line, $status, $importance, $bottle]) {
                if (($j % $casts->count()) !== $i) {
                    continue; // キャストに顧客を分散
                }
                $customer = Customer::create(['store_id' => $store->id, 'kana' => $name]);
                $rel = CastCustomerRelationship::create([
                    'store_id' => $store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id,
                    'customer_name' => $name, 'line_display_name' => $line, 'status' => $status,
                    'importance' => $importance, 'line_exchanged_on' => now()->subDays(20 + $j),
                    'first_met_on' => now()->subDays(20 + $j),
                ]);
                CustomerAlias::create(['store_id' => $store->id, 'customer_id' => $customer->id, 'cast_id' => $cast->id, 'type' => 'line_current', 'value' => $line]);

                $rel->notes()->create(['store_id' => $store->id, 'cast_id' => $cast->id, 'body' => "{$name}さん：ゴルフの話で盛り上がる。次は同伴の相談。"]);

                if ($bottle) {
                    CustomerBottle::create(['store_id' => $store->id, 'customer_id' => $customer->id, 'name' => $bottle, 'status' => 'kept', 'opened_on' => now()->subDays(15)]);
                }

                if (in_array($status, ['honshimei', 'continuing'], true)) {
                    NextAction::create(['store_id' => $store->id, 'relationship_id' => $rel->id, 'cast_id' => $cast->id, 'content' => 'お礼LINEを送る', 'kind' => 'thanks', 'due_on' => now()->addDays($j)]);

                    // 過去の来店履歴
                    $visit = Visit::create([
                        'store_id' => $store->id, 'customer_id' => $customer->id, 'primary_cast_id' => $cast->id,
                        'arrived_at' => now()->subDays(7 + $j)->setTime(21, 0), 'left_at' => now()->subDays(7 + $j)->setTime(23, 30),
                        'status' => 'left', 'nomination_type' => 'honshimei', 'is_honshimei' => true, 'amount' => 40000 + $j * 5000,
                    ]);
                    VisitCast::create(['store_id' => $store->id, 'visit_id' => $visit->id, 'cast_id' => $cast->id, 'role' => 'nominated']);
                }
            }
        }

        // お知らせ・営業ヒント
        $author = User::where('login_id', 'tencho')->value('id');
        Announcement::create(['store_id' => $store->id, 'author_id' => $author, 'category' => 'tip', 'title' => '花火大会をきっかけに', 'body' => "今週末は花火大会。『花火見に行った？』から自然に近況を聞けます。写真を送ってくれた方にはお礼を。", 'importance' => 'normal', 'published_at' => now()]);
        Announcement::create(['store_id' => $store->id, 'author_id' => $author, 'category' => 'notice', 'title' => '来週の営業について', 'body' => "月曜は貸切のため通常営業はお休みです。", 'importance' => 'high', 'published_at' => now(), 'expires_on' => now()->addDays(10)]);

        $this->command?->info('試験データを投入しました。');
    }
}
