<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Services\MedicalHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FamilyMedicalHistoryController extends Controller
{
    public function __construct(protected MedicalHistoryService $historyService) {}

    public function show(Request $request, $patientId): JsonResponse {
        $perPage = $request->query('per_page', 15);
        $user = request()->user();
        $patient = $this->historyService->getFamilyPatient($user->id, $patientId);
        $data = $this->historyService->getPaginatedMedicationHistory($patient, $perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => ['current' => $data['current'], 'past' => $data['past']->items()],
            'pagination' => ['total' => $data['past']->total(), 'current_page' => $data['past']->currentPage(), 'last_page' => $data['past']->lastPage()]
        ]);
    }
}