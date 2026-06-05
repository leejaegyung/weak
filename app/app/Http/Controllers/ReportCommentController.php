<?php

namespace App\Http\Controllers;

use App\Models\ReportComment;
use App\Models\WeeklyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportCommentController extends Controller
{
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
        if (!Auth::user()->isAdmin()) abort(403);

        $data = $request->validate([
            'section' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $comment = ReportComment::create([
            'report_id' => $report->id,
            'user_id'   => Auth::id(),
            'section'   => $data['section'] ?? 'general',
            'content'   => $data['content'],
        ]);

        $comment->load('user');

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
