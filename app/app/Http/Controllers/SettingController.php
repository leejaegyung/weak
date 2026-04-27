<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private WebhookService $webhookService) {}

    /** Webhook 설정 페이지 */
    public function webhook(): Response
    {
        return Inertia::render('Admin/Webhook', [
            'webhook_url'     => Setting::get('webhook_url', ''),
            'webhook_enabled' => Setting::get('webhook_enabled', '0') === '1',
        ]);
    }

    /** Webhook 설정 저장 */
    public function updateWebhook(Request $request): RedirectResponse
    {
        $request->validate([
            'webhook_url'     => ['nullable', 'url'],
            'webhook_enabled' => ['boolean'],
        ]);

        Setting::set('webhook_url', $request->input('webhook_url', ''));
        Setting::set('webhook_enabled', $request->boolean('webhook_enabled') ? '1' : '0');

        return back()->with('success', 'Webhook 설정이 저장되었습니다.');
    }

    /** Webhook 테스트 전송 */
    public function testWebhook(Request $request): JsonResponse
    {
        $request->validate(['webhook_url' => ['required', 'url']]);

        // 임시로 URL을 직접 사용해 테스트 메시지 발송
        $response = \Illuminate\Support\Facades\Http::timeout(5)
            ->post($request->webhook_url, ['text' => '✅ 주간업무보고 시스템 Webhook 연결 테스트입니다.']);

        return response()->json(['ok' => $response->successful()]);
    }

    /** 미제출자 Webhook 알림 발송 (관리자 수동 트리거) */
    public function sendNotSubmittedAlert(Request $request): JsonResponse
    {
        $request->validate(['week_start' => ['required', 'date']]);

        $weekStart = $request->input('week_start');
        $weekEnd   = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');

        $submittedUserIds = WeeklyReport::whereBetween('curr_start', [$weekStart, $weekEnd])
            ->pluck('user_id');

        $notSubmittedUsers = User::where('is_active', true)
            ->whereNotIn('id', $submittedUserIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($notSubmittedUsers->isEmpty()) {
            return response()->json(['ok' => true, 'message' => '미제출자가 없습니다.']);
        }

        $monday     = Carbon::parse($weekStart);
        $month      = $monday->month;
        $weekOfMon  = (int) ceil($monday->day / 7);
        $weekLabel  = "{$month}월 {$weekOfMon}주차";

        $this->webhookService->notifyNotSubmitted($notSubmittedUsers->pluck('name')->toArray(), $weekLabel);

        return response()->json([
            'ok'      => true,
            'message' => count($notSubmittedUsers) . '명에게 미제출 알림을 발송했습니다.',
        ]);
    }
}
