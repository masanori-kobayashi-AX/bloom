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
}
