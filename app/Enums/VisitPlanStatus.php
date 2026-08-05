<?php

namespace App\Enums;

/**
 * 来店予定の状態。
 */
enum VisitPlanStatus: string
{
    case Pending = 'pending';       // 未確認（キャストが登録）
    case Confirmed = 'confirmed';   // 黒服が確認済み
    case Arrived = 'arrived';       // 来店した（visitに変換済み）
    case Cancelled = 'cancelled';   // 取消

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未確認',
            self::Confirmed => '確認済み',
            self::Arrived => '来店済み',
            self::Cancelled => '取消',
        };
    }
}
