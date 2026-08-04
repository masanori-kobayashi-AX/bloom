<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 重大注意情報（§5-5-D）。危険行為・暴言・ハラスメント・料金トラブル等。
 * 事実(fact)と主観(subjective)を分けて記録する。分類は AlertCategory の固定リスト。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->nullable()->constrained('casts')->nullOnDelete(); // 記録者キャスト
            $table->string('category');                       // AlertCategory
            $table->text('fact');                             // 事実
            $table->text('subjective')->nullable();           // 主観・所感
            $table->string('severity')->default('mid');       // low / mid / high
            $table->boolean('resolved')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'customer_id', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_alerts');
    }
};
