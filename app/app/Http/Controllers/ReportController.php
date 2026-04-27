<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\ScheduleService;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService       $reportService,
        private NotificationService $notificationService,
        private WebhookService      $webhookService,
        private ScheduleService     $scheduleService,
    ) {}

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

        // 현재 사용자의 금주·차주 일정 사전 로드
        $mySchedulesRaw = $this->scheduleService->getByUserAndRange(
            $user->id,
            $weekInfo['curr_start'],
            $weekInfo['next_end']
        );
        $mySchedules = collect($mySchedulesRaw)
            ->mapWithKeys(fn($s) => [
                (is_array($s) ? substr($s['date'], 0, 10) : substr((string)$s->date, 0, 10))
                => is_array($s) ? ($s['content'] ?? '') : ($s->content ?? '')
            ])->toArray();

        return Inertia::render('Report/Create', [
            'weekInfo'       => $weekInfo,
            'prevReports'    => $prevReports,
            'existingReport' => $existing ? ['id' => $existing->id, 'week' => $existing->week] : null,
            'mySchedules'    => $mySchedules,
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

        // 같은 주차 팀원 보고서 맵 (사용자 스위처용)
        $weekReports = WeeklyReport::where('week', $report->week)->pluck('id', 'user_id');
        $teamUsers   = User::where('is_active', true)->where('is_hidden', false)
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'report_id' => $weekReports->get($u->id),
            ])->values()->toArray();

        return Inertia::render('Report/Show', [
            'report'    => $report,
            'teamUsers' => $teamUsers,
        ]);
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

        $report->load('user');
        $report->update(['status' => 'rejected']);

        // 인앱 알림 생성 (보고서 작성자에게)
        $this->notificationService->create(
            $report->user_id,
            'rejected',
            '보고서가 반려되었습니다',
            $report->week . ' 주간보고가 관리자에 의해 반려되었습니다. 수정 후 재제출해 주세요.',
            "/reports/{$report->id}"
        );

        // Webhook 발송
        $this->webhookService->notifyRejected($report->user->name ?? '알 수 없음', $report->week);

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
