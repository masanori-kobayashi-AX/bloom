<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * キャストの月次目標に、本指名件数・同伴件数の目標を追加。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cast_goals', function (Blueprint $table) {
            $table->unsignedInteger('target_honshimei')->default(0)->after('target_amount'); // 本指名件数の目標
            $table->unsignedInteger('target_dohan')->default(0)->after('target_honshimei');   // 同伴件数の目標
        });
    }

    public function down(): void
    {
        Schema::table('cast_goals', function (Blueprint $table) {
            $table->dropColumn(['target_honshimei', 'target_dohan']);
        });
    }
};
