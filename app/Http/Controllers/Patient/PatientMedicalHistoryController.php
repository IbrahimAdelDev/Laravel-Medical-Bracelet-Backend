<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\MedicalHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientMedicalHistoryController extends Controller
{
    public function __construct(protected MedicalHistoryService $historyService) {}

    public function index(Request $request): JsonResponse {
        $perPage = $request->query('per_page', 15);
        $user = request()->user();
        $data = $this->historyService->getPaginatedMedicationHistory($user, $perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'current' => $data['current'],
                'past' => $data['past']->items(),
            ],
            'pagination' => [
                'total' => $data['past']->total(),
                'current_page' => $data['past']->currentPage(),
                'last_page' => $data['past']->lastPage(),
            ]
        ]);
    }
}