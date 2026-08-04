<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'user_id', 'display_name', 'position', 'status',
        'note', 'created_by', 'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /** この黒服が現在担当しているキャスト。 */
    public function activeAssignments()
    {
        return $this->hasMany(CastStaffAssignment::class, 'staff_id')->whereNull('released_at');
    }

    public function assignedCasts()
    {
        return $this->belongsToMany(Cast::class, 'cast_staff_assignments', 'staff_id', 'cast_id')
            ->wherePivotNull('released_at');
    }
}
