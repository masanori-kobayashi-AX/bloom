<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * リクエスト中の「現在の店舗ID」を解決する単一の窓口。
 * 全モデルの store_id スコープはここを参照する（データ漏洩防止の要）。
 */
class CurrentStore
{
    private static ?int $overrideStoreId = null;
    private static bool $hasOverride = false;

    /** 明示的に店舗を固定する（コンソール処理・テスト用）。 */
    public static function set(?int $storeId): void
    {
        self::$overrideStoreId = $storeId;
        self::$hasOverride = true;
    }

    public static function clear(): void
    {
        self::$overrideStoreId = null;
        self::$hasOverride = false;
    }

    /**
     * 現在の店舗ID。解決できなければ null（＝スコープを適用しない：認証前・シーダー等）。
     */
    public static function id(): ?int
    {
        if (self::$hasOverride) {
            return self::$overrideStoreId;
        }

        $user = Auth::user();

        return $user?->currentStoreId();
    }
}
