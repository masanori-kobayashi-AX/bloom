<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 来店予定（§5-7）。キャスト・黒服が登録し、黒服画面に表示される。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('visit_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete(); // 指名キャスト
            $table->date('planned_date');
            $table->time('planned_time')->nullable();
            $table->unsignedSmallInteger('party_size')->nullable();
            $table->boolean('dohan')->default(false);          // 同伴の有無
            $table->string('bottle_note')->nullable();         // ボトル名
            $table->text('prep')->nullable();                  // 事前準備
            $table->text('note')->nullable();                  // 黒服への依頼・注意点
            $table->string('status')->default('pending');      // VisitPlanStatus
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'planned_date', 'status']);
            $table->index(['store_id', 'cast_id', 'planned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_plans');
    }
};
