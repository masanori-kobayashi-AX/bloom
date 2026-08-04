<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserStoreMembership extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'store_id', 'user_id', 'role_id', 'status',
        'joined_at', 'suspended_at', 'suspended_reason', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
