<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\KakaoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KakaoController extends Controller
{
    public function __construct(private KakaoService $kakaoService) {}

    /** 카카오 연동 설정 페이지 */
    public function show(): Response
    {
        $users = User::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'kakao_id'])
            ->map(fn($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'position'  => $u->position,
                'connected' => !empty($u->kakao_id),
            ]);

        return Inertia::render('Admin/Kakao', [
            'rest_api_key'    => Setting::get('kakao_rest_api_key', ''),
            'client_secret'   => Setting::get('kakao_client_secret', ''),
            'redirect_uri'    => route('auth.kakao.callback'),
            'users'           => $users,
        ]);
    }

    /** REST API 키 + 클라이언트 시크릿 저장 */
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['rest_api_key' => ['required', 'string', 'max:255']]);
        Setting::set('kakao_rest_api_key', trim($request->input('rest_api_key')));
        Setting::set('kakao_client_secret', trim($request->input('client_secret', '')));
        return back()->with('success', '설정이 저장되었습니다.');
    }

    /** 미제출자 카카오 알림 — 팀원별 토큰으로 각자에게 발송 */
    public function sendAlert(Request $request): JsonResponse
    {
        $request->validate(['week_start' => ['required', 'date']]);

        $weekStart = $request->input('week_start');
        $weekEnd   = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');

        $submittedIds = WeeklyReport::whereBetween('curr_start', [$weekStart, $weekEnd])
            ->pluck('user_id');

        $notSubmitted = User::where('is_active', true)
            ->whereNotIn('id', $submittedIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($notSubmitted->isEmpty()) {
            return response()->json(['ok' => true, 'message' => '미제출자가 없습니다.']);
        }

        $monday    = Carbon::parse($weekStart);
        $weekLabel = $monday->month . '월 ' . (int) ceil($monday->day / 7) . '주차';
        $appUrl    = config('app.url');

        $successNames = [];
        $failNames    = [];

        foreach ($notSubmitted as $user) {
            $text = "📋 [{$weekLabel}] 주간보고 미제출 알림\n{$user->name}님, 아직 이번 주 보고서를 제출하지 않으셨습니다.\n\n→ 보고서 작성: {$appUrl}/reports/create";
            $ok   = $this->kakaoService->sendToUser($user, $text);
            if ($ok) $successNames[] = $user->name;
            else     $failNames[]    = $user->name;
        }

        $message = '';
        if ($successNames) $message .= count($successNames) . '명 발송 완료(' . implode(', ', $successNames) . ')';
        if ($failNames)    $message .= ($message ? ' / ' : '') . count($failNames) . '명 실패(카카오 미연동 포함): ' . implode(', ', $failNames);

        return response()->json(['ok' => empty($failNames) || !empty($successNames), 'message' => $message]);
    }
}
