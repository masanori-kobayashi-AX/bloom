<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャストのコンディション・相談（§5-12 / §5-5-C）。
 * 公開先(audience)を本人が明確に選ぶ。人事評価には流用しない（運用ルールで担保）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cast_support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->string('audience');                   // SupportAudience: staff / manager（公開先）
            $table->string('condition')->nullable();      // CastCondition（任意）
            $table->text('body')->nullable();             // 自由記述（任意）
            $table->string('status')->default('open');    // open / acknowledged / resolved
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'audience', 'status']);
            $table->index(['store_id', 'cast_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_support_requests');
    }
};
