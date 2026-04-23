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
    public function list(?int $userId = null, ?string $status = null, ?string $search = null, ?string $weekStart = null): array
    {
        // 미제출 필터: 해당 주차 보고서가 없는 활성 사용자 목록 반환
        if ($status === 'not_submitted') {
            if (!$weekStart) return [];
            $weekEnd         = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');
            $existingUserIds = WeeklyReport::whereBetween('curr_start', [$weekStart, $weekEnd])->pluck('user_id');

            $usersQuery = User::where('is_active', true)->whereNotIn('id', $existingUserIds);
            if ($userId)  $usersQuery->where('id', $userId);
            if ($search)  $usersQuery->where('name', 'like', "%{$search}%");

            return $usersQuery->orderBy('sort_order')->orderBy('name')->get()->map(fn($u) => [
                'id'           => null,
                'week'         => null,
                'status'       => 'not_submitted',
                'status_label' => '미제출',
                'curr_start'   => null,
                'curr_end'     => null,
                'curr_work'    => [],
                'next_plan'    => [],
                'todo_items'   => [],
                'submitted_at' => null,
                'user_id'      => $u->id,
                'user'         => ['id' => $u->id, 'name' => $u->name, 'position' => $u->position ?? ''],
            ])->toArray();
        }

        $query = WeeklyReport::with('user')->orderBy('week', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($weekStart) {
            $weekEnd = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');
            $query->whereBetween('curr_start', [$weekStart, $weekEnd]);
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

        return $query->get()->map(fn($r) => [
            'id'           => $r->id,
            'week'         => $r->week,
            'status'       => $r->status,
            'status_label' => $r->status_label,
            'curr_start'   => $r->curr_start?->format('Y-m-d'),
            'curr_end'     => $r->curr_end?->format('Y-m-d'),
            'curr_work'    => $r->curr_work,
            'next_plan'    => $r->next_plan,
            'todo_items'   => $r->todo_items,
            'submitted_at' => $r->submitted_at?->format('Y-m-d H:i'),
            'user_id'      => $r->user_id,
            'user'         => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'position' => $r->user->position] : null,
        ])->toArray();
    }

    public function statusCounts(?int $userId = null, ?string $weekStart = null): array
    {
        $query = WeeklyReport::query();
        if ($userId) $query->where('user_id', $userId);
        if ($weekStart) {
            $weekEnd = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');
            $query->whereBetween('curr_start', [$weekStart, $weekEnd]);
        }

        $all       = (clone $query)->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $rejected  = (clone $query)->where('status', 'rejected')->count();

        // 미제출: 활성 사용자 중 해당 주차에 보고서가 없는 수
        if ($weekStart) {
            $weekEnd         = Carbon::parse($weekStart)->addDays(4)->format('Y-m-d');
            $existingUserIds = WeeklyReport::whereBetween('curr_start', [$weekStart, $weekEnd])->pluck('user_id');
            if ($userId) {
                // 비관리자: 본인만 체크
                $notSubmitted = in_array($userId, $existingUserIds->toArray()) ? 0 : 1;
            } else {
                $notSubmitted = User::where('is_active', true)->whereNotIn('id', $existingUserIds)->count();
            }
        } else {
            $notSubmitted = 0;
        }

        return compact('all', 'notSubmitted', 'submitted', 'rejected');
    }

    public function create(array $data, int $userId): WeeklyReport
    {
        $data['user_id'] = $userId;
        $data['status']  = 'draft';
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
        $startDate    = Carbon::parse($currStart);
        $endDate      = $startDate->copy()->addDays(4);
        $week         = $startDate->format('Y') . '-W' . $startDate->format('W');
        $nextWeekStart = $endDate->copy()->addDays(3);
        $nextWeekEnd  = $nextWeekStart->copy()->addDays(4);

        $reports = WeeklyReport::with('user')->where('week', $week)->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('주간업무보고 ' . $week);

        // Sheet 1: 외부일정표
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('외부일정표');

        $dates = [];
        for ($d = $startDate->copy(); $d <= $endDate; $d->addDay()) $dates[] = $d->copy();
        for ($d = $nextWeekStart->copy(); $d <= $nextWeekEnd; $d->addDay()) $dates[] = $d->copy();

        $sheet1->setCellValue('A1', '이름');
        $sheet1->getStyle('A1')->applyFromArray($this->headerStyle());

        $dayNames = ['월', '화', '수', '목', '금'];
        foreach ($dates as $i => $date) {
            $col   = chr(66 + $i);
            $label = $date->format('m/d') . '(' . $dayNames[$date->dayOfWeekIso - 1] . ')';
            $sheet1->setCellValue($col . '1', $label);
            $sheet1->getStyle($col . '1')->applyFromArray($this->headerStyle());
            $sheet1->getColumnDimension($col)->setWidth(14);
        }
        $sheet1->getColumnDimension('A')->setWidth(12);
        $sheet1->getRowDimension(1)->setRowHeight(22);

        $users = User::where('is_active', true)->orderBy('name')->get();
        $row   = 2;
        foreach ($users as $user) {
            $sheet1->setCellValue('A' . $row, $user->name);
            $sheet1->getStyle('A' . $row)->applyFromArray($this->cellStyle());

            $schedules = $user->schedules()
                ->whereBetween('date', [$startDate->format('Y-m-d'), $nextWeekEnd->format('Y-m-d')])
                ->get()->keyBy(fn($s) => $s->date->format('Y-m-d'));

            foreach ($dates as $i => $date) {
                $col     = chr(66 + $i);
                $content = $schedules->get($date->format('Y-m-d'))?->content ?? '';
                $sheet1->setCellValue($col . $row, $content);
                $sheet1->getStyle($col . $row)->applyFromArray($this->cellStyle());
                $sheet1->getStyle($col . $row)->getAlignment()->setWrapText(true);
            }
            $sheet1->getRowDimension($row)->setRowHeight(40);
            $row++;
        }

        // Sheet 2+: 개인 보고서
        foreach ($reports as $idx => $report) {
            $sheet = $spreadsheet->createSheet($idx + 1);
            $sheet->setTitle(substr($report->user->name ?? '무명', 0, 31));
            $this->fillReportSheet($sheet, $report);
        }

        $filename = '주간업무보고_' . $week . '_' . now()->format('YmdHis') . '.xlsx';
        $filepath = storage_path('app/exports/' . $filename);
        if (!is_dir(storage_path('app/exports'))) mkdir(storage_path('app/exports'), 0777, true);

        (new Xlsx($spreadsheet))->save($filepath);
        return $filepath;
    }

    private function fillReportSheet($sheet, WeeklyReport $report): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', ($report->user->name ?? '') . ' - 주간업무보고 ' . $report->week);
        $sheet->getStyle('A1')->applyFromArray($this->titleStyle());
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->setCellValue('A2', '이번 주');
        $sheet->setCellValue('B2', $report->curr_start->format('Y-m-d') . ' ~ ' . $report->curr_end->format('Y-m-d'));
        $sheet->setCellValue('D2', '다음 주');
        $sheet->setCellValue('E2', $report->next_start->format('Y-m-d') . ' ~ ' . $report->next_end->format('Y-m-d'));
        $sheet->getStyle('A2:F2')->applyFromArray($this->subHeaderStyle());

        $row = 3;
        $this->writeSection($sheet, $row, '이번 주 결과', $report->curr_work ?? []);
        $row += count($report->curr_work ?? []) + 2;

        $this->writeSection($sheet, $row, '다음 주 계획', $report->next_plan ?? []);
        $row += count($report->next_plan ?? []) + 2;

        // TODO
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'Todo 목록');
        $sheet->getStyle('A' . $row)->applyFromArray($this->subHeaderStyle());
        $row++;

        foreach ($report->todo_items ?? [] as $item) {
            $sheet->setCellValue('A' . $row, $item['content'] ?? '');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray($this->cellStyle());
            $row++;
        }

        $row++;
        foreach ([['특이사항', $report->notes], ['요청사항', $report->requests]] as [$label, $content]) {
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('A' . $row, $label);
            $sheet->getStyle('A' . $row)->applyFromArray($this->subHeaderStyle());
            $row++;
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('A' . $row, $content ?? '');
            $sheet->getStyle('A' . $row)->applyFromArray($this->cellStyle());
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(60);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
    }

    private function writeSection($sheet, int &$row, string $title, array $items): void
    {
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, $title);
        $sheet->getStyle('A' . $row)->applyFromArray($this->subHeaderStyle());
        $row++;

        $sheet->setCellValue('A' . $row, '분류');
        $sheet->setCellValue('B' . $row, '내용');
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($this->subHeaderStyle());
        $row++;

        foreach ($items as $item) {
            $sheet->setCellValue('A' . $row, $item['category'] ?? '기타');
            $sheet->mergeCells('B' . $row . ':F' . $row);
            $sheet->setCellValue('B' . $row, $item['content'] ?? '');
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($this->cellStyle());
            $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(36);
            $row++;
        }
    }

    private function headerStyle(): array
    {
        return [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1100']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }

    private function titleStyle(): array
    {
        return [
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1A1100']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDCB40']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }

    private function subHeaderStyle(): array
    {
        return [
            'font'      => ['bold' => true, 'color' => ['rgb' => '1A1100']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8EE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }

    private function cellStyle(): array
    {
        return [
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }
}
