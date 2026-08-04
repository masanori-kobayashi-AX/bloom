<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 自分用メモ。可視範囲＝本人キャスト＋オーナー(admin)のみ。
 */
class CustomerNote extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'relationship_id', 'cast_id', 'body', 'created_by', 'updated_by',
    ];

    public function relationship()
    {
        return $this->belongsTo(CastCustomerRelationship::class, 'relationship_id');
    }
}
