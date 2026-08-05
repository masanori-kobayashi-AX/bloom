<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class VisitBottle extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'visit_id', 'customer_bottle_id', 'name', 'action'];
}
