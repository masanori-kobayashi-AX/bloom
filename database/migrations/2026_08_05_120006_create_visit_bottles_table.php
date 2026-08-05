<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 来店時に出した・入れたボトル。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('visit_bottles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('customer_bottle_id')->nullable()->constrained('customer_bottles')->nullOnDelete();
            $table->string('name');
            $table->string('action')->nullable(); // keep(新規キープ) / open(開栓) / order(注文)
            $table->timestamps();

            $table->index(['store_id', 'visit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_bottles');
    }
};
