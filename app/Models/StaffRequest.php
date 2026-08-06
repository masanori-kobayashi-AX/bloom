<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * キャスト→担当黒服の業務連絡。
 */
class StaffRequest extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'cast_id', 'staff_id', 'customer_id', 'relationship_id',
        'type', 'body', 'reply', 'replied_at', 'replied_by', 'status', 'confirmed_at', 'confirmed_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'confirmed_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'in_progress']);
    }
}
