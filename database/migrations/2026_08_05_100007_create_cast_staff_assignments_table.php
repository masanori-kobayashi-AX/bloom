<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャストと担当黒服の紐付け。released_at が NULL のものが現在有効な担当。
 * 担当黒服はこの紐付けを通じてのみ、担当キャストの共有情報にアクセスできる。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cast_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'cast_id', 'released_at']);
            $table->index(['store_id', 'staff_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_staff_assignments');
    }
};
