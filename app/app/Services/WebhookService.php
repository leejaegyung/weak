<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /** 상태 레이블 목록 */
    private const STATUS_LABELS = ['외근', '출장', '반차', '휴가'];

    /** Webhook 활성화 여부 */
    public function isEnabled(): bool
    {
        return Setting::get('webhook_enabled', '0') === '1'
            && !empty(Setting::get('webhook_url'));
    }

    /** 메시지 전송 (Slack / Mattermost 공통 포맷) */
    public function send(string $text): bool
    {
        if (!$this->isEnabled()) return false;

        $url = Setting::get('webhook_url');

        try {
            $response = Http::timeout(5)->asJson()->withoutVerifying()->post($url, ['text' => $text]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Webhook 전송 실패: ' . $e->getMessage());
            return false;
        }
    }

    /** 보고서 반려 알림 */
    public function notifyRejected(string $userName, string $week, string $reason = ''): void
    {
        $reasonText = $reason ? "\n📝 반려 사유: {$reason}" : '';
        $this->send("⚠️ **보고서 반려 알림**\n{$userName}님의 {$week} 주간보고가 반려되었습니다. 수정 후 재제출해 주세요.{$reasonText}");
    }

    /** 미제출자 일괄 알림 */
    public function notifyNotSubmitted(array $userNames, string $weekLabel): void
    {
        if (empty($userNames)) return;
        $list = implode(', ', $userNames);
        $this->send("📋 **미제출 알림** — {$weekLabel}\n아직 주간보고를 제출하지 않은 팀원: {$list}");
    }

    // ═══════════════════════════════════════════════
    //  매일 아침 팀 일정 자동 발송
    // ═══════════════════════════════════════════════

    /** 당일 팀 일정 메시지 텍스트 생성 (Webhook·카카오 공용) */
    public function buildDailyMessage(string $date): ?string
    {
        $schedules = Schedule::with('user')
            ->where('date', $date)
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->get()
            ->filter(fn($s) => $s->user && $s->user->is_active && !$s->user->is_hidden)
            ->sortBy(fn($s) => $s->user->sort_order ?? 9999)
            ->values();

        if ($schedules->isEmpty()) return null;

        $statusGroups = [];
        $siteGroups   = [];
        $otherGroups  = [];

        foreach ($schedules as $sched) {
            $name   = $sched->user->name ?? '?';
            $parsed = $this->parseContent($sched->content ?? '');

            foreach ($parsed['statuses'] as $status) {
                $statusGroups[$status][] = $name;
            }
            foreach ($parsed['sites'] as $site) {
                $siteGroups[$site][] = $name;
            }
            foreach (array_filter(array_map('trim', explode("\n", $parsed['detail']))) as $item) {
                $otherGroups[$item][] = $name;
            }
        }

        if (empty($statusGroups) && empty($siteGroups) && empty($otherGroups)) return null;

        $carbon = Carbon::parse($date)->locale('ko');
        $dayKr  = ['일','월','화','수','목','금','토'][$carbon->dayOfWeek];
        $lines  = ["📅 **{$carbon->format('Y년 m월 d일')}({$dayKr}) 팀 일정**"];

        foreach ($statusGroups as $status => $people) {
            $icon    = ['외근' => '🏢', '출장' => '✈️', '반차' => '🕐', '휴가' => '🌴'][$status] ?? '•';
            $lines[] = "{$icon} {$status}: " . implode(', ', $people);
        }
        foreach ($siteGroups as $site => $people) {
            $lines[] = "🌐 {$site}: " . implode(', ', $people);
        }
        foreach ($otherGroups as $item => $people) {
            $lines[] = "✏ {$item}: " . implode(', ', $people);
        }

        return implode("\n", $lines);
    }

    /** 당일 팀 일정 Webhook 발송 (스케줄러에서 호출) */
    public function sendDailySchedule(string $date): bool
    {
        if (!$this->isEnabled()) return false;
        $message = $this->buildDailyMessage($date);
        if (!$message) return false;
        return $this->send($message);
    }

    /** 일정 내용 파싱 — 신형식 [시간대]상태:사이트 및 구형식 모두 지원 */
    private function parseContent(string $content): array
    {
        $raw = trim($content);
        if (!$raw) return ['statuses' => [], 'sites' => [], 'detail' => ''];

        $lines    = array_filter(array_map('trim', explode("\n", $raw)));
        $statuses = [];
        $sites    = [];
        $details  = [];

        foreach ($lines as $line) {
            if (!$line) continue;

            // ── 신형식: [종일]외근:KBS 원주  /  [오전]휴가  /  [오후]외근:MBC,KBS ──
            if (preg_match('/^\[([^\]]+)\](.*)$/', $line, $m)) {
                $rest     = trim($m[2]);             // "외근:KBS 원주" or "휴가"
                $colonPos = strpos($rest, ':');

                if ($colonPos !== false) {
                    $status  = trim(substr($rest, 0, $colonPos));
                    $siteStr = trim(substr($rest, $colonPos + 1));
                } else {
                    $status  = $rest;
                    $siteStr = '';
                }

                if ($status) {
                    if (in_array($status, self::STATUS_LABELS)) {
                        $statuses[] = $status;
                    } else {
                        $details[] = $status; // 상태가 아닌 텍스트
                    }
                }
                if ($siteStr) {
                    foreach (array_filter(array_map('trim', explode(',', $siteStr))) as $s) {
                        $sites[] = $s;
                    }
                }
                continue;
            }

            // ── 구형식: ":사이트들" ──
            if (str_starts_with($line, ':')) {
                foreach (array_filter(array_map('trim', explode(',', substr($line, 1)))) as $s) {
                    $sites[] = $s;
                }
                continue;
            }

            // ── 구형식: "상태:사이트들" or "상태1,상태2:사이트들" ──
            $colonPos = strpos($line, ':');
            if ($colonPos !== false) {
                $before   = trim(substr($line, 0, $colonPos));
                $after    = trim(substr($line, $colonPos + 1));
                $potStats = array_filter(array_map('trim', explode(',', $before)));
                if ($potStats && !array_diff(array_values($potStats), self::STATUS_LABELS)) {
                    foreach ($potStats as $s) $statuses[] = $s;
                    foreach (array_filter(array_map('trim', explode(',', $after))) as $s) $sites[] = $s;
                    continue;
                }
            }

            // ── 구형식: "상태1,상태2" (사이트 없음) ──
            $parts = array_filter(array_map('trim', explode(',', $line)));
            if ($parts && !array_diff(array_values($parts), self::STATUS_LABELS)) {
                foreach ($parts as $s) $statuses[] = $s;
                continue;
            }

            // 그 외 기타 텍스트
            $details[] = $line;
        }

        return [
            'statuses' => array_values(array_unique($statuses)),
            'sites'    => array_values(array_unique($sites)),
            'detail'   => implode("\n", $details),
        ];
    }
}
