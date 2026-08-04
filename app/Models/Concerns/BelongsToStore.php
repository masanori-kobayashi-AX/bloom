<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Support\CurrentStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * store_id を持つモデルに、現在店舗での自動スコープと自動セットを付与する。
 * 全クエリで店舗越境の漏洩を防ぐ最終防衛線（§8「全クエリで store_id を強制」）。
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope());

        static::creating(function (Model $model): void {
            if (empty($model->store_id)) {
                $storeId = CurrentStore::id();
                if ($storeId !== null) {
                    $model->store_id = $storeId;
                }
            }
        });
    }

    /** グローバルスコープを外して全店舗横断で取得（管理者・システム処理限定で使用）。 */
    public static function acrossStores(): Builder
    {
        return static::withoutGlobalScope(StoreScope::class);
    }
}
