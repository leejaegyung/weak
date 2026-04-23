<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        // 기존 사용자에게 이름 순으로 sort_order 초기값 설정
        $users = DB::table('users')->orderBy('name')->pluck('id');
        foreach ($users as $i => $id) {
            DB::table('users')->where('id', $id)->update(['sort_order' => $i]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
