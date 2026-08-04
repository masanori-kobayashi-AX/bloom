<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 初回ログイン・パスワード再設定後は、変更を完了するまで他画面へ進ませない（§5-1）。
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change')
            && ! $request->routeIs('password.change.update')
            && ! $request->routeIs('logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
