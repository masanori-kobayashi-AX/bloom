<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 監査ログ。追記専用のため updated_at を持たない。
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'store_id', 'user_id', 'action', 'auditable_type', 'auditable_id',
        'description', 'before', 'after', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
