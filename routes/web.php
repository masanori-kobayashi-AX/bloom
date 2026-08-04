<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// ---- 認証（未ログイン） -------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---- 認証済み ------------------------------------------------------------
Route::middleware(['auth', 'active'])->group(function () {
    // パスワード変更（初回強制・任意）— password.changed の前に置く
    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    // 初回PW変更を終えるまで他画面へ進めない
    Route::middleware('password.changed')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        // アカウント管理・紐付け（責任者・管理者のみ）
        Route::middleware('role:manager,admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
            Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
            Route::post('/accounts/{user}/suspend', [AccountController::class, 'suspend'])->name('accounts.suspend');
            Route::post('/accounts/{user}/reactivate', [AccountController::class, 'reactivate'])->name('accounts.reactivate');
            Route::post('/accounts/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');

            Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
            Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
            Route::post('/assignments/{assignment}/release', [AssignmentController::class, 'release'])->name('assignments.release');
        });
    });
});
