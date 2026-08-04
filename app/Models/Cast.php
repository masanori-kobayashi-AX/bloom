<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cast extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'user_id', 'display_name', 'kana', 'status',
        'joined_on', 'left_on', 'note', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
            'left_on' => 'date',
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

    /** 現在の担当黒服（released_at が NULL）。 */
    public function activeAssignments()
    {
        return $this->hasMany(CastStaffAssignment::class)->whereNull('released_at');
    }

    public function assignments()
    {
        return $this->hasMany(CastStaffAssignment::class);
    }
}
