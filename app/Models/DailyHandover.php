<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 今日の申し送り（キャスト→黒服「今日伝えておきたい情報」）。日付単位。
 */
class DailyHandover extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'cast_id', 'relationship_id', 'for_date', 'body',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['for_date' => 'date'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cast()
    {
        return $this->belongsTo(Cast::class);
    }
}
