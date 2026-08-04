<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * ロールに応じたトップ画面の振り分け（各ロールの本格ダッシュボードは Phase 2 以降）。
 */
class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('home', [
            'user' => $user,
            'role' => $user->role(),
        ]);
    }
}
