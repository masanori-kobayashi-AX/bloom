<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\CurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * 監査ログの記録窓口（§3-2）。センシティブ閲覧・重要操作・権限変更で呼ぶ。
 */
class AuditLogger
{
    /**
     * @param  array<string,mixed>|null  $before
     * @param  array<string,mixed>|null  $after
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $before = null,
        ?array $after = null,
        ?int $storeId = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'store_id' => $storeId ?? CurrentStore::id(),
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
