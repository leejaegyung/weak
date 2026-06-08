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
            ->get(['id', 'name', 'position', 'kakao_id', 'kakao_channel_uuid'])
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'position'     => $u->position,
                'connected'    => !empty($u->getRawOriginal('kakao_id')),
                'channel_uuid' => !empty($u->getRawOriginal('kakao_channel_uuid')),
            ]);

        return Inertia::render('Admin/Kakao', [
            'rest_api_key'            => Setting::get('kakao_rest_api_key', ''),
            'client_secret'           => Setting::get('kakao_client_secret', ''),
            'redirect_uri'            => route('auth.kakao.callback'),
            'users'                   => $users,
            'kakao_daily_enabled'     => Setting::get('kakao_daily_enabled', '0') === '1',
            'kakao_daily_time'        => Setting::get('kakao_daily_time', '09:00'),
            'kakao_channel_public_id' => Setting::get('kakao_channel_public_id', ''),
            'kakao_app_admin_key'     => Setting::get('kakao_app_admin_key', ''),
        ]);
    }

    /** REST API 키 + 클라이언트 시크릿 + 채널 설정 저장 */
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['rest_api_key' => ['required', 'string', 'max:255']]);
        Setting::set('kakao_rest_api_key', trim($request->input('rest_api_key')));
        Setting::set('kakao_client_secret', trim($request->input('client_secret', '')));
        Setting::set('kakao_channel_public_id', trim($request->input('channel_public_id', '')));
        Setting::set('kakao_app_admin_key', trim($request->input('app_admin_key', '')));
        return back()->with('success', '설정이 저장되었습니다.');
    }

    /** 미제출자 카카오 알림 — 팀원별 토큰으로 각자에게 발송 */
    public function sendAlert(Request $request): JsonResponse
    {
        $request->validate(['week_start' => ['required', 'date']]);

        // 입력 날짜가 무슨 요일이든 그 주 월요일로 정규화
        $weekStart = Carbon::parse($request->input('week_start'))->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
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

    /** 오늘 팀 일정 카카오 즉시 발송 (수동 테스트) */
    public function sendDailyNow(): JsonResponse
    {
        $date   = Carbon::now()->timezone('Asia/Seoul')->toDateString();
        $result = $this->kakaoService->sendDailySchedule($date);
        $ok     = $result['sent'] > 0;
        $msg    = $ok
            ? "{$result['sent']}명 발송 완료" . ($result['failed'] > 0 ? ", {$result['failed']}명 실패" : '')
            : "발송 실패 — 오늘({$date}) 일정이 없거나 카카오 연동 팀원이 없습니다";
        return response()->json(['ok' => $ok, 'message' => $msg, 'date' => $date]);
    }

    /** 채널 API 진단 — 실제 카카오 API 응답 반환 */
    public function debugChannel(): JsonResponse
    {
        $channelPublicId = Setting::get('kakao_channel_public_id', '');
        $adminKey        = Setting::get('kakao_app_admin_key', '');

        // 현재 로그인 유저의 raw 토큰
        $me    = auth()->user();
        $token = $me ? \App\Models\User::where('id', $me->id)->value('kakao_access_token') : null;
        $kakaoId = $me ? \App\Models\User::where('id', $me->id)->value('kakao_id') : null;

        $result = [
            'channel_public_id' => $channelPublicId,
            'admin_key_set'     => !empty($adminKey),
            'kakao_id'          => $kakaoId,
            'has_token'         => !empty($token),
        ];

        // 1) 사용자 토큰으로 /v1/api/talk/channels 호출
        if ($token) {
            try {
                $http = \Illuminate\Support\Facades\Http::timeout(10);
                if (app()->environment('local')) $http = $http->withoutVerifying();
                $r = $http->withToken($token)->get('https://kapi.kakao.com/v1/api/talk/channels');
                $result['user_token_test'] = ['status' => $r->status(), 'body' => $r->json()];
            } catch (\Throwable $e) {
                $result['user_token_test'] = ['error' => $e->getMessage()];
            }
        }

        // 2) 어드민 키로 /v1/api/talk/channels 호출 (target_id_type=user_id)
        if ($adminKey && $kakaoId) {
            try {
                $http = \Illuminate\Support\Facades\Http::timeout(10);
                if (app()->environment('local')) $http = $http->withoutVerifying();
                $r = $http->withHeaders(['Authorization' => 'KakaoAK ' . $adminKey])
                    ->get('https://kapi.kakao.com/v1/api/talk/channels', [
                        'target_id_type'    => 'user_id',
                        'target_id'         => $kakaoId,
                        'channel_public_id' => $channelPublicId,
                    ]);
                $result['admin_key_test'] = ['status' => $r->status(), 'body' => $r->json()];
            } catch (\Throwable $e) {
                $result['admin_key_test'] = ['error' => $e->getMessage()];
            }
        }

        // 3) 어드민 키로 채널 팔로워 목록 조회
        if ($adminKey && $channelPublicId) {
            try {
                $http = \Illuminate\Support\Facades\Http::timeout(10);
                if (app()->environment('local')) $http = $http->withoutVerifying();
                $r = $http->withHeaders(['Authorization' => 'KakaoAK ' . $adminKey])
                    ->get('https://kapi.kakao.com/v1/api/talk/channels/followers', [
                        'channel_public_id' => $channelPublicId,
                        'count'             => 10,
                    ]);
                $result['followers_test'] = ['status' => $r->status(), 'body' => $r->json()];
            } catch (\Throwable $e) {
                $result['followers_test'] = ['error' => $e->getMessage()];
            }
        }

        return response()->json($result);
    }

    /** 채널 구독자 UUID 일괄 동기화 (어드민 키 사용 — 팀원 재인증 불필요) */
    public function syncChannelUuids(): JsonResponse
    {
        $result = $this->kakaoService->syncAllChannelUuids();

        if (isset($result['error'])) {
            return response()->json(['ok' => false, 'message' => $result['error']]);
        }

        $msg = "채널 구독 팀원 {$result['synced']}명 동기화 완료";
        if ($result['not_subscribed'] > 0) $msg .= " / 미구독 {$result['not_subscribed']}명";
        if ($result['failed'] > 0)         $msg .= " / 오류 {$result['failed']}명";

        return response()->json(['ok' => true, 'message' => $msg, 'result' => $result]);
    }

    /** 카카오 자동발송 설정 저장 */
    public function updateDailySettings(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
            'time'    => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);
        Setting::set('kakao_daily_enabled', $request->enabled ? '1' : '0');
        Setting::set('kakao_daily_time', $request->time);
        return response()->json(['ok' => true]);
    }
}
