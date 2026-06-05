<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function __construct(private AiService $aiService) {}

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

        $result = $this->aiService->analyzeIssue($data['title'], $data['content']);

        Issue::create([
            'user_id'         => Auth::id(),
            'title'           => $data['title'],
            'content'         => $data['content'],
            'status'          => 'registered',
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

        $request->validate(['status' => ['required', 'in:registered,impossible,processing,completed']]);
        $issue->update(['status' => $request->status]);

        return back()->with('success', '상태가 변경되었습니다.');
    }

    public function exportMd(): HttpResponse
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $issues = Issue::with('user')->where('status', 'registered')->orderByDesc('created_at')->get();

        $statusLabel = [
            'registered' => '등록',
            'impossible' => '불가',
            'processing' => '처리 중',
            'completed'  => '적용 완료',
        ];
        $statusOrder = ['registered'];

        $today   = now()->format('Y-m-d');
        $total   = $issues->count();
        $counts  = $issues->groupBy('status')->map->count();

        $md  = "# 요구사항 및 이슈 목록\n\n";
        $md .= "> **생성일**: {$today}  \n";
        $md .= "> **시스템**: 주간업무보고 시스템  \n";
        $md .= "> **용도**: LLM 하네스 엔지니어링용 요구사항 관리 문서  \n\n";
        $md .= "---\n\n";

        // 요약 테이블
        $md .= "## 개요\n\n";
        $md .= "| 상태 | 건수 |\n|------|------|\n";
        foreach ($statusOrder as $s) {
            $md .= "| " . ($statusLabel[$s] ?? $s) . " | " . ($counts[$s] ?? 0) . " |\n";
        }
        $md .= "| **전체** | **{$total}** |\n\n";
        $md .= "---\n\n";

        // 상태별 섹션
        foreach ($statusOrder as $status) {
            $group = $issues->where('status', $status)->values();
            if ($group->isEmpty()) continue;

            $md .= "## " . ($statusLabel[$status] ?? $status) . "\n\n";

            foreach ($group as $issue) {
                $num = $issue->id;
                $md .= "### #{$num} — {$issue->title}\n\n";
                $md .= "| 항목 | 내용 |\n|------|------|\n";
                $md .= "| **작성자** | " . ($issue->user?->name ?? '알 수 없음') . " |\n";
                $md .= "| **상태** | " . ($statusLabel[$issue->status] ?? $issue->status) . " |\n";
                $md .= "| **등록일** | " . $issue->created_at->format('Y-m-d H:i') . " |\n\n";

                $md .= "**요구/이슈 내용:**\n\n";
                foreach (explode("\n", $issue->content) as $line) {
                    $md .= "> " . rtrim($line) . "  \n";
                }
                $md .= "\n";

                if ($issue->claude_response) {
                    $md .= "**AI 검토 결과:**\n\n";
                    foreach (explode("\n", $issue->claude_response) as $line) {
                        $md .= "> " . rtrim($line) . "  \n";
                    }
                    $md .= "\n";
                }

                $md .= "---\n\n";
            }
        }

        $filename = '요구사항_' . $today . '.md';

        return response($md, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename),
        ]);
    }
}
