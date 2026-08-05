<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SalesRecord extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'visit_id', 'customer_id', 'cast_id', 'amount', 'category',
        'recorded_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }
}
