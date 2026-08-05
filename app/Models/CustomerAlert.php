<?php

namespace App\Models;

use App\Enums\AlertCategory;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 重大注意情報。事実と主観を分けて記録。情報源・発生日・対応方針・失効日で構造化。
 */
class CustomerAlert extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'cast_id', 'category', 'fact', 'subjective',
        'source', 'occurred_on', 'action_plan', 'expires_on',
        'severity', 'resolved', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => AlertCategory::class,
            'resolved' => 'boolean',
            'occurred_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /** 現役の注意（未解決かつ失効日前）。失効・解決済みはデータは残すが表示から外す。 */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('resolved', false)
            ->where(function ($q) {
                $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString());
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }
}
