<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('issues')->whereIn('status', ['pending', 'unclear'])->update(['status' => 'registered']);
        DB::table('issues')->where('status', 'resolved')->update(['status' => 'completed']);

        Schema::table('issues', function (Blueprint $table) {
            $table->string('status', 20)->default('registered')->change();
        });
    }

    public function down(): void
    {
        DB::table('issues')->where('status', 'registered')->update(['status' => 'pending']);
        DB::table('issues')->where('status', 'impossible')->update(['status' => 'unclear']);
        DB::table('issues')->where('status', 'completed')->update(['status' => 'resolved']);

        Schema::table('issues', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
