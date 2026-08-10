<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 로그인 직후 이동할 시작 화면을 사용자별로 지정할 수 있게 한다.
 * 값이 없으면 팀 일정판(User::DEFAULT_PAGE_FALLBACK)으로 이동한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_page', 50)->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_page');
        });
    }
};
