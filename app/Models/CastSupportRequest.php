<?php

namespace App\Models;

use App\Enums\CastCondition;
use App\Enums\SupportAudience;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * キャストのコンディション・相談。公開先(audience)で可視範囲を制御する。
 */
class CastSupportRequest extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'cast_id', 'audience', 'condition', 'body', 'reply', 'replied_at', 'replied_by', 'status',
        'acknowledged_at', 'acknowledged_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'audience' => SupportAudience::class,
            'condition' => CastCondition::class,
            'acknowledged_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'resolved');
    }
}
