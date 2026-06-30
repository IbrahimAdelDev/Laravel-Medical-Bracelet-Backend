<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\PatientRelationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DoctorPatientController extends Controller
{
    public function __construct(protected PatientRelationService $relationService) {}

    public function unlinkPatient(Request $request, $patientId): JsonResponse
    {
        $doctorId = $request->user()->id;
        
        $this->relationService->unlinkDoctorPatient($doctorId, (int) $patientId);

        return response()->json([
            'status' => 'success',
            'message' => 'Patient has been successfully unlinked from your list.'
        ], 200);
    }
}