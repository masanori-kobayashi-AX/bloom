<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ユーザーの店舗所属とロール・在籍状態。
 * 「アカウント停止(退店)」は status=suspended で表現し、データは削除しない（§5-1）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_store_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspended_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'store_id']);
            $table->index(['store_id', 'role_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_store_memberships');
    }
};
