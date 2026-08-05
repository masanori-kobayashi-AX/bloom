<?php

namespace App\Enums;

/**
 * 相談の公開先（§5-12）。キャストが明確に選択する。
 */
enum SupportAudience: string
{
    case Staff = 'staff';       // 担当黒服のみ
    case Manager = 'manager';   // 店舗責任者のみ

    public function label(): string
    {
        return match ($this) {
            self::Staff => '担当黒服のみ',
            self::Manager => '店舗責任者のみ',
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
