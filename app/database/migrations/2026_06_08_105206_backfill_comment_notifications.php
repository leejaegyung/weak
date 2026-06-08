<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('report_comments')) return;

        $comments = DB::table('report_comments')
            ->join('weekly_reports', 'report_comments.report_id', '=', 'weekly_reports.id')
            ->join('users as commenter', 'report_comments.user_id', '=', 'commenter.id')
            ->select(
                'report_comments.report_id',
                'report_comments.content',
                'report_comments.created_at as commented_at',
                'weekly_reports.user_id as report_owner_id',
                'commenter.id as commenter_id',
                'commenter.name as commenter_name',
                'commenter.role as commenter_role'
            )
            ->get();

        foreach ($comments as $comment) {
            // 관리자가 타인의 보고서에 단 코멘트만 대상
            if ($comment->commenter_role !== 'admin') continue;
            if ($comment->commenter_id === $comment->report_owner_id) continue;

            $link = '/reports/' . $comment->report_id;

            // 동일 보고서에 대한 comment 알림이 이미 있으면 건너뜀
            $exists = DB::table('notifications')
                ->where('user_id', $comment->report_owner_id)
                ->where('type', 'comment')
                ->where('link', $link)
                ->exists();

            if ($exists) continue;

            DB::table('notifications')->insert([
                'user_id'    => $comment->report_owner_id,
                'type'       => 'comment',
                'title'      => $comment->commenter_name . '님이 코멘트를 작성했습니다.',
                'body'       => mb_strimwidth($comment->content, 0, 80, '…'),
                'link'       => $link,
                'read_at'    => null,
                'created_at' => $comment->commented_at,
                'updated_at' => $comment->commented_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('notifications')->where('type', 'comment')->delete();
    }
};
