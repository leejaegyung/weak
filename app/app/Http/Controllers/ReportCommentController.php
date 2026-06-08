<?php

namespace App\Http\Controllers;

use App\Models\ReportComment;
use App\Models\WeeklyReport;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportCommentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(WeeklyReport $report): JsonResponse
    {
        $comments = ReportComment::with('user')
            ->where('report_id', $report->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'user_id'    => $c->user_id,
                'user_name'  => $c->user?->name ?? '알 수 없음',
                'section'    => $c->section,
                'content'    => $c->content,
                'created_at' => $c->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json($comments);
    }

    public function store(Request $request, WeeklyReport $report): JsonResponse
    {
        $user = Auth::user();

        // 관리자 또는 보고서 소유자만 작성 가능
        if (!$user->isAdmin() && $report->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'section' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $comment = ReportComment::create([
            'report_id' => $report->id,
            'user_id'   => $user->id,
            'section'   => $data['section'] ?? 'general',
            'content'   => $data['content'],
        ]);

        $comment->load('user');

        $preview = mb_strimwidth($comment->content, 0, 80, '…');
        $link    = "/reports/{$report->id}";

        if ($user->isAdmin()) {
            // 관리자 코멘트 → 보고서 작성자에게 알림 (본인 제외)
            if ($report->user_id !== $user->id) {
                $this->notifications->create(
                    $report->user_id,
                    'comment',
                    "{$user->name}님이 코멘트를 작성했습니다.",
                    $preview,
                    $link
                );
            }
        } else {
            // 보고서 소유자 답변 → 해당 보고서에 코멘트를 단 관리자들에게 알림
            $adminIds = ReportComment::where('report_id', $report->id)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->unique();

            foreach ($adminIds as $adminId) {
                $this->notifications->create(
                    $adminId,
                    'comment',
                    "{$user->name}님이 답변을 작성했습니다.",
                    $preview,
                    $link
                );
            }
        }

        return response()->json([
            'id'         => $comment->id,
            'user_id'    => $comment->user_id,
            'user_name'  => $comment->user?->name ?? '알 수 없음',
            'section'    => $comment->section,
            'content'    => $comment->content,
            'created_at' => $comment->created_at->format('Y-m-d H:i'),
        ], 201);
    }

    public function update(Request $request, ReportComment $comment): JsonResponse
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $data = $request->validate(['content' => ['required', 'string', 'max:1000']]);
        $comment->update($data);

        return response()->json(['content' => $comment->content]);
    }

    public function destroy(ReportComment $comment): JsonResponse
    {
        if (!Auth::user()->isAdmin()) abort(403);
        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
