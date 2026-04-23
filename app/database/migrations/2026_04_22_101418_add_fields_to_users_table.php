<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('name');
            $table->enum('role', ['admin', 'user'])->default('user')->after('position');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('google_id')->nullable()->after('is_active');
            $table->string('username')->nullable()->unique()->after('id');
            $table->timestamp('last_login_at')->nullable()->after('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['position', 'role', 'is_active', 'google_id', 'username']);
        });
    }
};
