<?php

namespace App\Services;

use App\Enums\RoleKey;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * ログイン完了処理と、メールによる2段階認証(OTP)の発行・検証。
 */
class LoginService
{
    private const CODE_MINUTES = 10;

    /**
     * この利用者に2FAを課すか。
     * 全顧客データを扱う店長・管理者かつメール登録済みのみ（届く相手だけ強制）。
     * キャスト・黒服、メール未登録者はパスワードのみ。
     */
    public static function needsTwoFactor(User $user): bool
    {
        return $user->hasRole(RoleKey::Manager, RoleKey::Admin) && ! empty($user->email);
    }

    /** ワンタイムコードを発行してメール送信。開発環境ではコードを画面表示用に返す。 */
    public static function startTwoFactor(User $user, Request $request): ?string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::CODE_MINUTES),
        ])->saveQuietly();

        $request->session()->put('2fa:user_id', $user->id);

        try {
            Mail::raw("Bloom ログイン認証コード：{$code}\n\n有効期限は{" . self::CODE_MINUTES . "}分です。心当たりがない場合はこのメールを破棄してください。", function ($m) use ($user) {
                $m->to($user->email)->subject('【Bloom】ログイン認証コード');
            });
        } catch (\Throwable $e) {
            // 送信失敗はログに残す（ログイン自体は止めない：コード再送で対応）
            report($e);
        }

        // ローカル/テスト環境のみコードを返す（本番は返さない）。画面表示はlocalのみ。
        return app()->environment(['local', 'testing']) ? $code : null;
    }

    /** 入力コードを検証。成功なら true。 */
    public static function verifyCode(User $user, string $code): bool
    {
        if (! $user->two_factor_code || ! $user->two_factor_expires_at || $user->two_factor_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $user->two_factor_code);
    }

    /** ログインを確定（セッション確立・履歴・監査・遷移先）。2FA後もここに合流。 */
    public static function complete(User $user, Request $request): RedirectResponse
    {
        $user->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->saveQuietly();

        Auth::login($user, remember: false);
        $request->session()->forget('2fa:user_id');
        $request->session()->regenerate();

        LoginHistory::create([
            'user_id' => $user->id,
            'login_id_attempted' => $user->login_id,
            'succeeded' => true,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);
        AuditLogger::record('auth.login', $user, '本人がログイン', storeId: $user->currentStoreId(), userId: $user->id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('home'));
    }
}
