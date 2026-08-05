<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_code', 'kana', 'age_range', 'occupation', 'area',
        'birthday', 'note', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['birthday' => 'date'];
    }

    public function aliases()
    {
        return $this->hasMany(CustomerAlias::class);
    }

    public function relationships()
    {
        return $this->hasMany(CastCustomerRelationship::class);
    }

    public function alerts()
    {
        return $this->hasMany(CustomerAlert::class);
    }

    public function bottles()
    {
        return $this->hasMany(CustomerBottle::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function visitPlans()
    {
        return $this->hasMany(VisitPlan::class);
    }

    public function handovers()
    {
        return $this->hasMany(DailyHandover::class);
    }

    /** 有効なキープボトル。 */
    public function keptBottles()
    {
        return $this->hasMany(CustomerBottle::class)->where('status', 'kept');
    }
}
