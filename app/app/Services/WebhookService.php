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

    /**
     * 본인 하루 일정을 팀에 공유 (일정판의 '팀에 알리기' 버튼).
     * 아침 정기 발송 이후에 급하게 잡힌 일정을 바로 알리기 위한 수동 알림이다.
     */
    public function notifyUserSchedule(\App\Models\User $user, string $date): bool
    {
        if (!$this->isEnabled()) return false;

        return $this->send($this->buildUserDayMessage($user, $date));
    }

    /** 특정 사용자의 하루 일정 메시지 */
    public function buildUserDayMessage(\App\Models\User $user, string $date): string
    {
        $content = Schedule::where('user_id', $user->id)->where('date', $date)->value('content');

        $carbon = Carbon::parse($date);
        $dayKr  = ['일','월','화','수','목','금','토'][$carbon->dayOfWeek];
        $header = "🔔 **일정 변경** — {$carbon->format('m월 d일')}({$dayKr})";

        $entries = $this->parseContent($content ?? '')['entries'];

        if (empty($entries)) {
            return "{$header}\n- {$user->name} : 일정 없음";
        }

        $statusIcons = ['외근' => '🏢', '출장' => '✈️', '반차' => '🕐', '휴가' => '🌴'];
        $parts       = [];

        foreach ($entries as $entry) {
            $time   = $entry['time']   ?? '종일';
            $status = $entry['status'] ?? '';
            $sites  = $entry['sites']  ?? [];

            $icon   = $statusIcons[$status] ?? '•';
            $prefix = ($time !== '' && $time !== '종일') ? "({$time}) " : '';
            $label  = trim($status . ($sites ? ' ' . implode(', ', $sites) : ''));

            $parts[] = $icon . ' ' . $prefix . ($label !== '' ? $label : '일정');
        }

        return "{$header}\n- {$user->name} : " . implode(' / ', $parts);
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

        // 동일 인물의 여러 슬롯(오전/오후 등)은 한 명으로 병합
        // status → [ name => ['name' => '박성원', 'times' => ['오전','오후']], ... ]
        $statusGroups = [];
        // site   → 인물 기준: [ name => ['name' => '박성원', 'sites' => [['site'=>'MBC 상암','time'=>'오전'], ...]], ... ]
        $siteGroups   = [];

        foreach ($schedules as $sched) {
            $name    = $sched->user->name ?? '?';
            $parsed  = $this->parseContent($sched->content ?? '');

            foreach ($parsed['entries'] as $entry) {
                $time   = $entry['time']   ?? '종일';
                $status = $entry['status'] ?? '';
                $sites  = $entry['sites']  ?? [];

                // 상태 미설정 항목은 외근으로 표시
                $effectiveStatus = ($status !== '' && in_array($status, self::STATUS_LABELS))
                    ? $status
                    : '외근';

                if (!isset($statusGroups[$effectiveStatus][$name])) {
                    $statusGroups[$effectiveStatus][$name] = ['name' => $name, 'times' => []];
                }
                if (!in_array($time, $statusGroups[$effectiveStatus][$name]['times'], true)) {
                    $statusGroups[$effectiveStatus][$name]['times'][] = $time;
                }

                foreach ($sites as $site) {
                    if (!isset($siteGroups[$name])) {
                        $siteGroups[$name] = ['name' => $name, 'sites' => []];
                    }
                    // 동일 (사이트, 시간) 중복 방지
                    $dup = false;
                    foreach ($siteGroups[$name]['sites'] as $sx) {
                        if ($sx['site'] === $site && $sx['time'] === $time) { $dup = true; break; }
                    }
                    if (!$dup) {
                        $siteGroups[$name]['sites'][] = ['site' => $site, 'time' => $time];
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
            $peopleStr = implode(', ', array_map([$this, 'formatPerson'], array_values($people)));
            $lines[] = "{$icon} {$status}: {$peopleStr}";
        }

        $timeOrder = ['종일' => 0, '오전' => 1, '오후' => 2];
        foreach ($siteGroups as $person) {
            $sites = $person['sites'];
            usort($sites, fn($a, $b) => ($timeOrder[$a['time']] ?? 9) <=> ($timeOrder[$b['time']] ?? 9));
            $siteStr = implode(', ', array_map(
                fn($s) => ($s['time'] !== '' && $s['time'] !== '종일') ? "{$s['site']}({$s['time']})" : $s['site'],
                $sites
            ));
            $lines[] = "- {$person['name']} : {$siteStr}";
        }

        return implode("\n", $lines);
    }

    /**
     * 인물 표시 문자열 생성 — 동일 인물의 시간을 묶어 한 번만 표기
     * 예) 박성원(오전, 오후), 종일이거나 시간 구분 없으면 이름만
     */
    private function formatPerson(array $person): string
    {
        $times = array_values(array_filter(
            $person['times'] ?? [],
            fn($t) => $t !== '' && $t !== '종일'
        ));

        // 종일이 포함되어 있거나 시간 구분이 없으면 이름만
        if (in_array('종일', $person['times'] ?? [], true) || empty($times)) {
            return $person['name'];
        }

        // 시간순 정렬 (오전 → 오후)
        $order = ['오전' => 0, '오후' => 1];
        usort($times, fn($a, $b) => ($order[$a] ?? 9) <=> ($order[$b] ?? 9));

        return $person['name'] . '(' . implode(', ', $times) . ')';
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
