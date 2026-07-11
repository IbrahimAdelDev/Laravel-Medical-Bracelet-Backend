<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\DoctorDashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DoctorDashboardService $dashboardService) {}

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->dashboardService->getStatsMetrics($request->user()->id);
        
        return response()->json(['status' => 'success', 'data' => $stats]);
    }
}