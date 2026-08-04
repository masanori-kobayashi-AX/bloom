<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ロールマスタ。cast(キャスト)/staff(担当黒服)/manager(責任者・店長)/admin(システム管理者)。
 * 権限は store ごとの user_store_memberships で付与する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // cast / staff / manager / admin
            $table->string('name');            // 表示名
            $table->unsignedSmallInteger('level')->default(0); // 権限強度（比較用）
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
