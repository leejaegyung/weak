<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\WeeklyReport;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(Request $request): Response
    {
        $user   = Auth::user();
        $userId = $user->isAdmin() ? null : $user->id;
        $status = $request->query('status');
        $search = $request->query('search');

        // 주차 네비게이션
        $now       = Carbon::now();
        $weekParam = $request->query('week');
        $monday    = $weekParam
            ? Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY)
            : $now->copy()->startOfWeek(Carbon::MONDAY);

        $weekStart = $monday->format('Y-m-d');
        $weekEnd   = $monday->copy()->addDays(4)->format('Y-m-d');

        // "4월 3주차" 라벨
        $month       = $monday->month;
        $weekOfMonth = (int) ceil($monday->day / 7);
        $weekLabel   = $month . '월 ' . $weekOfMonth . '주차';

        $reports = $this->reportService->list($userId, $status, $search, $weekStart);
        $counts  = $this->reportService->statusCounts($userId, $weekStart);

        return Inertia::render('Report/Index', [
            'reports'       => $reports,
            'counts'        => $counts,
            'isAdmin'       => $user->isAdmin(),
            'filters'       => ['status' => $status, 'search' => $search, 'week' => $weekStart],
            'weekStart'     => $weekStart,
            'weekEnd'       => $weekEnd,
            'weekLabel'     => $weekLabel,
            'prevWeek'      => $monday->copy()->subDays(7)->format('Y-m-d'),
            'nextWeek'      => $monday->copy()->addDays(7)->format('Y-m-d'),
            'isCurrentWeek' => $monday->isSameDay($now->copy()->startOfWeek(Carbon::MONDAY)),
        ]);
    }

    public function create(): Response
    {
        $user     = Auth::user();
        $weekInfo = $this->getCurrentWeek();

        // 이번 주 보고서 이미 있으면 알림용으로 전달
        $existing = $this->reportService->findByWeekAndUser($weekInfo['week'], $user->id);

        $oneMonthAgo = Carbon::now()->subWeeks(4)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $prevReports = WeeklyReport::where('user_id', $user->id)
            ->where('curr_start', '>=', $oneMonthAgo)
            ->orderBy('week', 'desc')
            ->get()
            ->map(function ($r) {
                $monday          = $r->curr_start;
                $month           = $monday->month;
                $weekOfMonth = (int) ceil($monday->day / 7);
                $label       = $month . '월 ' . $weekOfMonth . '주차';

                return [
                    'id'         => $r->id,
                    'week'       => $r->week,
                    'label'      => $label,
                    'curr_start' => $monday->format('Y-m-d'),
                    'curr_end'   => $r->curr_end ? $r->curr_end->format('Y-m-d') : $monday->copy()->addDays(4)->format('Y-m-d'),
                ];
            });

        return Inertia::render('Report/Create', [
            'weekInfo'       => $weekInfo,
            'prevReports'    => $prevReports,
            'existingReport' => $existing ? ['id' => $existing->id, 'week' => $existing->week] : null,
        ]);
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $data   = $request->validated();
        $userId = Auth::id();

        $existing = $this->reportService->findByWeekAndUser($data['week'], $userId);
        if ($existing) {
            return redirect()->route('reports.show', $existing->id)
                ->with('info', '이번 주 보고서가 이미 존재합니다.');
        }

        $report = $this->reportService->create($data, $userId);

        return redirect()->route('reports.show', $report->id)
            ->with('success', '보고서가 저장되었습니다.');
    }

    public function show(WeeklyReport $report): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) abort(403);

        $report->load('user');

        return Inertia::render('Report/Show', ['report' => $report]);
    }

    public function edit(WeeklyReport $report): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) abort(403);

        $report->load('user');

        return Inertia::render('Report/Edit', ['report' => $report]);
    }

    public function update(UpdateReportRequest $request, WeeklyReport $report): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) abort(403);

        $this->reportService->update($report, $request->validated());

        return redirect()->route('reports.show', $report->id)
            ->with('success', '보고서가 수정되었습니다.');
    }

    public function destroy(WeeklyReport $report): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) abort(403);

        $this->reportService->delete($report);

        return redirect()->route('reports.index')->with('success', '보고서가 삭제되었습니다.');
    }

    /** 보고서 제출 */
    public function submit(WeeklyReport $report): RedirectResponse
    {
        $user = Auth::user();
        if ($report->user_id !== $user->id) abort(403);
        if (!in_array($report->status, ['draft', 'rejected'])) {
            return back()->withErrors(['status' => '이미 제출된 보고서입니다.']);
        }

        $report->update(['status' => 'submitted', 'submitted_at' => now()]);

        return back()->with('success', '보고서가 제출되었습니다.');
    }

    /** 관리자: 반려 */
    public function reject(WeeklyReport $report): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $report->update(['status' => 'rejected']);

        return back()->with('success', '보고서가 반려되었습니다.');
    }

    /** 인쇄 미리보기 */
    public function printView(WeeklyReport $report): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) abort(403);

        $report->load('user');

        return Inertia::render('Report/Print', ['report' => $report]);
    }

    /** 이전 보고서 내용 불러오기 (JSON) */
    public function loadReport(WeeklyReport $report): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $report->user_id !== $user->id) abort(403);

        return response()->json($report);
    }

    private function getCurrentWeek(): array
    {
        $now      = Carbon::now();
        $monday   = $now->copy()->startOfWeek(Carbon::MONDAY);
        $friday   = $monday->copy()->addDays(4);
        $nextMon  = $monday->copy()->addWeek();
        $nextFri  = $nextMon->copy()->addDays(4);

        $month          = $now->month;
        $weekOfMonth = (int) ceil($monday->day / 7);

        return [
            'week'       => $now->format('Y') . '-W' . $now->format('W'),
            'label'      => $month . '월 ' . $weekOfMonth . '주차',
            'curr_start' => $monday->format('Y-m-d'),
            'curr_end'   => $friday->format('Y-m-d'),
            'next_start' => $nextMon->format('Y-m-d'),
            'next_end'   => $nextFri->format('Y-m-d'),
        ];
    }
}
