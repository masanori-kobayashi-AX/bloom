<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * アフター見守りに「予定帰宅時刻」を追加。
 * これを過ぎても帰宅連絡がなければ『未連絡（予定超過）』として最上部で警告し、
 * 店舗が対応（電話→緊急連絡先→店長）に動けるようにする。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('after_logs', function (Blueprint $table) {
            $table->timestamp('expected_home_at')->nullable()->after('departed_at');
        });
    }

    public function down(): void
    {
        Schema::table('after_logs', function (Blueprint $table) {
            $table->dropColumn('expected_home_at');
        });
    }
};
