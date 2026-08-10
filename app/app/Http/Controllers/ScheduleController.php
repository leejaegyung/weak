<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkStoreScheduleRequest;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\HolidayService;
use App\Services\ScheduleService;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService,
        private HolidayService $holidayService,
        private WebhookService $webhookService,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $now  = Carbon::now();

        // 주차 네비게이션: ?week=2026-04-20 형태
        $weekParam = $request->query('week');
        $monday    = $weekParam
            ? Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY)
            : $now->copy()->startOfWeek(Carbon::MONDAY);

        $currFriday  = $monday->copy()->addDays(4);
        $nextMonday  = $monday->copy()->addDays(7);
        $nextFriday  = $monday->copy()->addDays(11);

        $startDate = $monday->format('Y-m-d');
        $endDate   = $nextFriday->format('Y-m-d');

        // 전체 사용자 목록 (관리자가 지정한 순서 → 이름 순, 숨김 제외)
        $users = User::where('is_active', true)->where('is_hidden', false)->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn($u) => [
                'id'               => $u->id,
                'name'             => $u->name,
                'position'         => $u->position,
                'avatar_color'     => $u->avatar_color,
                'avatar_image_url' => $u->avatar_image_url,
            ])
            ->toArray();

        // 팀 전체 스케줄 [user_id => [date => content]]
        $teamSchedules = $this->scheduleService->getTeamSchedules($startDate, $endDate);

        // 주차 날짜 레이블 생성
        $currDates = collect(range(0, 4))->map(fn($i) => $monday->copy()->addDays($i)->format('Y-m-d'))->toArray();
        $nextDates = collect(range(0, 4))->map(fn($i) => $nextMonday->copy()->addDays($i)->format('Y-m-d'))->toArray();

        // 해당 주차 사용자별 보고서 ID 맵 {userId: reportId}
        $weekReportMap = WeeklyReport::whereBetween('curr_start', [$startDate, $endDate])
            ->pluck('id', 'user_id')
            ->toArray();

        $mySites = $user->sites->pluck('name')->toArray();

        // 공휴일(대체공휴일 포함) 맵 — 표시 주차 범위
        $holidays = $this->holidayService->between($startDate, $endDate);

        return Inertia::render('Schedule/Index', [
            'users'          => $users,
            'teamSchedules'  => $teamSchedules,
            'currDates'      => $currDates,
            'nextDates'      => $nextDates,
            'holidays'       => $holidays,
            'weekStart'      => $startDate,
            'currentUserId'  => $user->id,
            'isAdmin'        => $user->isAdmin(),
            'prevWeek'       => $monday->copy()->subDays(7)->format('Y-m-d'),
            'nextWeek'       => $monday->copy()->addDays(7)->format('Y-m-d'),
            'isCurrentWeek'  => $monday->isSameDay($now->copy()->startOfWeek(Carbon::MONDAY)),
            'weekReportMap'  => $weekReportMap,
            'mySites'        => $mySites,
            'notifyEnabled'  => $this->webhookService->isEnabled(),
        ]);
    }

    /** 월별 팀 일정 조회 (월간 달력용) */
    public function monthly(Request $request): JsonResponse
    {
        $request->validate(['month' => ['required', 'regex:/^\d{4}-\d{2}$/']]);

        $month     = Carbon::parse($request->query('month') . '-01');
        $startDate = $month->copy()->startOfMonth()->format('Y-m-d');
        $endDate   = $month->copy()->endOfMonth()->format('Y-m-d');

        $users = User::where('is_active', true)
            ->where('is_hidden', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'               => $u->id,
                'name'             => $u->name,
                'position'         => $u->position,
                'avatar_color'     => $u->avatar_color,
                'avatar_image_url' => $u->avatar_image_url,
            ])
            ->toArray();

        $teamSchedules = $this->scheduleService->getTeamSchedules($startDate, $endDate);

        // 공휴일(대체공휴일 포함) 맵 — 해당 월 범위
        $holidays = $this->holidayService->between($startDate, $endDate);

        return response()->json([
            'users'         => $users,
            'teamSchedules' => $teamSchedules,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'holidays'      => $holidays,
        ]);
    }

    /** 공휴일(대체공휴일 포함) 맵 조회 — 일괄 등록 달력용 */
    public function holidays(Request $request): JsonResponse
    {
        $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end'   => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json(
            $this->holidayService->between($request->query('start'), $request->query('end'))
        );
    }

    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'date'    => ['required', 'date'],
            'content' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $authUser = Auth::user();
        $targetId = $request->input('user_id');

        // admin은 타인 셀도 저장 가능, 일반 유저는 본인만
        if ($targetId && $targetId !== $authUser->id && !$authUser->isAdmin()) {
            abort(403);
        }

        $userId = ($targetId && $authUser->isAdmin()) ? $targetId : $authUser->id;

        $schedule = $this->scheduleService->upsert(
            $userId,
            $request->input('date'),
            $request->input('content')
        );

        return response()->json($schedule);
    }

    /**
     * 당일 본인 일정을 팀 채널에 알림 (수동).
     * 아침 정기 발송 이후 급하게 잡힌 일정을 바로 공유하기 위한 용도라 당일만 허용한다.
     */
    public function notify(Request $request): JsonResponse
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        $date  = $request->input('date');
        $today = Carbon::now('Asia/Seoul')->toDateString();

        if ($date !== $today) {
            return response()->json(['ok' => false, 'message' => '당일 일정만 알릴 수 있습니다.'], 422);
        }

        if (!$this->webhookService->isEnabled()) {
            return response()->json(['ok' => false, 'message' => '팀 알림이 설정되어 있지 않습니다.'], 422);
        }

        $sent = $this->webhookService->notifyUserSchedule(Auth::user(), $date);

        return response()->json([
            'ok'      => $sent,
            'message' => $sent ? '팀에 알렸습니다.' : '알림 전송에 실패했습니다.',
        ], $sent ? 200 : 502);
    }

    /** 기간 일괄 등록/삭제 (본인 일정 한정) */
    public function bulkUpsert(BulkStoreScheduleRequest $request): JsonResponse
    {
        $result = $this->scheduleService->bulkUpsert(Auth::id(), $request->validated());

        return response()->json($result);
    }
}
