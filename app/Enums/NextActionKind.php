<?php

namespace App\Enums;

/**
 * 次のアクションの種別（§5-8）。MVPでは自動送信せず「誰に連絡するか忘れない」ことに集中。
 */
enum NextActionKind: string
{
    case LineContact = 'line_contact';   // LINE連絡
    case Thanks = 'thanks';              // お礼
    case VisitCheck = 'visit_check';     // 来店確認
    case Birthday = 'birthday';          // 誕生日連絡
    case Event = 'event';                // イベント案内
    case CatchUp = 'catch_up';           // 近況確認
    case Dohan = 'dohan';                // 同伴相談
    case Other = 'other';                // その他

    public function label(): string
    {
        return match ($this) {
            self::LineContact => 'LINE連絡',
            self::Thanks => 'お礼',
            self::VisitCheck => '来店確認',
            self::Birthday => '誕生日連絡',
            self::Event => 'イベント案内',
            self::CatchUp => '近況確認',
            self::Dohan => '同伴相談',
            self::Other => 'その他',
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
