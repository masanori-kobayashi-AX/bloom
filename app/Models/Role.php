<?php

namespace App\Models;

use App\Enums\RoleKey;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['key', 'name', 'level'];

    public function key(): RoleKey
    {
        return RoleKey::from($this->key);
    }

    public function memberships()
    {
        return $this->hasMany(UserStoreMembership::class);
    }
}
