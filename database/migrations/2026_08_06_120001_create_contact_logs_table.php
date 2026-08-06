<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 顧客とのやり取り履歴（電話・LINE・来店 など）。キャストがワンタップで記録する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('relationship_id')->constrained('cast_customer_relationships')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('channel');                 // ContactChannel: phone/line/visit/other
            $table->string('note')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'relationship_id', 'contacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_logs');
    }
};
