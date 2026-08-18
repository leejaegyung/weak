<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('week');                                       // "2026-W34"
            $table->date('week_start');                                   // 해당 주차 월요일
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->nullable();                    // 계정 삭제 대비 스냅샷
            $table->string('to_email');
            $table->json('cc_emails')->nullable();
            $table->string('subject');
            $table->unsignedInteger('report_count')->default(0);          // 메일에 포함된 보고서 수
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_mail_logs');
    }
};
