<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(private ScheduleService $scheduleService)
    {
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
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'position' => $u->position])
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

        return Inertia::render('Schedule/Index', [
            'users'          => $users,
            'teamSchedules'  => $teamSchedules,
            'currDates'      => $currDates,
            'nextDates'      => $nextDates,
            'weekStart'      => $startDate,
            'currentUserId'  => $user->id,
            'isAdmin'        => $user->isAdmin(),
            'prevWeek'       => $monday->copy()->subDays(7)->format('Y-m-d'),
            'nextWeek'       => $monday->copy()->addDays(7)->format('Y-m-d'),
            'isCurrentWeek'  => $monday->isSameDay($now->copy()->startOfWeek(Carbon::MONDAY)),
            'weekReportMap'  => $weekReportMap,
            'mySites'        => $mySites,
        ]);
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
}
