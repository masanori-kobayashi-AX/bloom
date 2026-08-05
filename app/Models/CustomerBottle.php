<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerBottle extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'name', 'opened_on', 'status', 'note',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['opened_on' => 'date'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
