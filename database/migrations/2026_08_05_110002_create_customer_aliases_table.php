<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 顧客のLINE表示名・旧LINE名・別名・呼び名（§7-3）。名寄せ候補と検索の材料。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->nullable()->constrained('casts')->nullOnDelete(); // この別名を知るキャスト
            $table->string('type');   // line_current / line_old / nickname / reading / other
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'value']);
            $table->index(['store_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_aliases');
    }
};
