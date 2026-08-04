<?php

namespace App\Http\Middleware;

use App\Enums\RoleKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ロールベースのアクセス制御（サーバー側認可。画面表示だけに依存しない）。
 * 使い方: ->middleware('role:manager,admin')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = array_map(fn (string $r) => RoleKey::from($r), $roles);

        if (! $user->hasRole(...$allowed)) {
            abort(403, 'この操作を行う権限がありません。');
        }

        return $next($request);
    }
}
