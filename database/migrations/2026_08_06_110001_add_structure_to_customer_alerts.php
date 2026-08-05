<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 重大注意を構造化（誹謗・噂の保管庫化を防ぐ）：情報源・発生日・対応方針・失効日。
 * 失効日を過ぎたものは現役表示から外す（無期限保存しない・データは保持）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_alerts', function (Blueprint $table) {
            $table->string('source')->nullable()->after('subjective');      // 情報源（本人/会計/防犯カメラ 等）
            $table->date('occurred_on')->nullable()->after('source');       // 発生日
            $table->string('action_plan')->nullable()->after('occurred_on'); // 対応方針
            $table->date('expires_on')->nullable()->after('action_plan');   // 失効日（再確認期限）
        });
    }

    public function down(): void
    {
        Schema::table('customer_alerts', function (Blueprint $table) {
            $table->dropColumn(['source', 'occurred_on', 'action_plan', 'expires_on']);
        });
    }
};
