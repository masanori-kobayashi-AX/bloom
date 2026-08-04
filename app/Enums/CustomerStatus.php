<?php

namespace App\Enums;

/**
 * 顧客ステータス（関係の段階・§5-3）。場内→本指名の移行把握に使う。
 */
enum CustomerStatus: string
{
    case LineOnly = 'line_only';       // LINE交換のみ
    case Free = 'free';                // フリー接客
    case Zainai = 'zainai';            // 場内指名
    case Honshimei = 'honshimei';      // 本指名
    case Continuing = 'continuing';    // 継続顧客
    case Important = 'important';      // 重要顧客
    case Dormant = 'dormant';          // 休眠
    case Closed = 'closed';            // 対応終了
    case Caution = 'caution';          // 要注意

    public function label(): string
    {
        return match ($this) {
            self::LineOnly => 'LINE交換のみ',
            self::Free => 'フリー接客',
            self::Zainai => '場内指名',
            self::Honshimei => '本指名',
            self::Continuing => '継続顧客',
            self::Important => '重要顧客',
            self::Dormant => '休眠',
            self::Closed => '対応終了',
            self::Caution => '要注意',
        };
    }

    /** @return array<string,string> value=>label */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }
}
