<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 次のアクション（§5-8）。「誰に連絡するかを忘れない」ための管理。自動送信はしない。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('next_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('relationship_id')->constrained('cast_customer_relationships')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->string('content');                  // アクション内容
            $table->string('kind')->default('other');   // NextActionKind
            $table->date('due_on')->nullable();         // 期限
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'cast_id', 'completed', 'due_on']);
            $table->index(['store_id', 'relationship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('next_actions');
    }
};
