<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmDoseRequest;
use App\Services\MedicationService;
use Illuminate\Http\JsonResponse;

class PatientMedicationController extends Controller
{
    protected MedicationService $medicationService;

    public function __construct(MedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    public function confirm(ConfirmDoseRequest $request, $id): JsonResponse
    {
        $patientId = $request->user()->id;
        $patient = $request->user();

        if(!$patient->cal_self_manage) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to confirm doses.'
            ], 403);
        }
        
        $dose = $this->medicationService->confirmDose($id, $patientId, $request->action);

        return response()->json([
            'status' => 'success',
            'message' => 'Medication action processed successfully.',
            'data' => $dose
        ], 200);
    }
}