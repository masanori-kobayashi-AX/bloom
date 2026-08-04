<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * created_by / updated_by を認証ユーザーで自動セットする。
 */
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            $uid = Auth::id();
            if ($uid !== null) {
                if (empty($model->created_by)) {
                    $model->created_by = $uid;
                }
                if (empty($model->updated_by)) {
                    $model->updated_by = $uid;
                }
            }
        });

        static::updating(function (Model $model): void {
            $uid = Auth::id();
            if ($uid !== null) {
                $model->updated_by = $uid;
            }
        });
    }
}
