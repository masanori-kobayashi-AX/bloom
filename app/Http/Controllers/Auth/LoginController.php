<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleKey;
use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;   // 連続失敗の許容回数
    private const LOCK_MINUTES = 15;  // 一時ロック時間

    /** ログインURLの役割セグメント → ロール。owner=システム管理者。 */
    private const ROLE_MAP = [
        'cast' => RoleKey::Cast,
        'staff' => RoleKey::Staff,
        'manager' => RoleKey::Manager,
        'owner' => RoleKey::Admin,
    ];

    private const ROLE_LABEL = [
        'cast' => 'キャスト',
        'staff' => '黒服（担当スタッフ）',
        'manager' => '店長・責任者',
        'owner' => '管理者',
    ];

    /** 共通ログイン（全役割可）。 */
    public function create()
    {
        return view('auth.login', ['role' => null, 'roleLabel' => null]);
    }

    /** 役割別ログイン入口（その役割のみログイン可）。 */
    public function createRole(string $role)
    {
        abort_unless(array_key_exists($role, self::ROLE_MAP), 404);

        return view('auth.login', ['role' => $role, 'roleLabel' => self::ROLE_LABEL[$role]]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->attempt($request, null);
    }

    public function storeRole(Request $request, string $role): RedirectResponse
    {
        abort_unless(array_key_exists($role, self::ROLE_MAP), 404);

        return $this->attempt($request, $role);
    }

    /** 認証本体。$role が指定された入口では、その役割のアカウントだけを通す。 */
    private function attempt(Request $request, ?string $role): RedirectResponse
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

        // 役割別入口では、その役割以外は弾く（誤入口・なりすまし対策）
        if ($role !== null && $user->role() !== self::ROLE_MAP[$role]) {
            $this->recordLogin($user, $credentials['login_id'], false, $request);
            throw ValidationException::withMessages([
                'login_id' => 'このページは' . self::ROLE_LABEL[$role] . '専用のログイン入口です。ご自身の役割のログインページからお入りください。',
            ]);
        }

        // メール2段階認証が必要な役割（店長・管理者＋メール登録済み）はコード確認へ
        if (LoginService::needsTwoFactor($user)) {
            $devCode = LoginService::startTwoFactor($user, $request);

            return redirect()->route('login.2fa')
                ->with('status', $user->maskedEmail() . ' に認証コードを送信しました。届いたコードを入力してください。')
                ->with($devCode ? ['dev_2fa_code' => $devCode] : []);
        }

        // 2FA不要はそのままログイン確定
        return LoginService::complete($user, $request);
    }

    /** 2段階認証コードの入力画面。 */
    public function showTwoFactor(Request $request)
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    /** 入力されたコードを検証してログインを確定。 */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa:user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['required', 'string']]);
        /** @var User $user */
        $user = User::findOrFail($userId);

        if (! LoginService::verifyCode($user, trim($data['code']))) {
            throw ValidationException::withMessages([
                'code' => 'コードが正しくないか、有効期限が切れています。再送してお試しください。',
            ]);
        }

        return LoginService::complete($user, $request);
    }

    /** コードを再送する。 */
    public function resendTwoFactor(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa:user_id');
        if (! $userId) {
            return redirect()->route('login');
        }
        /** @var User $user */
        $user = User::findOrFail($userId);
        $devCode = LoginService::startTwoFactor($user, $request);

        return redirect()->route('login.2fa')
            ->with('status', '認証コードを再送しました。')
            ->with($devCode ? ['dev_2fa_code' => $devCode] : []);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user) {
            \App\Services\AuditLogger::record('auth.logout', $user, '本人がログアウト', storeId: $user->currentStoreId(), userId: $user->id);
        }

        \Illuminate\Support\Facades\Auth::logout();
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
