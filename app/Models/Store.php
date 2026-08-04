<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'name', 'code', 'timezone', 'is_active', 'note', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function memberships()
    {
        return $this->hasMany(UserStoreMembership::class);
    }
}
