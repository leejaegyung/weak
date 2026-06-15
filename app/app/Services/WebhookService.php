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

        // status → [['name' => '김선호', 'time' => '오전'], ...]
        $statusGroups = [];
        // site → [['name' => '김선호', 'time' => '종일'], ...]
        $siteGroups   = [];

        foreach ($schedules as $sched) {
            $name    = $sched->user->name ?? '?';
            $parsed  = $this->parseContent($sched->content ?? '');
            $seen    = []; // 같은 (status, name, time) 중복 방지

            foreach ($parsed['entries'] as $entry) {
                $time   = $entry['time']   ?? '종일';
                $status = $entry['status'] ?? '';
                $sites  = $entry['sites']  ?? [];

                // 상태 미설정 항목은 외근으로 표시
                $effectiveStatus = ($status !== '' && in_array($status, self::STATUS_LABELS))
                    ? $status
                    : '외근';

                $statusKey = "{$effectiveStatus}|{$name}|{$time}";
                if (!isset($seen[$statusKey])) {
                    $seen[$statusKey] = true;
                    $statusGroups[$effectiveStatus][] = ['name' => $name, 'time' => $time];
                }

                foreach ($sites as $site) {
                    $siteKey = "{$site}|{$name}";
                    if (!isset($seen[$siteKey])) {
                        $seen[$siteKey] = true;
                        $siteGroups[$site][] = ['name' => $name, 'time' => $time];
                    }
                }
            }
        }

        if (empty($statusGroups) && empty($siteGroups)) return null;

        $carbon = Carbon::parse($date)->locale('ko');
        $dayKr  = ['일','월','화','수','목','금','토'][$carbon->dayOfWeek];
        $lines  = ["📅 **{$carbon->format('Y년 m월 d일')}({$dayKr}) 팀 일정**"];

        $statusIcons = ['외근' => '🏢', '출장' => '✈️', '반차' => '🕐', '휴가' => '🌴'];

        foreach ($statusGroups as $status => $people) {
            $icon      = $statusIcons[$status] ?? '•';
            $peopleStr = implode(', ', array_map(
                fn($p) => $p['time'] !== '종일' ? "{$p['name']}({$p['time']})" : $p['name'],
                $people
            ));
            $lines[] = "{$icon} {$status}: {$peopleStr}";
        }

        foreach ($siteGroups as $site => $people) {
            $peopleStr = implode(', ', array_map(
                fn($p) => $p['time'] !== '종일' ? "{$p['name']}({$p['time']})" : $p['name'],
                $people
            ));
            $lines[] = "🌐 {$site}: {$peopleStr}";
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

    /**
     * 일정 내용 파싱 — 신형식 [시간대]상태:사이트|메모 및 구형식 모두 지원
     * 반환: entries[] = [{time, status, sites[]}]
     */
    private function parseContent(string $content): array
    {
        $raw = trim($content);
        if (!$raw) return ['entries' => []];

        $lines   = array_filter(array_map('trim', explode("\n", $raw)));
        $entries = [];

        foreach ($lines as $line) {
            if (!$line) continue;

            // ── 신형식: [시간대]상태:사이트1,사이트2|메모 ──
            if (preg_match('/^\[([^\]]+)\](.*)$/', $line, $m)) {
                $time = $m[1];
                $rest = $m[2];

                // | 뒤는 슬롯 자유 입력(메모) — 목적지로 입력하는 경우가 많아 사이트로 함께 집계
                $memo    = '';
                $pipePos = strpos($rest, '|');
                if ($pipePos !== false) {
                    $memo = trim(substr($rest, $pipePos + 1));
                    $rest = substr($rest, 0, $pipePos);
                }
                $rest = trim($rest);

                $status = '';
                $sites  = [];

                $colonPos = strpos($rest, ':');
                if ($colonPos !== false) {
                    $status  = trim(substr($rest, 0, $colonPos));
                    $siteStr = trim(substr($rest, $colonPos + 1));
                    if ($siteStr) {
                        $sites = array_values(array_filter(array_map('trim', explode(',', $siteStr))));
                    }
                } else {
                    $status = $rest;
                }

                // 등록 사이트(콜론)뿐 아니라 자유 입력 메모(파이프)도 목적지로 표시
                if ($memo !== '') {
                    $sites[] = $memo;
                }

                $entries[] = ['time' => $time, 'status' => $status, 'sites' => $sites];
                continue;
            }

            // ── 구형식: ":사이트들" ──
            if (str_starts_with($line, ':')) {
                $sites = array_values(array_filter(array_map('trim', explode(',', substr($line, 1)))));
                if ($sites) {
                    $entries[] = ['time' => '종일', 'status' => '', 'sites' => $sites];
                }
                continue;
            }

            // ── 구형식: "상태:사이트들" or "상태1,상태2:사이트들" ──
            $colonPos = strpos($line, ':');
            if ($colonPos !== false) {
                $before   = trim(substr($line, 0, $colonPos));
                $after    = trim(substr($line, $colonPos + 1));
                $potStats = array_values(array_filter(array_map('trim', explode(',', $before))));
                if ($potStats && !array_diff($potStats, self::STATUS_LABELS)) {
                    $sites = array_values(array_filter(array_map('trim', explode(',', $after))));
                    foreach ($potStats as $s) {
                        $entries[] = ['time' => '종일', 'status' => $s, 'sites' => $sites];
                    }
                    continue;
                }
            }

            // ── 구형식: "상태1,상태2" (사이트 없음) ──
            $parts = array_values(array_filter(array_map('trim', explode(',', $line))));
            if ($parts && !array_diff($parts, self::STATUS_LABELS)) {
                foreach ($parts as $s) {
                    $entries[] = ['time' => '종일', 'status' => $s, 'sites' => []];
                }
            }
        }

        return ['entries' => $entries];
    }
}
