<?php

namespace App\Models;

use App\Enums\AfterStatus;
use App\Enums\NominationType;
use App\Enums\VisitStatus;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'visit_plan_id', 'primary_cast_id',
        'arrived_at', 'left_at', 'status', 'nomination_type', 'dohan',
        'is_zainai', 'is_honshimei', 'seat', 'party_size', 'amount', 'arrival_note',
        'after_note', 'caution', 'after_status', 'after_status_note',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'arrived_at' => 'datetime',
            'left_at' => 'datetime',
            'status' => VisitStatus::class,
            'nomination_type' => NominationType::class,
            'after_status' => AfterStatus::class,
            'dohan' => 'boolean',
            'is_zainai' => 'boolean',
            'is_honshimei' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function primaryCast()
    {
        return $this->belongsTo(Cast::class, 'primary_cast_id');
    }

    public function visitCasts()
    {
        return $this->hasMany(VisitCast::class);
    }

    public function bottles()
    {
        return $this->hasMany(VisitBottle::class);
    }

    public function scopePresent(Builder $q): Builder
    {
        return $q->where('status', VisitStatus::Present->value);
    }
}
