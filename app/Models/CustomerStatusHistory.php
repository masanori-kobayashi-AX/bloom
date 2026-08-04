<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

/**
 * ステータス変更履歴。追記専用。
 */
class CustomerStatusHistory extends Model
{
    use BelongsToStore;

    public const UPDATED_AT = null;

    protected $fillable = [
        'store_id', 'relationship_id', 'from_status', 'to_status', 'changed_by', 'note', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
