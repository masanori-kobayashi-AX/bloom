<?php

namespace App\Models;

use App\Enums\VisitPlanStatus;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitPlan extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'cast_id', 'planned_date', 'planned_time',
        'party_size', 'dohan', 'bottle_note', 'prep', 'note', 'status',
        'confirmed_by', 'confirmed_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'dohan' => 'boolean',
            'status' => VisitPlanStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }
}
