<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;

class CastStaffAssignment extends Model
{
    use Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'cast_id', 'staff_id', 'assigned_at', 'released_at',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('released_at');
    }
}
