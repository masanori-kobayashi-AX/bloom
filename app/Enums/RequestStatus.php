<?php

namespace App\Enums;

/**
 * キャスト→黒服の業務連絡の状態（§5-11）。
 */
enum RequestStatus: string
{
    case Pending = 'pending';         // 未確認
    case Confirmed = 'confirmed';     // 確認済み
    case InProgress = 'in_progress';  // 対応中
    case Done = 'done';               // 完了

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未確認',
            self::Confirmed => '確認済み',
            self::InProgress => '対応中',
            self::Done => '完了',
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
