<?php

namespace App\Models;

use App\Enums\CustomerImportance;
use App\Enums\CustomerStatus;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * キャストと顧客の関係。キャストの「自分の顧客」の実体はこれ。
 */
class CastCustomerRelationship extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'cast_id', 'customer_name', 'line_display_name',
        'status', 'importance', 'line_exchanged_on', 'first_met_on', 'met_context',
        'zainai_first_on', 'honshimei_first_on', 'favorite_drink', 'hobby',
        'usual_weekday', 'visit_expectation', 'next_talk', 'avatar_emoji',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'importance' => CustomerImportance::class,
            'line_exchanged_on' => 'date',
            'first_met_on' => 'date',
            'zainai_first_on' => 'date',
            'honshimei_first_on' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class, 'relationship_id')->latest();
    }

    public function sharedNotes()
    {
        return $this->hasMany(CustomerSharedNote::class, 'relationship_id')->latest();
    }

    public function nextActions()
    {
        return $this->hasMany(NextAction::class, 'relationship_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(CustomerStatusHistory::class, 'relationship_id')->latest();
    }

    public function contactLogs()
    {
        return $this->hasMany(ContactLog::class, 'relationship_id')->latest('contacted_at');
    }

    /** 自分（ログイン中キャスト）の関係だけに絞る。 */
    public function scopeOwnedBy(Builder $query, int $castId): Builder
    {
        return $query->where('cast_id', $castId);
    }
}
