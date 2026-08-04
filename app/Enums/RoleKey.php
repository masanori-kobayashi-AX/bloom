<?php

namespace App\Enums;

/**
 * システムのロール。level は権限の強さ（比較・表示順に使用）。
 */
enum RoleKey: string
{
    case Cast = 'cast';       // キャスト
    case Staff = 'staff';     // 担当黒服
    case Manager = 'manager'; // 責任者・店長
    case Admin = 'admin';     // システム管理者

    public function label(): string
    {
        return match ($this) {
            self::Cast => 'キャスト',
            self::Staff => '担当黒服',
            self::Manager => '責任者・店長',
            self::Admin => 'システム管理者',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::Cast => 10,
            self::Staff => 20,
            self::Manager => 30,
            self::Admin => 99,
        };
    }

    /** @return array<int, array{key:string,name:string,level:int}> */
    public static function seed(): array
    {
        return array_map(fn (self $r) => [
            'key' => $r->value,
            'name' => $r->label(),
            'level' => $r->level(),
        ], self::cases());
    }
}
