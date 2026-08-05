<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャストの月次目標（本人設定・変更可）。進捗＝当月の来店金額合計/目標。
 * 支援用の指標であり、人事・報酬評価には使わない（労務リスク回避）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cast_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->string('period', 7);              // YYYY-MM
            $table->unsignedBigInteger('target_amount')->default(0);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['cast_id', 'period']);
            $table->index(['store_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_goals');
    }
};
