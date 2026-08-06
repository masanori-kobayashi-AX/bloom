<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 席マスタ。店長が管理し、来店時の席割りに使う。
 */
class Seat extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = ['store_id', 'name', 'sort_order', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
