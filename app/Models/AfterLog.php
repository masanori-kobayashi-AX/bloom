<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * アフター記録。黒服がキャストの安全（帰宅連絡まで）を見守るための台帳。
 */
class AfterLog extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'cast_id', 'customer_id', 'companion', 'destination',
        'departed_at', 'expected_home_at', 'home_reported_at', 'status', 'note', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'departed_at' => 'datetime',
            'expected_home_at' => 'datetime',
            'home_reported_at' => 'datetime',
        ];
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /** 帰宅連絡がまだ来ていない（＝見守り継続中）。 */
    public function scopeOut($query)
    {
        return $query->where('status', 'out');
    }

    public function isHome(): bool
    {
        return $this->status === 'home';
    }

    /** 予定帰宅時刻を過ぎても帰宅連絡がない＝未連絡（要対応）。 */
    public function isOverdue(): bool
    {
        return $this->status === 'out'
            && $this->expected_home_at !== null
            && $this->expected_home_at->isPast();
    }
}
