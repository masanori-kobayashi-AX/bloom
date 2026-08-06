<?php

namespace App\Enums;

/**
 * 顧客とのやり取りチャンネル（履歴用）。
 */
enum ContactChannel: string
{
    case Phone = 'phone';   // 電話
    case Line = 'line';     // LINE
    case Visit = 'visit';   // 来店
    case Other = 'other';   // その他

    public function label(): string
    {
        return match ($this) {
            self::Phone => '電話',
            self::Line => 'LINE',
            self::Visit => '来店',
            self::Other => 'その他',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Phone => '📞',
            self::Line => '💬',
            self::Visit => '🚪',
            self::Other => '🔖',
        };
    }
}
