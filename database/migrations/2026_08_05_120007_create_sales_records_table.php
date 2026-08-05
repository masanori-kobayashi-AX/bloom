<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 売上記録（§5-6）。将来の顧客価値分析・キャスト別売上集計の土台。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->nullable()->constrained('casts')->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('category')->nullable();
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'recorded_at']);
            $table->index(['store_id', 'customer_id']);
            $table->index(['store_id', 'cast_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_records');
    }
};
