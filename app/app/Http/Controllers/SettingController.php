<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWeeklyMailRequest;
use App\Mail\WeeklyReportMail;
use App\Models\Setting;
use App\Models\WeeklyReport;
use App\Services\KakaoService;
use App\Services\MailService;
use App\Services\ReportService;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        private WebhookService $webhookService,
        private KakaoService   $kakaoService,
        private MailService    $mailService,
        private ReportService  $reportService,
    ) {}

    // ═══════════════════════════════════════════════
    //  Webhook
    // ═══════════════════════════════════════════════

    /** Webhook 설정 페이지 */
    public function webhook(): Response
    {
        return Inertia::render('Admin/Webhook', [
            'webhook_url'           => Setting::get('webhook_url', ''),
            'webhook_enabled'       => Setting::get('webhook_enabled', '0') === '1',
            'webhook_daily_enabled' => Setting::get('webhook_daily_enabled', '0') === '1',
            'webhook_daily_time'    => Setting::get('webhook_daily_time', '09:00'),
        ]);
    }

    /** Webhook 설정 저장 */
    public function updateWebhook(Request $request): RedirectResponse
    {
        $request->validate([
            'webhook_url'           => ['nullable', 'url'],
            'webhook_enabled'       => ['boolean'],
            'webhook_daily_enabled' => ['boolean'],
            'webhook_daily_time'    => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        Setting::set('webhook_url',           $request->input('webhook_url', ''));
        Setting::set('webhook_enabled',       $request->boolean('webhook_enabled') ? '1' : '0');
        Setting::set('webhook_daily_enabled', $request->boolean('webhook_daily_enabled') ? '1' : '0');
        Setting::set('webhook_daily_time',    $request->input('webhook_daily_time', '09:00'));

        return back()->with('success', 'Webhook 설정이 저장되었습니다.');
    }

    /** 오늘 팀 일정 수동 즉시 발송 (테스트용) */
    public function sendDailyNow(): JsonResponse
    {
        $date = Carbon::now()->timezone('Asia/Seoul')->toDateString();
        $ok   = app(WebhookService::class)->sendDailySchedule($date);
        return response()->json(['ok' => $ok, 'date' => $date]);
    }

    /** Webhook 테스트 전송 */
    public function testWebhook(Request $request): JsonResponse
    {
        $request->validate(['webhook_url' => ['required', 'url']]);

        $response = \Illuminate\Support\Facades\Http::timeout(5)
            ->asJson()
            ->withoutVerifying()
            ->post($request->webhook_url, ['text' => '✅ 주간업무보고 시스템 Webhook 연결 테스트입니다.']);

        return response()->json(['ok' => $response->successful()]);
    }

    /** 미제출자 알림 발송 (관리자 수동 트리거) — channels: webhook | kakao | both */
    public function sendNotSubmittedAlert(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => ['required', 'date'],
            'channels'   => ['required', 'in:webhook,kakao,both'],
        ]);

        $channels = $request->input('channels', 'both');

        $weekStart = Carbon::parse($request->input('week_start'))->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        // 미제출자 + 작성 중(draft) 인원을 모두 알림 대상으로 산정
        $targets = $this->reportService->notifyTargets($weekStart);

        if ($targets->isEmpty()) {
            return response()->json(['ok' => true, 'message' => '알림 대상자가 없습니다.']);
        }

        $monday    = Carbon::parse($weekStart);
        $month     = $monday->month;
        $weekOfMon = (int) ceil($monday->day / 7);
        $weekLabel = "{$month}월 {$weekOfMon}주차";

        $webhookSent = 0;
        $kakaoSent   = 0;
        $kakaoFailed = 0;

        if (in_array($channels, ['webhook', 'both'])) {
            // 작성 중인 팀원은 이름 뒤에 상태를 표기해 구분
            $names = $targets->map(fn($t) => $t['state'] === 'draft'
                ? "{$t['user']->name}(작성 중)"
                : $t['user']->name
            )->toArray();

            $this->webhookService->notifyNotSubmitted($names, $weekLabel);
            $webhookSent = 1;
        }

        if (in_array($channels, ['kakao', 'both'])) {
            foreach ($targets as $target) {
                $user = $target['user'];
                if (empty($user->kakao_id)) continue;

                $body = $target['state'] === 'draft'
                    ? "{$user->name}님, 작성 중인 주간보고가 아직 제출되지 않았습니다."
                    : "{$user->name}님, 이번 주 주간보고가 아직 제출되지 않았습니다.";

                $text = "📋 [{$weekLabel}] 주간보고 미제출 알림\n\n{$body}\n\n빠른 시간 내에 보고서를 제출해 주세요.";
                $ok   = $this->kakaoService->sendToUser($user, $text);
                $ok ? $kakaoSent++ : $kakaoFailed++;
            }
        }

        $total = $targets->count();
        $parts = [];
        if ($webhookSent) $parts[] = 'Webhook 전송 완료';
        if ($kakaoSent || $kakaoFailed) {
            $parts[] = "카카오 {$kakaoSent}/{$total}명 전송" . ($kakaoFailed ? " ({$kakaoFailed}명 실패 — 카카오 미연동)" : '');
        }

        return response()->json([
            'ok'      => true,
            'message' => implode(' / ', $parts) ?: '알림을 발송했습니다.',
        ]);
    }

    // ═══════════════════════════════════════════════
    //  SMTP 메일 설정
    // ═══════════════════════════════════════════════

    /** SMTP 설정 페이지 */
    public function smtp(): Response
    {
        return Inertia::render('Admin/Smtp', [
            'smtp_host'         => Setting::get('smtp_host', ''),
            'smtp_port'         => Setting::get('smtp_port', '587'),
            'smtp_encryption'   => Setting::get('smtp_encryption', 'tls'),
            'smtp_username'     => Setting::get('smtp_username', ''),
            'smtp_from_address' => Setting::get('smtp_from_address', ''),
            'smtp_from_name'    => Setting::get('smtp_from_name', '주간업무보고 시스템'),
            'mail_to'           => Setting::get('mail_to', ''),
            'mail_cc'           => json_decode(Setting::get('mail_cc', '[]'), true) ?? [],
        ]);
    }

    /** SMTP 설정 저장 */
    public function updateSmtp(Request $request): RedirectResponse
    {
        $request->validate([
            'smtp_host'         => ['nullable', 'string', 'max:255'],
            'smtp_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'smtp_username'     => ['nullable', 'string', 'max:255'],
            'smtp_password'     => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name'    => ['nullable', 'string', 'max:100'],
            'mail_to'           => ['nullable', 'email', 'max:255'],
            'mail_cc'           => ['nullable', 'array'],
            'mail_cc.*'         => ['email', 'max:255'],
        ]);

        Setting::set('smtp_host',         $request->input('smtp_host', ''));
        Setting::set('smtp_port',         (string) $request->input('smtp_port', '587'));
        Setting::set('smtp_encryption',   $request->input('smtp_encryption', 'tls'));
        Setting::set('smtp_username',     $request->input('smtp_username', ''));
        Setting::set('smtp_from_address', $request->input('smtp_from_address', ''));
        Setting::set('smtp_from_name',    $request->input('smtp_from_name', '주간업무보고 시스템'));
        Setting::set('mail_to',           $request->input('mail_to', ''));
        Setting::set('mail_cc',           json_encode(array_filter($request->input('mail_cc', []))));

        // 비밀번호는 입력된 경우에만 업데이트
        if ($request->filled('smtp_password')) {
            Setting::set('smtp_password', $request->input('smtp_password'));
        }

        return back()->with('success', 'SMTP 설정이 저장되었습니다.');
    }

    /** SMTP 연결 테스트 */
    public function testSmtp(Request $request): JsonResponse
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $result = $this->mailService->sendTest($request->input('test_email'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** 메일 기본값 조회 (보고서 목록 페이지 모달용) */
    public function mailDefaults(): JsonResponse
    {
        return response()->json([
            'configured' => $this->mailService->isConfigured(),
            'mail_to'    => Setting::get('mail_to', ''),
            'mail_cc'    => json_decode(Setting::get('mail_cc', '[]'), true) ?? [],
        ]);
    }

    // ═══════════════════════════════════════════════
    //  주간보고 메일 전송
    // ═══════════════════════════════════════════════

    /** 주간보고 메일 전송 (관리자 수동 트리거) */
    public function sendWeeklyMail(SendWeeklyMailRequest $request): JsonResponse
    {
        if (!$this->mailService->isConfigured()) {
            return response()->json([
                'ok'      => false,
                'message' => 'SMTP 설정이 구성되지 않았습니다. 알림 > 메일(SMTP) 설정 페이지에서 먼저 설정해 주세요.',
            ], 422);
        }

        $weekStart = Carbon::parse($request->input('week_start'))->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $weekEnd   = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');
        $week      = $this->reportService->weekString($weekStart);

        // 제출된 보고서 조회 (미제출자가 있어도 제출분만 포함하여 상시 전송 가능)
        $submittedReports = WeeklyReport::with('user')
            ->where('week', $week)
            ->whereNotNull('submitted_at')
            ->get();

        if ($submittedReports->isEmpty()) {
            return response()->json([
                'ok'      => false,
                'message' => '해당 주차의 제출된 보고서가 없습니다.',
            ], 422);
        }

        // 제출자 목록 구성 (팀 일정판과 동일한 정렬: sort_order → name)
        $baseUrl = rtrim(config('app.url'), '/');
        $reports = $submittedReports
            ->sort(fn($a, $b) =>
                [$a->user->sort_order ?? 9999, $a->user->name ?? '']
                <=> [$b->user->sort_order ?? 9999, $b->user->name ?? '']
            )
            ->values()
            ->map(fn($r) => [
                'name'     => $r->user->name     ?? '-',
                'position' => $r->user->position ?? '',
            ])->toArray();

        $data = [
            'to'          => $request->input('to'),
            'cc'          => array_filter($request->input('cc', [])),
            'subject'     => $request->input('subject'),
            'body_intro'  => trim((string) $request->input('body_intro', '')) ?: "안녕하세요.\n이번 주 팀원 주간업무보고를 전달드립니다.",
            'body_main'   => trim((string) $request->input('body_main', '')),
            'body_outro'  => trim((string) $request->input('body_outro', '')) ?: '감사합니다.',
            'week'        => $week,
            'week_start'  => $weekStart,
            'week_end'    => $weekEnd,
            // 메일에는 개별 보고서 링크 대신 해당 주차 목록 링크 하나만 넣는다
            'list_url'    => "{$baseUrl}/reports?week={$weekStart}",
            'reports'     => $reports,
        ];

        try {
            $log = $this->mailService->sendWeeklyReport($data);

            return response()->json([
                'ok'       => true,
                'message'  => '메일을 성공적으로 전송했습니다.',
                'sent_at'  => $log->sent_at->format('Y-m-d H:i'),
                'mail_log' => $this->mailService->weekLog($week),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => '메일 전송 실패: ' . $e->getMessage(),
            ], 500);
        }
    }
}
