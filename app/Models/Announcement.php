<?php

namespace App\Models;

use App\Enums\AnnouncementCategory;
use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes, Blameable, BelongsToStore;

    protected $fillable = [
        'store_id', 'author_id', 'category', 'title', 'body', 'importance',
        'expires_on', 'published_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => AnnouncementCategory::class,
            'expires_on' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /** 掲載中（公開済み・期限内）のもの。 */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where(function ($q) {
                $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString());
            });
    }
}
