<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャストごとの顧客との関係（§7-3）。同一顧客に複数キャストの関係がぶら下がる。
 * キャストは自分の relationship しか見えない（他キャストの情報は分離）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cast_customer_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();

            $table->string('customer_name');                 // キャストが普段呼んでいる名前（必須）
            $table->string('line_display_name')->nullable(); // 現在のLINE表示名
            $table->string('status')->default('line_only');  // 関係ステータス（CustomerStatus）
            $table->string('importance')->nullable();        // 顧客区分・重要度（CustomerImportance）

            $table->date('line_exchanged_on')->nullable();   // LINE交換日
            $table->date('first_met_on')->nullable();        // 初回来店日・出会った日
            $table->string('met_context')->nullable();       // 出会った状況
            $table->date('zainai_first_on')->nullable();     // 初回場内指名日
            $table->date('honshimei_first_on')->nullable();  // 本指名化日

            $table->string('favorite_drink')->nullable();    // 好きな飲み物
            $table->string('hobby')->nullable();             // 趣味・関心
            $table->string('usual_weekday')->nullable();     // よく来店する曜日
            $table->string('visit_expectation')->nullable(); // 来店見込み
            $table->text('next_talk')->nullable();           // 次に話したい内容
            $table->string('avatar_emoji')->nullable();      // 識別用アイコン（絵文字）

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cast_id', 'customer_id']);
            $table->index(['store_id', 'cast_id', 'status']);
            $table->index(['store_id', 'cast_id', 'importance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_customer_relationships');
    }
};
