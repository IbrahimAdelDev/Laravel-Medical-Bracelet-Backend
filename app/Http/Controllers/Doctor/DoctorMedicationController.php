<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\DoctorMedicationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DoctorMedicationController extends Controller
{
    protected DoctorMedicationService $medicationService;

    public function __construct(DoctorMedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    public function index(Request $request, $patientId): JsonResponse
    {
        $doctorId = $request->user()->id;
        $perPage = (int) $request->query('per_page', 15);

        $medications = $this->medicationService->getPatientMedications($doctorId, (int) $patientId, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $medications->items(),
            'pagination' => [
                'total_items' => $medications->total(),
                'current_page' => $medications->currentPage(),
                'last_page' => $medications->lastPage(),
                'per_page' => $medications->perPage(),
            ]
        ], 200);
    }
}