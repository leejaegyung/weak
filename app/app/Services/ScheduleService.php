<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    /** 일정 셀 시간대 구분 (표시 순서와 동일) */
    public const TIME_ORDER = ['종일', '오전', '오후'];

    /** 일괄 삭제 전용 — 모든 시간대를 대상으로 함 */
    public const TIME_ALL = '전체';

    /** 상태 라벨 (구버전 포맷 파싱에만 사용) */
    private const STATUS_LABELS = ['외근', '출장', '반차', '휴가'];

    /** 일괄 등록 최대 기간 (일) */
    public const BULK_MAX_DAYS = 92;

    public function __construct(
        private HolidayService $holidayService,
    ) {
    }

    public function upsert(int $userId, string $date, ?string $content): Schedule
    {
        return Schedule::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['content' => $content]
        );
    }

    public function getByUserAndRange(int $userId, string $startDate, string $endDate): array
    {
        return Schedule::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /** 전체 팀 스케줄: [user_id => ['date' => content]] */
    public function getTeamSchedules(string $startDate, string $endDate): array
    {
        return Schedule::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->mapWithKeys(fn($s) => [
                $s->date->format('Y-m-d') => $s->content ?? ''
            ])->toArray())
            ->toArray();
    }

    // ──────────────────────────────────────────────────────────────
    // 기간 일괄 등록 / 삭제
    // ──────────────────────────────────────────────────────────────

    /**
     * 지정 기간의 날짜마다 같은 시간대 슬롯만 갱신(또는 삭제)한다.
     * 다른 시간대에 등록된 일정은 그대로 보존된다.
     *
     * 대상 날짜는 두 방식 중 하나로 지정한다.
     *  - dates: 사용자가 직접 고른 날짜 목록 (주말·공휴일 제외 옵션 미적용)
     *  - start_date ~ end_date: 기간 지정 (주말·공휴일 제외 옵션 적용)
     *
     * @param  array{
     *   dates?:array<string>, start_date?:string, end_date?:string, time:string,
     *   status?:string|null, sites?:array<string>, content?:string|null,
     *   skip_weekend?:bool, skip_holiday?:bool, delete?:bool
     * } $input
     * @return array{schedules:array<string,string>, saved:int, skipped_weekend:int, skipped_holiday:int}
     */
    public function bulkUpsert(int $userId, array $input): array
    {
        $time     = $input['time'];
        $isDelete = (bool) ($input['delete'] ?? false);

        // 등록할 슬롯 한 줄 — 삭제 모드는 빈 문자열
        $newLine = $isDelete
            ? ''
            : $this->buildSlotLine(
                $time,
                (string) ($input['status'] ?? ''),
                $input['sites'] ?? [],
                (string) ($input['content'] ?? ''),
            );

        $skippedWeekend = 0;
        $skippedHoliday = 0;
        $picked         = array_filter($input['dates'] ?? []);

        if ($picked) {
            // 직접 고른 날짜 — 사용자의 선택을 그대로 존중한다
            $targets = array_values(array_unique($picked));
            sort($targets);
        } else {
            $start = Carbon::parse($input['start_date'])->startOfDay();
            $end   = Carbon::parse($input['end_date'])->startOfDay();
            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }

            $skipWeekend = (bool) ($input['skip_weekend'] ?? true);
            $skipHoliday = (bool) ($input['skip_holiday'] ?? true);

            $holidays = $skipHoliday
                ? $this->holidayService->between($start->format('Y-m-d'), $end->format('Y-m-d'))
                : [];

            $targets = [];
            for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
                $date = $day->format('Y-m-d');
                if ($skipWeekend && $day->isWeekend()) {
                    $skippedWeekend++;
                    continue;
                }
                if ($skipHoliday && isset($holidays[$date])) {
                    $skippedHoliday++;
                    continue;
                }
                $targets[] = $date;
            }
        }

        if (! $targets) {
            return ['schedules' => [], 'saved' => 0, 'skipped_weekend' => $skippedWeekend, 'skipped_holiday' => $skippedHoliday];
        }

        $existing = Schedule::where('user_id', $userId)
            ->whereIn('date', $targets)
            ->get()
            ->mapWithKeys(fn($s) => [$s->date->format('Y-m-d') => $s->content ?? ''])
            ->toArray();

        $schedules = [];

        DB::transaction(function () use ($targets, $existing, $userId, $time, $newLine, &$schedules) {
            foreach ($targets as $date) {
                // '전체' 시간대는 삭제 모드에서만 사용되며 셀을 통째로 비운다
                $merged = $time === self::TIME_ALL
                    ? ''
                    : $this->mergeSlot($existing[$date] ?? '', $time, $newLine);

                $this->upsert($userId, $date, $merged !== '' ? $merged : null);
                $schedules[$date] = $merged;
            }
        });

        return [
            'schedules'       => $schedules,
            'saved'           => count($targets),
            'skipped_weekend' => $skippedWeekend,
            'skipped_holiday' => $skippedHoliday,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // 일정 셀 포맷 파싱 / 조립
    // 신규 포맷: [시간대]상태:사이트1,사이트2|내용  (여러 줄 = 여러 시간대)
    // 구버전 포맷: 상태:사이트\n상세  → 종일 슬롯으로 변환
    // ※ resources/js/Pages/Schedule/Index.vue 의 parsedCell 과 동일한 규칙
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{slots:array<int,array{time:string,status:string,sites:array<string>,content:string}>, content:string}
     */
    public function parseCell(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return ['slots' => [], 'content' => ''];
        }

        $lines        = preg_split('/\R/u', $text) ?: [];
        $slots        = [];
        $contentLines = [];

        // 신규 포맷 여부 확인 ([...] 패턴)
        $hasNewFmt = false;
        foreach ($lines as $line) {
            if (preg_match('/^\[[^\]]+\]/u', trim($line))) {
                $hasNewFmt = true;
                break;
            }
        }

        if ($hasNewFmt) {
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                if (! preg_match('/^\[([^\]]+)\](.*)$/u', $trimmed, $m)) {
                    $contentLines[] = $trimmed;
                    continue;
                }

                $rest        = $m[2];
                $mainPart    = $rest;
                $slotContent = '';

                // | 뒤는 슬롯 내용
                $pipeIdx = mb_strpos($rest, '|');
                if ($pipeIdx !== false) {
                    $mainPart    = mb_substr($rest, 0, $pipeIdx);
                    $slotContent = trim(mb_substr($rest, $pipeIdx + 1));
                }

                $status = '';
                $sites  = [];
                $colon  = mb_strpos($mainPart, ':');
                if ($colon !== false) {
                    $status = trim(mb_substr($mainPart, 0, $colon));
                    $sites  = $this->splitList(mb_substr($mainPart, $colon + 1));
                } else {
                    $status = trim($mainPart);
                }

                $slots[] = ['time' => $m[1], 'status' => $status, 'sites' => $sites, 'content' => $slotContent];
            }

            return ['slots' => $slots, 'content' => trim(implode(' ', $contentLines))];
        }

        // ── 구버전 파싱 → 종일 슬롯으로 변환 ──
        $header       = trim($lines[0] ?? '');
        $legacyStatus = '';
        $legacySites  = [];
        $detailStart  = 1;
        $colon        = mb_strpos($header, ':');

        if ($colon === 0) {
            $legacySites = $this->splitList(mb_substr($header, 1));
        } elseif ($colon !== false) {
            $before = $this->splitList(mb_substr($header, 0, $colon));
            if ($this->allStatusLabels($before)) {
                $legacyStatus = $before[0] ?? '';
                $legacySites  = $this->splitList(mb_substr($header, $colon + 1));
            } else {
                $detailStart = 0;
            }
        } else {
            $parts = $this->splitList($header);
            if ($parts && $this->allStatusLabels($parts)) {
                $legacyStatus = $parts[0];
            } elseif (in_array($header, self::STATUS_LABELS, true)) {
                $legacyStatus = $header;
            } else {
                $detailStart = 0;
            }
        }

        if ($legacyStatus !== '' || $legacySites) {
            $slots[] = ['time' => '종일', 'status' => $legacyStatus, 'sites' => $legacySites, 'content' => ''];
        }
        for ($i = $detailStart; $i < count($lines); $i++) {
            $t = trim($lines[$i]);
            if ($t !== '') {
                $contentLines[] = $t;
            }
        }

        return ['slots' => $slots, 'content' => trim(implode(' ', $contentLines))];
    }

    /** 슬롯 한 줄 조립 — 내용이 모두 비면 빈 문자열 */
    public function buildSlotLine(string $time, string $status, array $sites, string $content): string
    {
        $status  = trim($status);
        $sites   = $this->splitList(implode(',', $sites));
        $content = trim(preg_replace('/\R/u', ' ', $content) ?? '');

        if ($status === '' && ! $sites && $content === '') {
            return '';
        }

        $line = '[' . $time . ']' . $status;
        if ($sites) {
            $line .= ':' . implode(',', $sites);
        }
        if ($content !== '') {
            $line .= '|' . $content;
        }

        return $line;
    }

    /**
     * 지정 시간대 슬롯만 교체하고 나머지 시간대는 그대로 보존한다.
     * $newLine 이 빈 문자열이면 해당 시간대 슬롯을 삭제한다.
     */
    public function mergeSlot(?string $raw, string $time, string $newLine): string
    {
        $parsed = $this->parseCell($raw);

        $lines = [];
        foreach ($parsed['slots'] as $slot) {
            if ($slot['time'] === $time) {
                continue;
            }
            $line = $this->buildSlotLine($slot['time'], $slot['status'], $slot['sites'], $slot['content']);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        if ($newLine !== '') {
            $lines[] = $newLine;
        }

        usort($lines, fn($a, $b) => $this->slotLineOrder($a) <=> $this->slotLineOrder($b));

        // 구버전 자유 텍스트는 유실되지 않도록 뒤에 보존
        if ($parsed['content'] !== '') {
            $lines[] = $parsed['content'];
        }

        return implode("\n", $lines);
    }

    /** 슬롯 줄의 시간대 정렬 순서 (종일 → 오전 → 오후) */
    private function slotLineOrder(string $line): int
    {
        preg_match('/^\[([^\]]+)\]/u', $line, $m);
        $idx = array_search($m[1] ?? '', self::TIME_ORDER, true);

        return $idx === false ? count(self::TIME_ORDER) : $idx;
    }

    /** 쉼표 구분 문자열 → 공백 제거된 값 배열 */
    private function splitList(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            fn($v) => $v !== ''
        ));
    }

    /** 값이 모두 상태 라벨인지 (빈 배열이면 true — JS every() 와 동일) */
    private function allStatusLabels(array $values): bool
    {
        foreach ($values as $v) {
            if (! in_array($v, self::STATUS_LABELS, true)) {
                return false;
            }
        }

        return true;
    }
}
