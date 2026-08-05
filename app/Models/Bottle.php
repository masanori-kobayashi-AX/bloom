<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bottle extends Model
{
    use SoftDeletes, BelongsToStore;

    protected $fillable = ['store_id', 'name'];
}
