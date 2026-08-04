<?php

namespace App\Enums;

/**
 * 顧客区分・重要度（キャストが手動設定・§5-9）。ステータス(段階)とは別軸の優先度タグ。
 */
enum CustomerImportance: string
{
    case Core = 'core';           // コア
    case Continuing = 'continuing'; // 継続
    case Nurturing = 'nurturing';  // 育成中
    case Light = 'light';          // ライト
    case Dormant = 'dormant';      // 休眠
    case Closed = 'closed';        // 対応終了

    public function label(): string
    {
        return match ($this) {
            self::Core => 'コア',
            self::Continuing => '継続',
            self::Nurturing => '育成中',
            self::Light => 'ライト',
            self::Dormant => '休眠',
            self::Closed => '対応終了',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }
}
