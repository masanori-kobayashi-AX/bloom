<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAlias extends Model
{
    use SoftDeletes, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'cast_id', 'type', 'value',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
