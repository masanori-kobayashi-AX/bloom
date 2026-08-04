<?php

namespace App\Support;

use App\Models\CastCustomerRelationship;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

/**
 * 顧客関係へのアクセス認可の単一窓口。
 * ・キャスト：自分の関係のみ
 * ・オーナー(admin)：全件閲覧可（自分用メモ閲覧時は監査ログ記録）
 * ・それ以外（店長・黒服）：この画面群では不可（集計/共有は別導線）
 */
class CustomerAccess
{
    /** 現在ユーザーのキャストプロフィールID（キャストでなければ null）。 */
    public static function currentCastId(): ?int
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->castProfile?->id;
    }

    /** 関係を閲覧できるか。 */
    public static function canView(CastCustomerRelationship $rel): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true; // オーナーは全件閲覧可
        }

        $castId = self::currentCastId();

        return $castId !== null && $rel->cast_id === $castId;
    }

    /** 関係を編集できるか（キャスト本人のみ。オーナーは閲覧専用）。 */
    public static function canEdit(CastCustomerRelationship $rel): bool
    {
        $castId = self::currentCastId();

        return $castId !== null && $rel->cast_id === $castId;
    }

    /** 自分用メモの閲覧可否。本人 or オーナー。オーナー閲覧時は監査ログを残す。 */
    public static function canViewPrivateNotes(CastCustomerRelationship $rel): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $castId = self::currentCastId();
        if ($castId !== null && $rel->cast_id === $castId) {
            return true; // 本人
        }

        if ($user->isAdmin()) {
            // オーナー閲覧：トラブル・表示バグ対応のための閲覧を記録（§3-2）
            AuditLogger::record(
                'view.private_note',
                $rel,
                'オーナーが自分用メモを閲覧',
                storeId: $rel->store_id,
            );

            return true;
        }

        return false;
    }
}
