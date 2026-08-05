<?php

namespace App\Enums;

/**
 * お知らせ・営業ヒントの種類（§5-13）。
 */
enum AnnouncementCategory: string
{
    case Notice = 'notice';     // 店舗連絡
    case Event = 'event';       // イベント案内
    case Topic = 'topic';       // 今週の話題
    case Tip = 'tip';           // 営業ヒント
    case Success = 'success';   // 成功事例
    case Caution = 'caution';   // 注意喚起

    public function label(): string
    {
        return match ($this) {
            self::Notice => '店舗連絡',
            self::Event => 'イベント案内',
            self::Topic => '今週の話題',
            self::Tip => '営業ヒント',
            self::Success => '成功事例',
            self::Caution => '注意喚起',
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
