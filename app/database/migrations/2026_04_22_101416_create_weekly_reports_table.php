<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('week');                          // "2026-W17"
            $table->string('status')->default('draft');      // draft|submitted|reviewed|rejected
            $table->date('curr_start');                      // 이번 주 시작
            $table->date('curr_end');                        // 이번 주 종료
            $table->date('next_start');                      // 다음 주 시작
            $table->date('next_end');                        // 다음 주 종료
            $table->json('curr_work')->nullable();           // 이번 주 결과 [{category,content}]
            $table->json('next_plan')->nullable();           // 다음 주 계획 [{category,content}]
            $table->json('todo_items')->nullable();          // [{content}]
            $table->text('notes')->nullable();               // 특이사항
            $table->text('requests')->nullable();            // 요청사항
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
