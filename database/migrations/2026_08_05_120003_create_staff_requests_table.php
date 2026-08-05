<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャスト→担当黒服の業務連絡（§5-11）。チャット代替でなく業務連絡に絞る。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff_profiles')->nullOnDelete(); // 担当黒服
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('relationship_id')->nullable()->constrained('cast_customer_relationships')->nullOnDelete();
            $table->string('type');                       // 対応依頼/来店予定共有/ボトル確認/接客注意/相談/フォロー希望/その他
            $table->text('body');
            $table->string('status')->default('pending'); // RequestStatus
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'staff_id', 'status']);
            $table->index(['store_id', 'cast_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_requests');
    }
};
