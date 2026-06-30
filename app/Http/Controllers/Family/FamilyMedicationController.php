<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Services\FamilyMedicationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FamilyMedicationController extends Controller
{
    protected $medicationService;

    public function __construct(FamilyMedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    public function dailyDoses(Request $request, $patientId): JsonResponse
    {
        $familyId = $request->user()->id;

        $data = $this->medicationService->getPatientDailyDoses($familyId, (int) $patientId);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}