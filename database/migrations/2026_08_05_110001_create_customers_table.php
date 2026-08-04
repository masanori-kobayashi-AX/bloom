<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店舗共通の顧客本体（§7-3）。同一人物が複数キャストと関係を持ちうるため、
 * 「顧客本体」と「キャストとの関係(cast_customer_relationships)」を分離する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('customer_code')->nullable();   // 店舗側の顧客識別番号（名寄せ用）
            $table->string('kana')->nullable();            // 読み方
            $table->string('age_range')->nullable();       // 年代
            $table->string('occupation')->nullable();      // 職業
            $table->string('area')->nullable();            // 居住エリア
            $table->date('birthday')->nullable();          // 誕生日
            $table->text('note')->nullable();              // 店舗共通メモ（本体レベル）
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'kana']);
            $table->index(['store_id', 'customer_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
