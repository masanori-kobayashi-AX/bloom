<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CastGoal;
use App\Services\AuditLogger;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * 本人によるパスワード変更＋初回セットアップ。
 * 初回(must_change_password)のキャストは、源氏名・基本情報・今月の目標もここで設定する。
 */
class PasswordChangeController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $forced = (bool) $user->must_change_password;

        return view('auth.password-change', [
            'forced' => $forced,
            'isCastSetup' => $forced && $user->isCast(),
            'castProfile' => $user->castProfile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isCastSetup = $user->must_change_password && $user->isCast();

        $rules = [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
        if ($isCastSetup) {
            $rules['display_name'] = ['required', 'string', 'max:100']; // 源氏名
            $rules['kana'] = ['nullable', 'string', 'max:100'];
            $rules['target_amount'] = ['nullable', 'integer', 'min:0', 'max:100000000']; // 今月の目標（任意）
        }
        $data = $request->validate($rules);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => '現在のパスワードが正しくありません。']);
        }
        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => '現在と異なるパスワードを設定してください。']);
        }

        DB::transaction(function () use ($user, $data, $isCastSetup) {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'must_change_password' => false,
            ])->saveQuietly();

            if ($isCastSetup && $user->castProfile) {
                $cast = $user->castProfile;
                $cast->update(['display_name' => $data['display_name'], 'kana' => $data['kana'] ?? null]);

                if (! empty($data['target_amount'])) {
                    CastGoal::updateOrCreate(
                        ['cast_id' => $cast->id, 'period' => CastGoal::currentPeriod()],
                        ['store_id' => CurrentStore::id(), 'target_amount' => $data['target_amount']]
                    );
                }
            }

            AuditLogger::record('auth.setup', $user, $isCastSetup ? '初回セットアップ完了' : '本人がパスワードを変更', storeId: $user->currentStoreId(), userId: $user->id);
        });

        return redirect()->route('home')->with('status', $isCastSetup ? 'セットアップが完了しました。ようこそ！' : 'パスワードを変更しました。');
    }
}
