<?php

namespace App\Models\Scopes;

use App\Support\CurrentStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * 現在店舗で自動的に絞り込むグローバルスコープ。
 */
class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = CurrentStore::id();
        if ($storeId !== null) {
            $builder->where($model->getTable() . '.store_id', $storeId);
        }
    }
}
