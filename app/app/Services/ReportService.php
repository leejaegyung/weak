<?php

namespace App\Services;

use App\Models\User;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportService
{
    /**
     * weekStart(Monday 날짜 문자열)로부터 ISO 주차 문자열(e.g. "2026-W20")을 계산.
     * curr_start 날짜 범위 대신 week 컬럼으로 조회해야 날짜 오차 없이 정확히 조회됨.
     */
    private function weekString(string $weekStart): string
    {
        $monday = Carbon::parse($weekStart);
        return $monday->format('Y') . '-W' . $monday->format('W');
    }

    public function list(?int $userId = null, ?string $status = null, ?string $search = null, ?string $weekStart = null): array
    {
        // 미제출 필터: 해당 주차 보고서가 없는 활성 사용자 목록 반환
        if ($status === 'not_submitted') {
            if (!$weekStart) return [];
            $weekStr         = $this->weekString($weekStart);
            $existingUserIds = WeeklyReport::where('week', $weekStr)->pluck('user_id');

            $usersQuery = User::where('is_active', true)->where('is_hidden', false)->whereNotIn('id', $existingUserIds);
            if ($userId)  $usersQuery->where('id', $userId);
            if ($search)  $usersQuery->where('name', 'like', "%{$search}%");

            return $usersQuery->orderBy('sort_order')->orderBy('name')->get()->map(fn($u) => [
                'id'            => null,
                'week'          => null,
                'status'        => 'not_submitted',
                'status_label'  => '미제출',
                'curr_start'    => null,
                'curr_end'      => null,
                'curr_work'     => [],
                'next_plan'     => [],
                'todo_items'    => [],
                'submitted_at'  => null,
                'comment_count' => 0,
                'user_id'       => $u->id,
                'user'          => ['id' => $u->id, 'name' => $u->name, 'position' => $u->position ?? ''],
            ])->toArray();
        }

        $query = WeeklyReport::with('user')->withCount('comments')->orderBy('week', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($weekStart) {
            // curr_start 날짜 범위가 아닌 week 컬럼으로 조회 (날짜 오차 방지)
            $query->where('week', $this->weekString($weekStart));
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhere('week', 'like', "%{$search}%");
            });
        }

        $reports = $query->get()->map(fn($r) => [
            'id'            => $r->id,
            'week'          => $r->week,
            'week_label'    => $r->week_label,
            'status'        => $r->status,
            'status_label'  => $r->status_label,
            'curr_start'    => $r->curr_start?->format('Y-m-d'),
            'curr_end'      => $r->curr_end?->format('Y-m-d'),
            'curr_work'     => $r->curr_work,
            'next_plan'     => $r->next_plan,
            'todo_items'    => $r->todo_items,
            'notes'         => $r->notes,
            'requests'      => $r->requests,
            'submitted_at'  => $r->submitted_at?->format('Y-m-d H:i'),
            'comment_count' => $r->comments_count ?? 0,
            'user_id'       => $r->user_id,
            'user'          => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'position' => $r->user->position] : null,
        ]);

        // 특정 주차 조회 + 상태 필터 없음 → 미제출 사용자도 포함
        if ($weekStart && !$status) {
            $weekStr         = $this->weekString($weekStart);
            $existingUserIds = WeeklyReport::where('week', $weekStr)->pluck('user_id');

            $usersQuery = User::where('is_active', true)->where('is_hidden', false)->whereNotIn('id', $existingUserIds);
            if ($userId) $usersQuery->where('id', $userId);
            if ($search) $usersQuery->where('name', 'like', "%{$search}%");

            $notSubmitted = $usersQuery->get()->map(fn($u) => [
                'id'            => null,
                'week'          => null,
                'status'        => 'not_submitted',
                'status_label'  => '미제출',
                'curr_start'    => null,
                'curr_end'      => null,
                'curr_work'     => [],
                'next_plan'     => [],
                'todo_items'    => [],
                'notes'         => null,
                'requests'      => null,
                'submitted_at'  => null,
                'comment_count' => 0,
                'user_id'       => $u->id,
                'user'          => ['id' => $u->id, 'name' => $u->name, 'position' => $u->position ?? ''],
            ]);

            $merged = array_merge($reports->toArray(), $notSubmitted->toArray());

            // 사용자 sort_order 기준 정렬
            $sortOrders = User::where('is_active', true)->pluck('sort_order', 'id');
            usort($merged, function ($a, $b) use ($sortOrders) {
                $aOrder = $sortOrders->get($a['user_id'] ?? 0, 999);
                $bOrder = $sortOrders->get($b['user_id'] ?? 0, 999);
                return $aOrder <=> $bOrder;
            });

            return $merged;
        }

        return $reports->toArray();
    }

    public function statusCounts(?int $userId = null, ?string $weekStart = null): array
    {
        $query = WeeklyReport::query();
        if ($userId) $query->where('user_id', $userId);
        if ($weekStart) {
            // week 컬럼으로 조회 (curr_start 날짜 오차 방지)
            $query->where('week', $this->weekString($weekStart));
        }

        $draft     = (clone $query)->where('status', 'draft')->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $rejected  = (clone $query)->where('status', 'rejected')->count();

        // 미제출: 활성 사용자 중 해당 주차에 보고서가 없는 수
        if ($weekStart) {
            $existingUserIds = WeeklyReport::where('week', $this->weekString($weekStart))->pluck('user_id');
            if ($userId) {
                // 비관리자: 본인만 체크
                $notSubmitted = in_array($userId, $existingUserIds->toArray()) ? 0 : 1;
            } else {
                $notSubmitted = User::where('is_active', true)->where('is_hidden', false)->whereNotIn('id', $existingUserIds)->count();
            }
        } else {
            $notSubmitted = 0;
        }

        // 전체 = 미제출 + 작성중(draft) + 제출됨 + 반려됨 합산
        $all = $notSubmitted + $draft + $submitted + $rejected;

        return compact('all', 'draft', 'notSubmitted', 'submitted', 'rejected');
    }

    public function create(array $data, int $userId, bool $submitNow = false): WeeklyReport
    {
        $data['user_id'] = $userId;
        $data['status']  = $submitNow ? 'submitted' : 'draft';
        if ($submitNow) $data['submitted_at'] = now();
        return WeeklyReport::create($data);
    }

    public function update(WeeklyReport $report, array $data): WeeklyReport
    {
        $report->update($data);
        return $report->fresh();
    }

    public function delete(WeeklyReport $report): void
    {
        $report->delete();
    }

    public function findByWeekAndUser(string $week, int $userId): ?WeeklyReport
    {
        return WeeklyReport::where('week', $week)->where('user_id', $userId)->first();
    }

    public function exportExcel(string $currStart): string
    {
        $monday  = Carbon::parse($currStart)->startOfWeek(Carbon::MONDAY);
        $friday  = $monday->copy()->addDays(4);
        $nextMon = $monday->copy()->addDays(7);
        $nextFri = $monday->copy()->addDays(11);
        $week    = $monday->format('Y') . '-W' . $monday->format('W');

        $reports = WeeklyReport::with('user')->where('week', $week)->get()
            ->sortBy(fn($r) => $r->user?->sort_order ?? 999)->values();
        $users   = User::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('주간업무보고 ' . $week);

        // Sheet 1: 팀 일정판
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('팀일정판');
        $this->buildScheduleSheet($sheet1, $monday, $friday, $nextMon, $nextFri, $users);

        // Sheet 2+: 개인 보고서 (sort_order 순)
        foreach ($reports as $idx => $report) {
            $sheet = $spreadsheet->createSheet($idx + 1);
            $sheet->setTitle(substr($report->user->name ?? '무명', 0, 31));
            $this->fillReportSheet($sheet, $report);
        }

        $filename = 'SE팀 주간업무보고_' . $friday->format('Y-m-d') . '.xlsx';
        $filepath = storage_path('app/exports/' . $filename);
        if (!is_dir(storage_path('app/exports'))) mkdir(storage_path('app/exports'), 0777, true);

        (new Xlsx($spreadsheet))->save($filepath);
        return $filepath;
    }

    // ── Sheet 1: 팀 일정판 ────────────────────────────────────────────────────
    private function buildScheduleSheet($sheet, Carbon $monday, Carbon $friday, Carbon $nextMon, Carbon $nextFri, $users): void
    {
        $currDates = collect(range(0, 4))->map(fn($i) => $monday->copy()->addDays($i));
        $nextDates = collect(range(0, 4))->map(fn($i) => $nextMon->copy()->addDays($i));
        $allDates  = [...$currDates->toArray(), ...$nextDates->toArray()];
        $cols      = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        // Row 1: 이름(A1:A2 병합) | 금주(B1:F1) | 차주(G1:K1)
        $sheet->mergeCells('A1:A2');
        $sheet->setCellValue('A1', '이름');
        $sheet->mergeCells('B1:F1');
        $sheet->setCellValue('B1', '금주');
        $sheet->mergeCells('G1:K1');
        $sheet->setCellValue('G1', '차주');

        // Row 2: 날짜 숫자
        foreach ($allDates as $i => $date) {
            $sheet->setCellValue($cols[$i] . '2', $date->day);
        }

        // 스타일: 이름 (연한 베이지)
        $sheet->getStyle('A1:A2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1A1100']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2DEB8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]],
        ]);

        // 금주 헤더 + 날짜 행 (동일한 진한 네이비, 이미지 기준)
        $sheet->getStyle('B1:F2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17375E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        // 차주 헤더 + 날짜 행 (동일한 핑크, 이미지 기준)
        $sheet->getStyle('G1:K2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '4A0010']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFB6C1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // 데이터 행
        $row = 3;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user->name);

            $schedules = $user->schedules()
                ->whereBetween('date', [$monday->format('Y-m-d'), $nextFri->format('Y-m-d')])
                ->get()
                ->keyBy(fn($s) => $s->date->format('Y-m-d'));

            foreach ($allDates as $i => $date) {
                $content = $schedules->get($date->format('Y-m-d'))?->content ?? '';
                $sheet->setCellValue($cols[$i] . $row, $content);
            }

            $sheet->getStyle('A' . $row)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1A1100']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2DEB8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]],
            ]);
            $sheet->getStyle('B' . $row . ':K' . $row)->applyFromArray([
                'font'      => ['size' => 10, 'color' => ['rgb' => '1A1100']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(28);
            $row++;
        }

        // 공지 행
        $sheet->mergeCells('A' . $row . ':K' . $row);
        $sheet->setCellValue('A' . $row, '주간보고 작성 매주 금요일 오전까지 작성 부탁드리겠습니다.');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FF0000'], 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);

        // 열 너비
        $sheet->getColumnDimension('A')->setWidth(12);
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }
    }

    // ── Sheet 2+: 개인 보고서 ─────────────────────────────────────────────────
    private function fillReportSheet($sheet, WeeklyReport $report): void
    {
        $currRange = $this->fmtExcelRange($report->curr_start, $report->curr_end);
        $nextRange = $this->fmtExcelRange($report->next_start, $report->next_end);
        $userName  = $report->user?->name     ?? '-';
        $userPos   = $report->user?->position ?? '사원';

        // ── 헤더 (Row 1~2) ─────────────────────────────
        // A1:E2 병합: 주간업무보고
        $sheet->mergeCells('A1:E2');
        $sheet->setCellValue('A1', '주간업무보고');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
        ]);

        // F1: 전주, G1: 날짜, H1: 이름, I1: 이름값
        // F2: 금주, G2: 날짜, H2: 직급, I2: 직급값
        $hdrData = [
            1 => ['전주', $currRange, '이름', $userName],
            2 => ['금주', $nextRange, '직급', $userPos],
        ];
        $labelStyle = [
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $valueStyle = [
            'font'      => ['size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        foreach ($hdrData as $r => [$l1, $v1, $l2, $v2]) {
            $sheet->setCellValue('F' . $r, $l1);
            $sheet->setCellValue('G' . $r, $v1);
            $sheet->setCellValue('H' . $r, $l2);
            $sheet->setCellValue('I' . $r, $v2);
            $sheet->getStyle('F' . $r)->applyFromArray($labelStyle);
            $sheet->getStyle('G' . $r)->applyFromArray($valueStyle);
            $sheet->getStyle('H' . $r)->applyFromArray($labelStyle);
            $sheet->getStyle('I' . $r)->applyFromArray($valueStyle);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(24);

        // ── 본문 컬럼 헤더 (Row 3) ─────────────────────
        $sheet->setCellValue('A3', '구분');
        $sheet->mergeCells('B3:E3');
        $sheet->setCellValue('B3', '전주 업무 (' . $currRange . ')');
        $sheet->mergeCells('F3:I3');
        $sheet->setCellValue('F3', '금주 업무 (' . $nextRange . ')');
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // ── 지원 행 (Row 4): 전주 / 금주 2열 ──────────
        $row = 4;
        $prevSupport = collect($report->curr_work ?? [])
            ->filter(fn($i) => ($i['category'] ?? '') === '지원')->values()->toArray();
        $prevText = $this->formatItemsAsText($prevSupport);
        $currText = $this->formatItemsAsText($report->next_plan ?? []);

        $sheet->setCellValue('A' . $row, '지원');
        $sheet->mergeCells('B' . $row . ':E' . $row);
        $sheet->setCellValue('B' . $row, $prevText);
        $sheet->mergeCells('F' . $row . ':I' . $row);
        $sheet->setCellValue('F' . $row, $currText);

        $this->applyBodyLabelStyle($sheet, 'A' . $row);
        $this->applyBodyContentStyle($sheet, 'B' . $row);
        $this->applyBodyContentStyle($sheet, 'F' . $row);
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
        ]);
        $lines = max(substr_count($prevText, "\n") + 1, substr_count($currText, "\n") + 1, 3);
        $sheet->getRowDimension($row)->setRowHeight(max(60, $lines * 16));
        $row++;

        // ── 단일열 섹션들 ────────────────────────────
        $prevNaebu  = collect($report->curr_work ?? [])
            ->filter(fn($i) => ($i['category'] ?? '') === '내부작업')->values()->toArray();
        $prevGongyu = collect($report->curr_work ?? [])
            ->filter(fn($i) => ($i['category'] ?? '') === '공유')->values()->toArray();
        $prevGita   = collect($report->curr_work ?? [])
            ->filter(fn($i) => !in_array($i['category'] ?? '', ['지원', '내부작업', '공유']))->values()->toArray();

        $sections = [
            ['todo항목(일정미정)', $this->formatTodoItems($report->todo_items ?? [])],
            ['내부작업',           $this->formatItemsAsText($prevNaebu)],
            ['공유',              $this->formatItemsAsText($prevGongyu)],
        ];
        if (!empty($prevGita)) {
            $sections[] = ['기타', $this->formatItemsAsText($prevGita)];
        }
        if ($report->notes)    $sections[] = ['특이사항', $report->notes];
        if ($report->requests) $sections[] = ['요청사항', $report->requests];

        foreach ($sections as [$label, $content]) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->mergeCells('B' . $row . ':I' . $row);
            $sheet->setCellValue('B' . $row, $content);
            $this->applyBodyLabelStyle($sheet, 'A' . $row);
            $this->applyBodyContentStyle($sheet, 'B' . $row);
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
            ]);
            $lines = max(substr_count($content, "\n") + 1, 2);
            $sheet->getRowDimension($row)->setRowHeight(max(36, $lines * 15));
            $row++;
        }

        // 열 너비
        $sheet->getColumnDimension('A')->setWidth(14);
        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $c) {
            $sheet->getColumnDimension($c)->setWidth(16);
        }
    }

    private function applyBodyLabelStyle($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
    }

    private function applyBodyContentStyle($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font'      => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true, 'indent' => 1],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
    }

    private function fmtExcelRange($start, $end): string
    {
        $s = ($start instanceof Carbon ? $start : Carbon::parse((string) $start))->format('m.d');
        $e = ($end   instanceof Carbon ? $end   : Carbon::parse((string) $end))->format('m.d');
        return $s . '~' . $e;
    }

    private function formatItemsAsText(array $items): string
    {
        $lines = [];
        $num   = 1;
        foreach ($items as $item) {
            $title = $item['title'] ?? $item['content'] ?? '';
            if (trim($title) === '') continue;
            $lines[] = $num++ . '. ' . $title;
            foreach ($item['sub_items'] ?? [] as $sub) {
                // sub_items 항목이 배열(객체)로 저장된 경우에도 안전하게 처리
                $subStr = is_array($sub) ? ($sub['content'] ?? $sub['title'] ?? '') : (string) $sub;
                if (trim($subStr) !== '') {
                    $lines[] = '   - ' . $subStr;
                }
            }
        }
        return implode("\n", $lines);
    }

    private function formatTodoItems(array $todos): string
    {
        $lines = [];
        foreach ($todos as $todo) {
            $mark    = ($todo['done'] ?? false) ? '☑' : '☐';
            $content = $todo['content'] ?? '';
            if (trim($content) !== '') {
                $lines[] = $mark . ' ' . $content;
            }
        }
        return implode("\n", $lines);
    }
}
