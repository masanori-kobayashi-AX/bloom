<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;

/**
 * キャストの月次目標。支援用の指標（人事評価には使わない）。
 */
class CastGoal extends Model
{
    use Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'cast_id', 'period', 'target_amount', 'target_honshimei', 'target_dohan', 'note', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'target_honshimei' => 'integer',
            'target_dohan' => 'integer',
        ];
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }

    public static function currentPeriod(): string
    {
        return now()->format('Y-m');
    }
}
