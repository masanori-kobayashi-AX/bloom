<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店舗からのお知らせ・営業ヒント（§5-13）。重要度・掲載期限で通知過多を避ける。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category');                  // AnnouncementCategory
            $table->string('title');
            $table->text('body');
            $table->string('importance')->default('normal'); // low / normal / high
            $table->date('expires_on')->nullable();      // 掲載期限
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'published_at']);
            $table->index(['store_id', 'expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
