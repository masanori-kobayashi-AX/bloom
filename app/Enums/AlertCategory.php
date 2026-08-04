<?php

namespace App\Enums;

/**
 * 重大注意情報の分類（§5-5-D）。曖昧・侮辱的な表現を正式分類名にしないための固定リスト。
 */
enum AlertCategory: string
{
    case PhysicalContact = 'physical_contact';   // 身体接触への注意
    case SexualRemarks = 'sexual_remarks';       // 性的発言が多い
    case Verbal = 'verbal_abuse';                // 暴言・威圧
    case Alcohol = 'alcohol_trouble';            // 飲酒トラブル
    case Payment = 'payment_trouble';            // 支払いトラブル
    case Stalker = 'stalker';                    // ストーカー懸念
    case OutsideContact = 'outside_contact';     // 店外での接触要求
    case CastConflict = 'cast_conflict';         // キャスト間トラブル
    case Other = 'other';                        // その他安全上の注意

    public function label(): string
    {
        return match ($this) {
            self::PhysicalContact => '身体接触への注意',
            self::SexualRemarks => '性的発言が多い',
            self::Verbal => '暴言・威圧',
            self::Alcohol => '飲酒トラブル',
            self::Payment => '支払いトラブル',
            self::Stalker => 'ストーカー懸念',
            self::OutsideContact => '店外での接触要求',
            self::CastConflict => 'キャスト間トラブル',
            self::Other => 'その他安全上の注意',
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
