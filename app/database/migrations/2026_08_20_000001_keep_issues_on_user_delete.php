<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 퇴사자 계정을 삭제해도 등록한 요구/이슈는 남기도록 한다.
 * (주간보고의 keep_weekly_reports_on_user_delete 와 동일한 방식)
 *
 * - issues.user_id : ON DELETE CASCADE → ON DELETE SET NULL
 * - author_name / author_position : 작성자 스냅샷 (계정이 사라져도 이름을 표시하기 위함)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->string('author_name', 100)->nullable()->after('user_id');
            $table->string('author_position', 100)->nullable()->after('author_name');
        });

        // 기존 이슈에 현재 작성자 정보를 스냅샷으로 채워 넣는다
        DB::statement('
            UPDATE issues
               SET author_name = users.name, author_position = users.position
              FROM users
             WHERE users.id = issues.user_id
        ');

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // 작성자 계정이 없는 이슈는 되돌릴 수 없으므로 제거한다
        DB::table('issues')->whereNull('user_id')->delete();

        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn(['author_name', 'author_position']);
        });
    }
};
