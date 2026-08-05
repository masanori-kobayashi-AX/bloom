<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class VisitCast extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'visit_id', 'cast_id', 'role'];

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }
}
