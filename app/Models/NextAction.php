<?php

namespace App\Models;

use App\Enums\NextActionKind;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 次のアクション。自動送信はせず、連絡漏れを防ぐためのリマインド。
 */
class NextAction extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'relationship_id', 'cast_id', 'content', 'kind',
        'due_on', 'completed', 'completed_at', 'note', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'kind' => NextActionKind::class,
            'due_on' => 'date',
            'completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function relationship()
    {
        return $this->belongsTo(CastCustomerRelationship::class, 'relationship_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('completed', false);
    }
}
