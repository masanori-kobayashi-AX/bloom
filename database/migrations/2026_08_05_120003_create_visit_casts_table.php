<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1回の来店に対応したキャスト（複数可）。指名・場内・ヘルプ等を記録。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('visit_casts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->string('role')->default('help'); // nominated / zainai / help
            $table->timestamps();

            $table->unique(['visit_id', 'cast_id']);
            $table->index(['store_id', 'cast_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_casts');
    }
};
