<?php

namespace App\Enums;

/**
 * 来店の状態。present=来店中、left=退店済。
 */
enum VisitStatus: string
{
    case Present = 'present';
    case Left = 'left';

    public function label(): string
    {
        return match ($this) {
            self::Present => '来店中',
            self::Left => '退店済',
        };
    }
}
