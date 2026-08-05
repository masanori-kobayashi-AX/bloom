<?php

namespace App\Enums;

/**
 * 指名区分（§5-6/§5-7）。場内→本指名の転換把握に使う。
 */
enum NominationType: string
{
    case Free = 'free';           // フリー
    case Zainai = 'zainai';       // 場内指名
    case Honshimei = 'honshimei'; // 本指名

    public function label(): string
    {
        return match ($this) {
            self::Free => 'フリー',
            self::Zainai => '場内指名',
            self::Honshimei => '本指名',
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
