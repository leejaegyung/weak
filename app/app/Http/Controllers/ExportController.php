<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function weeklyExcel(Request $request): BinaryFileResponse
    {
        $request->validate([
            'curr_start' => ['required', 'date'],
        ]);

        $filepath = $this->reportService->exportExcel($request->input('curr_start'));
        $filename = basename($filepath);

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
