<?php

namespace App\Enums;

/**
 * キャストのコンディション申告（§5-12）。任意。モチベーション点数・日々評価には使わない。
 */
enum CastCondition: string
{
    case Normal = 'normal';           // 今日は通常
    case NeedFollow = 'need_follow';  // 少しフォローしてほしい
    case TalkStaff = 'talk_staff';    // 担当と話したい
    case TalkManager = 'talk_manager'; // 責任者と話したい
    case Consult = 'consult';         // 接客上の相談がある

    public function label(): string
    {
        return match ($this) {
            self::Normal => '今日は通常',
            self::NeedFollow => '少しフォローしてほしい',
            self::TalkStaff => '担当と話したい',
            self::TalkManager => '責任者と話したい',
            self::Consult => '接客上の相談がある',
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
