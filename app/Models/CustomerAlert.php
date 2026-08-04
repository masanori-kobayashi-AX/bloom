<?php

namespace App\Models;

use App\Enums\AlertCategory;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 重大注意情報。事実と主観を分けて記録する。
 */
class CustomerAlert extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'cast_id', 'category', 'fact', 'subjective',
        'severity', 'resolved', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => AlertCategory::class,
            'resolved' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
