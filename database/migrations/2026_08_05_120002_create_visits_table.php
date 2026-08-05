<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 来店（来店中・来店履歴／§5-6・§5-7）。status=present が「来店中」。
 * 金額は任意だが将来の顧客価値分析に備えた構造にする。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('visit_plan_id')->nullable()->constrained('visit_plans')->nullOnDelete();
            $table->foreignId('primary_cast_id')->nullable()->constrained('casts')->nullOnDelete(); // 主対応・指名キャスト

            $table->timestamp('arrived_at');
            $table->timestamp('left_at')->nullable();
            $table->string('status')->default('present');      // VisitStatus: present / left

            $table->string('nomination_type')->nullable();     // NominationType: free/zainai/honshimei
            $table->boolean('dohan')->default(false);          // 同伴
            $table->boolean('is_zainai')->default(false);      // 場内指名の有無
            $table->boolean('is_honshimei')->default(false);   // 本指名の有無

            $table->string('seat')->nullable();                // 席
            $table->unsignedInteger('amount')->nullable();     // 利用金額（任意）
            $table->text('arrival_note')->nullable();          // 来店時メモ
            $table->text('after_note')->nullable();            // 接客後メモ（退店後の振り返り）
            $table->string('caution')->nullable();             // 当日の注意
            $table->string('after_status')->nullable();        // アフター見込み（AfterStatus・キャスト申告）
            $table->string('after_status_note')->nullable();   // アフターの補足

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'customer_id']);
            $table->index(['store_id', 'arrived_at']);
            $table->index(['store_id', 'primary_cast_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
