<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Services\FamilyMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FamilyMonitoringController extends Controller
{
    protected FamilyMonitoringService $monitoringService;

    public function __construct(FamilyMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $familyId = $request->user()->id;
        $perPage = $request->query('per_page', 15);

        $patients = $this->monitoringService->getLinkedPatientsWithStatus($familyId, (int) $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $patients->items(),
            'pagination' => [
                'total_items' => $patients->total(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
            ]
        ], 200);
    }

    /**
     * جلب حالة مريض معين
     */
    public function patientStatus(Request $request,int $id): JsonResponse
    {
        $familyId = $request->user()->id;

        $statusData = $this->monitoringService->getPatientStatusForFamily($familyId, (int) $id);

        return response()->json([
            'status' => 'success',
            'data' => $statusData
        ], 200);
    }
}