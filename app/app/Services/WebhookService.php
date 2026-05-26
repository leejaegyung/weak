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

    /** 당일 팀 일정 Webhook 발송 (스케줄러에서 호출) */
    public function sendDailySchedule(string $date): bool
    {
        if (!$this->isEnabled()) return false;

        $schedules = Schedule::with('user')
            ->where('date', $date)
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->get()
            ->filter(fn($s) => $s->user && $s->user->is_active && !$s->user->is_hidden)
            ->sortBy(fn($s) => $s->user->sort_order ?? 9999)
            ->values();

        if ($schedules->isEmpty()) return false;

        // 그룹화: 항목 → [사람, 사람]
        $statusGroups = [];
        $siteGroups   = [];
        $otherGroups  = [];

        foreach ($schedules as $sched) {
            $name    = $sched->user->name ?? '?';
            $parsed  = $this->parseContent($sched->content ?? '');

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

        // 항목이 하나도 없으면 발송 안 함
        if (empty($statusGroups) && empty($siteGroups) && empty($otherGroups)) return false;

        // 날짜 표시
        $carbon = Carbon::parse($date)->locale('ko');
        $dayKr  = ['일','월','화','수','목','금','토'][$carbon->dayOfWeek];
        $header = "📅 **{$carbon->format('Y년 m월 d일')}({$dayKr}) 팀 일정**\n";

        $lines = [$header];

        // 상태 (외근/출장/반차/휴가)
        if (!empty($statusGroups)) {
            foreach ($statusGroups as $status => $people) {
                $statusIcon = ['외근' => '🏢', '출장' => '✈️', '반차' => '🕐', '휴가' => '🌴'][$status] ?? '•';
                $lines[] = "{$statusIcon} {$status}: " . implode(', ', $people);
            }
        }

        // 현장/사이트
        if (!empty($siteGroups)) {
            foreach ($siteGroups as $site => $people) {
                $lines[] = "🌐 {$site}: " . implode(', ', $people);
            }
        }

        // 기타 일정
        if (!empty($otherGroups)) {
            foreach ($otherGroups as $item => $people) {
                $lines[] = "✏ {$item}: " . implode(', ', $people);
            }
        }

        return $this->send(implode("\n", $lines));
    }

    /** 일정 내용 파싱 — "상태1,상태2:사이트1,사이트2\n기타" 형식 역파싱 */
    private function parseContent(string $content): array
    {
        $raw = trim($content);
        if (!$raw) return ['statuses' => [], 'sites' => [], 'detail' => ''];

        $nlIdx      = strpos($raw, "\n");
        $headerLine = $nlIdx === false ? $raw : substr($raw, 0, $nlIdx);
        $detail     = $nlIdx === false ? '' : trim(substr($raw, $nlIdx + 1));

        $colonIdx = strpos($headerLine, ':');

        // ":사이트들" — 사이트만
        if ($colonIdx === 0) {
            $sites = array_values(array_filter(array_map('trim', explode(',', substr($headerLine, 1)))));
            return ['statuses' => [], 'sites' => $sites, 'detail' => $detail];
        }

        // "상태들:사이트들"
        if ($colonIdx !== false) {
            $before   = trim(substr($headerLine, 0, $colonIdx));
            $after    = trim(substr($headerLine, $colonIdx + 1));
            $potStats = array_values(array_filter(array_map('trim', explode(',', $before))));
            if ($potStats && !array_diff($potStats, self::STATUS_LABELS)) {
                $sites = $after ? array_values(array_filter(array_map('trim', explode(',', $after)))) : [];
                return ['statuses' => $potStats, 'sites' => $sites, 'detail' => $detail];
            }
        }

        // 상태만 콤마 구분
        $parts = array_values(array_filter(array_map('trim', explode(',', $headerLine))));
        if ($parts && !array_diff($parts, self::STATUS_LABELS)) {
            return ['statuses' => $parts, 'sites' => [], 'detail' => $detail];
        }

        return ['statuses' => [], 'sites' => [], 'detail' => $raw];
    }
}
