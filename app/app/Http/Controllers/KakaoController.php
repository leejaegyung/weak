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
        return Inertia::render('Admin/Kakao', [
            'rest_api_key' => Setting::get('kakao_rest_api_key', ''),
            'is_connected' => $this->kakaoService->isConnected(),
            'connected_at' => Setting::get('kakao_connected_at', ''),
            'redirect_uri' => route('admin.settings.kakao.callback'),
        ]);
    }

    /** REST API 키 저장 */
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['rest_api_key' => ['required', 'string', 'max:255']]);
        Setting::set('kakao_rest_api_key', trim($request->input('rest_api_key')));
        return back()->with('success', 'REST API 키가 저장되었습니다.');
    }

    /** 카카오 OAuth 인증 시작 → 카카오 로그인 페이지로 리다이렉트 */
    public function auth(): RedirectResponse
    {
        if (empty(Setting::get('kakao_rest_api_key'))) {
            return back()->with('error', 'REST API 키를 먼저 저장해 주세요.');
        }
        $redirectUri = route('admin.settings.kakao.callback');
        return redirect()->away($this->kakaoService->getAuthUrl($redirectUri));
    }

    /** 카카오 OAuth 콜백 — 인가 코드를 토큰으로 교환 */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('admin.settings.kakao')
                ->with('error', '카카오 인증이 취소되었습니다: ' . $request->input('error_description'));
        }

        $code        = $request->input('code');
        $redirectUri = route('admin.settings.kakao.callback');

        if ($this->kakaoService->exchangeCode($code, $redirectUri)) {
            return redirect()->route('admin.settings.kakao')
                ->with('success', '✅ 카카오 계정 연동이 완료되었습니다! 테스트 메시지를 발송해 보세요.');
        }

        return redirect()->route('admin.settings.kakao')
            ->with('error', '토큰 교환에 실패했습니다. REST API 키와 리다이렉트 URI 설정을 다시 확인해 주세요.');
    }

    /** 테스트 메시지 발송 (나에게 보내기) */
    public function test(): JsonResponse
    {
        $ok = $this->kakaoService->sendMessage(
            "✅ 주간업무보고 시스템 카카오톡 연결 테스트\n정상적으로 연동되었습니다!"
        );
        return response()->json(['ok' => $ok]);
    }

    /** 연동 해제 */
    public function disconnect(): RedirectResponse
    {
        $this->kakaoService->disconnect();
        return back()->with('success', '카카오 연동이 해제되었습니다.');
    }

    /** 미제출자 카카오 알림 수동 발송 */
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
        $names     = $notSubmitted->pluck('name')->implode(', ');

        $text = "📋 미제출 알림 — {$weekLabel}\n아직 주간보고를 제출하지 않은 팀원: {$names}";
        $ok   = $this->kakaoService->sendMessage($text);

        return response()->json([
            'ok'      => $ok,
            'message' => $ok
                ? $notSubmitted->count() . '명의 미제출 알림을 카카오톡으로 발송했습니다.'
                : '발송 실패. 카카오 연동 상태를 확인해 주세요.',
        ]);
    }
}
