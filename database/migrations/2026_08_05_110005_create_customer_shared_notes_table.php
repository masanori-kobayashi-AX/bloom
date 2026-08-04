<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店舗共有事項（担当黒服共有・§5-5-B）。担当黒服・店長は閲覧可。他キャストには非表示。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_shared_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('relationship_id')->constrained('cast_customer_relationships')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete(); // 依頼元キャスト
            $table->string('category')->nullable(); // 対応依頼/ボトル/席希望/接客注意/苦手/準備/会計
            $table->text('body');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'relationship_id']);
            $table->index(['store_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_shared_notes');
    }
};
