<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Services\ClaudeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function __construct(private ClaudeService $claudeService) {}

    public function index(): Response
    {
        $issues = Issue::with('user')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($i) => [
                'id'              => $i->id,
                'user_id'         => $i->user_id,
                'user_name'       => $i->user?->name ?? '알 수 없음',
                'title'           => $i->title,
                'content'         => $i->content,
                'status'          => $i->status,
                'claude_response' => $i->claude_response,
                'created_at'      => $i->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Issue/Index', [
            'issues' => $issues,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $result = $this->claudeService->analyzeIssue($data['title'], $data['content']);

        Issue::create([
            'user_id'         => Auth::id(),
            'title'           => $data['title'],
            'content'         => $data['content'],
            'status'          => $result['status'],
            'claude_response' => $result['response'],
        ]);

        return back()->with('success', '이슈/요구가 등록되었습니다.');
    }

    public function destroy(Issue $issue): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $issue->user_id !== $user->id) abort(403);

        $issue->delete();
        return back()->with('success', '삭제되었습니다.');
    }

    public function updateStatus(Request $request, Issue $issue): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $request->validate(['status' => ['required', 'in:pending,processing,resolved,unclear']]);
        $issue->update(['status' => $request->status]);

        return back()->with('success', '상태가 변경되었습니다.');
    }
}
