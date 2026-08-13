<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 이메일은 선택 입력 항목이다.
     * 회원가입·카카오 간편가입에서 이메일 없이 가입하면 NOT NULL 제약에 걸려
     * 500 에러가 나므로 nullable 로 변경한다.
     * (unique 인덱스는 유지 — PostgreSQL 은 NULL 중복을 허용한다)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // 되돌리기 전에 NULL 인 이메일을 아이디 기반 임시값으로 채운다
        DB::table('users')
            ->whereNull('email')
            ->update(['email' => DB::raw("COALESCE(username, 'user' || id) || '@company.local'")]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
