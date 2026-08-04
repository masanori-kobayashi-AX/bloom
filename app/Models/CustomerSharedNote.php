<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 店舗共有事項（担当黒服共有）。担当黒服・店長が閲覧できる。
 */
class CustomerSharedNote extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'relationship_id', 'customer_id', 'cast_id', 'category', 'body',
        'created_by', 'updated_by',
    ];

    public function relationship()
    {
        return $this->belongsTo(CastCustomerRelationship::class, 'relationship_id');
    }
}
