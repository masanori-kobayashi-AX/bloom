<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;   // 連続失敗の許容回数
    private const LOCK_MINUTES = 15;  // 一時ロック時間

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('login_id', $credentials['login_id'])->first();

        // 一時ロック中
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $this->recordLogin($user, $credentials['login_id'], false, $request);
            throw ValidationException::withMessages([
                'login_id' => 'ログイン試行が続いたため一時的にロックされています。しばらくしてから再度お試しください。',
            ]);
        }

        // ユーザー不在・パスワード不一致
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $this->registerFailure($user);
            }
            $this->recordLogin($user, $credentials['login_id'], false, $request);
            throw ValidationException::withMessages([
                'login_id' => 'ログインIDまたはパスワードが正しくありません。',
            ]);
        }

        // 停止・退店アカウント
        if (! $user->isUsable()) {
            $this->recordLogin($user, $credentials['login_id'], false, $request);
            throw ValidationException::withMessages([
                'login_id' => 'このアカウントは利用停止されています。店舗責任者にご連絡ください。',
            ]);
        }

        // 成功
        $user->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->saveQuietly();

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        $this->recordLogin($user, $credentials['login_id'], true, $request);
        AuditLogger::record('auth.login', $user, '本人がログイン', storeId: $user->currentStoreId(), userId: $user->id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLogger::record('auth.logout', $user, '本人がログアウト', storeId: $user->currentStoreId(), userId: $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function registerFailure(User $user): void
    {
        $attempts = $user->failed_attempts + 1;
        $user->failed_attempts = $attempts;
        if ($attempts >= self::MAX_ATTEMPTS) {
            $user->locked_until = now()->addMinutes(self::LOCK_MINUTES);
            $user->failed_attempts = 0;
        }
        $user->saveQuietly();
    }

    private function recordLogin(?User $user, string $loginId, bool $ok, Request $request): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'login_id_attempted' => $loginId,
            'succeeded' => $ok,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
