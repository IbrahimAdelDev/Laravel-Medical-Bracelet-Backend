<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function weekly(Request $request): JsonResponse
    {
        $reportData = $this->reportService->getWeeklySummary($request->user()->id);

        return response()->json([
            'status' => 'success',
            'data' => $reportData
        ], 200);
    }
}