<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャストの相談・業務連絡に、黒服／店長からの返信を持たせる。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cast_support_requests', function (Blueprint $table) {
            $table->text('reply')->nullable()->after('body');
            $table->timestamp('replied_at')->nullable()->after('reply');
            $table->unsignedBigInteger('replied_by')->nullable()->after('replied_at');
        });
        Schema::table('staff_requests', function (Blueprint $table) {
            $table->text('reply')->nullable()->after('body');
            $table->timestamp('replied_at')->nullable()->after('reply');
            $table->unsignedBigInteger('replied_by')->nullable()->after('replied_at');
        });
    }

    public function down(): void
    {
        Schema::table('cast_support_requests', function (Blueprint $table) {
            $table->dropColumn(['reply', 'replied_at', 'replied_by']);
        });
        Schema::table('staff_requests', function (Blueprint $table) {
            $table->dropColumn(['reply', 'replied_at', 'replied_by']);
        });
    }
};
