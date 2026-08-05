<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 今日の申し送り（キャストから黒服へ「今日伝えておきたい情報」）。
 * 大元の共有情報(customer_shared_notes)＝持続的 とは別チャンネルで、日付単位の当日情報。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->foreignId('relationship_id')->nullable()->constrained('cast_customer_relationships')->nullOnDelete();
            $table->date('for_date');
            $table->text('body');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'for_date']);
            $table->index(['store_id', 'customer_id', 'for_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_handovers');
    }
};
