<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * 本人によるパスワード変更（初回強制変更・任意変更 共通）。
 */
class PasswordChangeController extends Controller
{
    public function edit()
    {
        return view('auth.password-change', [
            'forced' => (bool) Auth::user()->must_change_password,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => '現在のパスワードが正しくありません。',
            ]);
        }

        if (Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => '現在と異なるパスワードを設定してください。',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ])->saveQuietly();

        AuditLogger::record('auth.password_change', $user, '本人がパスワードを変更', storeId: $user->currentStoreId(), userId: $user->id);

        return redirect()->route('home')->with('status', 'パスワードを変更しました。');
    }
}
