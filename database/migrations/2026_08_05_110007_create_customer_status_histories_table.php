<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 顧客ステータスの変更履歴（§5-3「変更履歴を残す」）。追記専用。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('relationship_id')->constrained('cast_customer_relationships')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'relationship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_status_histories');
    }
};
