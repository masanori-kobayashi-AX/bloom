<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * アフター記録。黒服がキャストの安全を見守るための台帳。
 * 「どのキャストが・どの店へ・何時から行き・帰宅連絡が来たか」を管理する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('after_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();   // 同伴のお客様（任意・任意で紐付け）
            $table->string('companion')->nullable();                 // お客様名などの自由記述
            $table->string('destination')->nullable();               // 行き先の店
            $table->timestamp('departed_at')->nullable();            // 何時から
            $table->timestamp('home_reported_at')->nullable();       // 帰宅連絡が来た時刻
            $table->string('status')->default('out');                // out=外出中(帰宅待ち) / home=帰宅済み
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'departed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_logs');
    }
};
