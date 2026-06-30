<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConditionRequest;
use App\Http\Requests\MedicationHistoryRequest;
use App\Http\Requests\StopMedicationRequest;
use App\Services\MedicalHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorMedicalHistoryController extends Controller
{
    public function __construct(protected MedicalHistoryService $historyService) {}

    // --- الأمراض والتشخيصات ---
    public function getConditions(Request $request, $patientId): JsonResponse {
        $perPage = $request->query('per_page', 15);
        $userId = request()->user()->id;
        $data = $this->historyService->getConditions($userId, $patientId, $perPage);
        return response()->json([
            'status' => 'success',
            'data' => $data->items(),
            'pagination' => [
                'total_items' => $data->total(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
            ]
        ]);
    }

    public function storeCondition(ConditionRequest $request, $patientId): JsonResponse {
        $userId = request()->user()->id;
        $this->historyService->manageCondition($userId, $patientId, $request->validated());
        return response()->json(['status' => 'success', 'message' => 'Condition added.']);
    }

    public function updateCondition(ConditionRequest $request, $patientId, $conditionId): JsonResponse {
        $userId = request()->user()->id;
        $this->historyService->manageCondition($userId, $patientId, $request->validated(), $conditionId);
        return response()->json(['status' => 'success', 'message' => 'Condition updated.']);
    }

    public function destroyCondition($patientId, $conditionId): JsonResponse {
        $userId = request()->user()->id;
        $this->historyService->deleteCondition($userId, $patientId, $conditionId);
        return response()->json(['status' => 'success', 'message' => 'Condition removed.']);
    }

    // --- سجل الأدوية التاريخي ---
    public function getMedicationHistory(Request $request, $patientId): JsonResponse {
        $perPage = $request->query('per_page', 15);
        $userId = request()->user()->id;
        $data = $this->historyService->getMedicationHistory($userId, $patientId, $perPage);
        return response()->json([
            'status' => 'success',
            'data' => [
                'current' => $data['current'],
                'past' => $data['past']->items(),
            ],
            'pagination' => [
                'total_items' => $data['past']->total(),
                'current_page' => $data['past']->currentPage(),
                'last_page' => $data['past']->lastPage(),
                'per_page' => $data['past']->perPage(),
            ]
        ]);
    }

    // public function storeMedicationHistory(MedicationHistoryRequest $request, $patientId): JsonResponse {
    //     $userId = request()->user()->id;
    //     $this->historyService->addMedicationHistory($userId, $patientId, $request->validated());
    //     return response()->json(['status' => 'success', 'message' => 'Medication documented.']);
    // }

    public function stopMedication(StopMedicationRequest $request, $patientId, $medId): JsonResponse {
        $userId = request()->user()->id;
        $this->historyService->stopMedication($userId, $patientId, $medId, $request->stop_reason);
        return response()->json(['status' => 'success', 'message' => 'Medication stopped and moved to past history.']);
    }
}