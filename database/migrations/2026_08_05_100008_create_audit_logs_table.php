<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 監査ログ（§3-2）。センシティブ情報の閲覧・重要操作・権限変更を記録する。
 * 追記専用（更新しない）ため created_at のみ。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // 操作者（閲覧者）
            $table->string('action');                    // 操作種別（例：view.private_note / account.suspend / role.change）
            $table->string('auditable_type')->nullable();// 対象モデル
            $table->unsignedBigInteger('auditable_id')->nullable(); // 対象ID
            $table->string('description')->nullable();
            $table->json('before')->nullable();          // 更新前の値
            $table->json('after')->nullable();           // 更新後の値
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
