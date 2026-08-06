<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Cast\AfterStatusController;
use App\Http\Controllers\Cast\CustomerAlertController;
use App\Http\Controllers\Cast\CustomerBottleController;
use App\Http\Controllers\Cast\CustomerController;
use App\Http\Controllers\Cast\CustomerNoteController;
use App\Http\Controllers\Cast\DailyHandoverController;
use App\Http\Controllers\Cast\GoalController;
use App\Http\Controllers\Cast\NextActionController;
use App\Http\Controllers\Cast\StaffRequestController;
use App\Http\Controllers\Cast\SupportController;
use App\Http\Controllers\Cast\VisitPlanController;
use App\Http\Controllers\AnnouncementFeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\Manager\AnnouncementController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Staff\VisitController;
use App\Http\Controllers\Staff\WorkController;
use Illuminate\Support\Facades\Route;

// ---- 認証（未ログイン） -------------------------------------------------
Route::middleware('guest')->group(function () {
    // 共通ログイン（全役割可）
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    // 役割別ログイン入口（その役割だけ通す）：/login/cast /staff /manager /owner
    Route::get('/login/{role}', [LoginController::class, 'createRole'])
        ->whereIn('role', ['cast', 'staff', 'manager', 'owner'])->name('login.role');
    Route::post('/login/{role}', [LoginController::class, 'storeRole'])
        ->whereIn('role', ['cast', 'staff', 'manager', 'owner'])->name('login.role.store');

    // メール2段階認証（コード入力）
    Route::get('/login-verify', [LoginController::class, 'showTwoFactor'])->name('login.2fa');
    Route::post('/login-verify', [LoginController::class, 'verifyTwoFactor'])->name('login.2fa.verify');
    Route::post('/login-verify/resend', [LoginController::class, 'resendTwoFactor'])->name('login.2fa.resend');

    // パスワード忘れ（メール再設定）
    Route::get('/password/forgot', [\App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/password/forgot', [\App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/password/reset/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->name('password.update');
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

        // お知らせ閲覧（全ロール共通・既読管理）
        Route::get('/announcements', [AnnouncementFeedController::class, 'index'])->name('announcements.index');
        Route::post('/announcements/{announcement}/read', [AnnouncementFeedController::class, 'read'])->name('announcements.read');

        // 受信箱（黒服・責任者）：業務連絡＋相談への対応
        Route::middleware('role:staff,manager,admin')->group(function () {
            Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
            Route::post('/inbox/requests/{staffRequest}/status', [InboxController::class, 'updateRequest'])->name('inbox.requests.status');
            Route::post('/inbox/support/{support}/acknowledge', [InboxController::class, 'acknowledgeSupport'])->name('inbox.support.ack');
            Route::post('/inbox/support/{support}/resolve', [InboxController::class, 'resolveSupport'])->name('inbox.support.resolve');
            Route::post('/inbox/support/{support}/reply', [InboxController::class, 'replySupport'])->name('inbox.support.reply');
            Route::post('/inbox/requests/{staffRequest}/reply', [InboxController::class, 'replyRequest'])->name('inbox.requests.reply');
        });

        // お知らせ配信管理（責任者・管理者）
        Route::middleware('role:manager,admin')->prefix('manager')->name('manager.')->group(function () {
            Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
            Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
            Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
            Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        });

        // キャスト顧客管理（キャスト本人＝全操作／オーナーは閲覧のみ。書込はコントローラで本人限定）
        Route::middleware('role:cast,admin')->group(function () {
            Route::get('/actions', [NextActionController::class, 'index'])->name('cast.actions.index');
            Route::post('/actions/{action}/complete', [NextActionController::class, 'complete'])->name('cast.actions.complete');

            Route::prefix('customers')->name('cast.customers.')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                Route::get('/create', [CustomerController::class, 'create'])->name('create');
                Route::post('/', [CustomerController::class, 'store'])->name('store');
                Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
                Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
                Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
                Route::post('/{customer}/status', [CustomerController::class, 'updateStatus'])->name('status');
                Route::post('/{customer}/close-consult', [CustomerController::class, 'closeConsult'])->name('close-consult');
                // やり取り（電話・LINE）記録／来店（今きた）
                Route::post('/{customer}/contact', [\App\Http\Controllers\Cast\ContactController::class, 'store'])->name('contact');
                Route::post('/{customer}/arrived', [\App\Http\Controllers\Cast\ContactController::class, 'arrived'])->name('arrived');
                Route::post('/{customer}/notes', [CustomerNoteController::class, 'storePrivate'])->name('notes.private');
                Route::post('/{customer}/shared-notes', [CustomerNoteController::class, 'storeShared'])->name('notes.shared');
                Route::post('/{customer}/alerts', [CustomerAlertController::class, 'store'])->name('alerts.store');
                Route::post('/{customer}/actions', [NextActionController::class, 'store'])->name('actions.store');
                // Phase 3：来店予定・今日の申し送り・ボトル・アフター見込み
                Route::post('/{customer}/plans', [VisitPlanController::class, 'store'])->name('plans.store');
                Route::post('/{customer}/handovers', [DailyHandoverController::class, 'store'])->name('handovers.store');
                Route::post('/{customer}/bottles', [CustomerBottleController::class, 'store'])->name('bottles.store');
                Route::post('/{customer}/after', [AfterStatusController::class, 'update'])->name('after.update');
                // Phase 5：担当黒服への業務連絡
                Route::post('/{customer}/staff-request', [StaffRequestController::class, 'store'])->name('staff-request.store');
            });
            Route::post('/bottles/{bottle}/empty', [CustomerBottleController::class, 'markEmpty'])->name('cast.bottles.empty');
            Route::post('/alerts/{alert}/resolve', [CustomerAlertController::class, 'resolve'])->name('cast.alerts.resolve');

            // Phase 5：コンディション・相談（公開先を本人が選ぶ）
            Route::get('/support', [SupportController::class, 'index'])->name('cast.support.index');
            Route::post('/support', [SupportController::class, 'store'])->name('cast.support.store');

            // 目標管理：本人が月次目標を設定・変更
            Route::get('/goal', [GoalController::class, 'edit'])->name('cast.goal.edit');
            Route::put('/goal', [GoalController::class, 'update'])->name('cast.goal.update');
        });

        // 黒服・店長の通常業務ページ（来店運用）。過去の顧客整理とは用途が違うので分離。
        Route::middleware('role:staff,manager,admin')->prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [WorkController::class, 'index'])->name('work.index');
            Route::get('/plans', [WorkController::class, 'plans'])->name('plans');
            Route::get('/search', [WorkController::class, 'search'])->name('search');
            Route::get('/casts', [WorkController::class, 'casts'])->name('casts');
            Route::get('/after', [WorkController::class, 'after'])->name('after');
            // アフター見守り（キャストの安全：どの店へ・何時から・帰宅連絡）
            Route::post('/after', [\App\Http\Controllers\Staff\AfterController::class, 'store'])->name('after.store');
            Route::post('/after/{afterLog}/home', [\App\Http\Controllers\Staff\AfterController::class, 'home'])->name('after.home');
            Route::post('/after/{afterLog}/reopen', [\App\Http\Controllers\Staff\AfterController::class, 'reopen'])->name('after.reopen');
            Route::delete('/after/{afterLog}', [\App\Http\Controllers\Staff\AfterController::class, 'destroy'])->name('after.destroy');
            Route::post('/visits/start', [VisitController::class, 'start'])->name('visits.start');
            Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
            Route::post('/visits/{visit}', [VisitController::class, 'update'])->name('visits.update');
            Route::post('/visits/{visit}/leave', [VisitController::class, 'leave'])->name('visits.leave');
            Route::post('/visits/{visit}/cancel', [VisitController::class, 'cancel'])->name('visits.cancel');
            Route::post('/visits/{visit}/seat', [VisitController::class, 'updateSeat'])->name('visits.seat');
            Route::post('/visits/{visit}/after', [VisitController::class, 'updateAfter'])->name('visits.after');
            Route::post('/visits/{visit}/casts', [VisitController::class, 'addCast'])->name('visits.casts.add');
            Route::delete('/visits/{visit}/casts/{visitCast}', [VisitController::class, 'removeCast'])->name('visits.casts.remove');
            Route::post('/plans/{plan}/confirm', [VisitController::class, 'confirmPlan'])->name('plans.confirm');
        });

        // 集計ダッシュボード・顧客一覧（責任者・管理者のみ）
        Route::middleware('role:manager,admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/admin/customers', [\App\Http\Controllers\Manager\CustomerOverviewController::class, 'index'])->name('admin.customers');
            Route::post('/admin/customers/{relationship}/cell', [\App\Http\Controllers\Manager\CustomerOverviewController::class, 'updateCell'])->name('admin.customers.cell');
            // 店長・オーナー用の顧客詳細（閲覧のみ。私だけのメモはオーナーのみ）
            Route::get('/admin/customer/{customer}', [CustomerController::class, 'show'])->name('admin.customer.show');
            // 席の管理・席割り
            Route::get('/admin/seats', [\App\Http\Controllers\Manager\SeatController::class, 'index'])->name('admin.seats.index');
            Route::post('/admin/seats', [\App\Http\Controllers\Manager\SeatController::class, 'store'])->name('admin.seats.store');
            Route::delete('/admin/seats/{seat}', [\App\Http\Controllers\Manager\SeatController::class, 'destroy'])->name('admin.seats.destroy');
        });

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
