<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users にログインID・アカウント状態・初回PW変更・失敗ロック・監査用カラムを追加。
 * キャストはメール未所持でも運用できるよう login_id を主ログイン識別子にし、email は任意化する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id')->nullable()->unique()->after('name'); // ログインID（英数）
            $table->boolean('must_change_password')->default(true)->after('password'); // 初回・再設定後の変更強制
            $table->boolean('is_active')->default(true)->after('must_change_password'); // アカウント有効/停止
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->unsignedSmallInteger('failed_attempts')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_attempts'); // ログイン失敗による一時ロック
            $table->string('phone')->nullable()->after('locked_until');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
        });

        // email はキャストが未所持でも登録できるよう任意化（unique は維持＝NULL は重複可）
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login_id']);
            $table->dropColumn([
                'login_id', 'must_change_password', 'is_active', 'last_login_at',
                'failed_attempts', 'locked_until', 'phone', 'created_by', 'updated_by', 'deleted_at',
            ]);
        });
    }
};
