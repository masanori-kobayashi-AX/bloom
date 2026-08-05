<?php

namespace App\Enums;

/**
 * アフター見込み（キャストが申告・黒服/店長が把握）。
 * 「今日この子はアフターに行けそうか」を営業中に共有するための状態。
 */
enum AfterStatus: string
{
    case Unlikely = 'unlikely'; // 難しい
    case Maybe = 'maybe';       // 微妙
    case Likely = 'likely';     // 行けそう
    case Going = 'going';       // 確定

    public function label(): string
    {
        return match ($this) {
            self::Unlikely => 'アフター難しい',
            self::Maybe => 'アフター微妙',
            self::Likely => 'アフター行けそう',
            self::Going => 'アフター確定',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Unlikely => '#8a8090',
            self::Maybe => '#c9a227',
            self::Likely => '#b25c72',
            self::Going => '#2f8f6b',
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
