<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * パスワード忘れ（メール再設定）。
 * メール登録済みのアカウント（主に店長・管理者・黒服）のみ。
 * キャスト等メール未登録者は、店長の「PW再設定」で対応する（画面で案内）。
 */
class PasswordResetController extends Controller
{
    /** 再設定リンクの送信を依頼する画面。 */
    public function request()
    {
        return view('auth.forgot-password');
    }

    /** 入力メール宛に再設定リンクを送る。存在の有無は明かさない。 */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // 登録の有無に関わらず同じ応答（アカウント列挙を防ぐ）
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'ご登録のメールアドレス宛に、再設定用のリンクを送信しました（該当がある場合）。メールをご確認ください。');
    }

    /** メール内リンクからの再設定フォーム。 */
    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** 新しいパスワードを確定する。 */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                    'failed_attempts' => 0,
                    'locked_until' => null,
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLogger::record('auth.password_reset_self', $user, '本人がメールでパスワードを再設定', storeId: $user->currentStoreId(), userId: $user->id);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'パスワードを再設定しました。新しいパスワードでログインしてください。');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
