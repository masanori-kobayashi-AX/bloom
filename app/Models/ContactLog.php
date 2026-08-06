<?php

namespace App\Models;

use App\Enums\ContactChannel;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 顧客とのやり取り履歴（電話・LINE・来店 など）。追記中心。
 */
class ContactLog extends Model
{
    use SoftDeletes, BelongsToStore;

    protected $fillable = [
        'store_id', 'relationship_id', 'cast_id', 'customer_id',
        'channel', 'note', 'contacted_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ContactChannel::class,
            'contacted_at' => 'datetime',
        ];
    }

    public function relationship()
    {
        return $this->belongsTo(CastCustomerRelationship::class, 'relationship_id');
    }
}
