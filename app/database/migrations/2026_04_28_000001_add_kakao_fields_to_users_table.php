<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kakao_id')->nullable()->unique()->after('google_id');
            $table->text('kakao_access_token')->nullable()->after('kakao_id');
            $table->text('kakao_refresh_token')->nullable()->after('kakao_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kakao_id', 'kakao_access_token', 'kakao_refresh_token']);
        });
    }
};
