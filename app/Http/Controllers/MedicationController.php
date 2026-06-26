<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use App\Services\MedicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function __construct(private readonly MedicationService $medicationService) {}

    public function index(Request $request): JsonResponse
    {
        $doctorId = $request->user()->id;
        $medications = $this->medicationService->getAllMedications($doctorId);

        return response()->json([
            'status' => 'success',
            'data' => $medications
        ]);
    }

    public function store(StoreMedicationRequest $request): JsonResponse
    {
        $medication = $this->medicationService->createMedication($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Medication scheduled successfully.',
            'data' => $medication
        ], 201);
    }

    public function update(UpdateMedicationRequest $request, Medication $medication): JsonResponse
    {
        $updatedMedication = $this->medicationService->updateMedication($medication, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Medication updated successfully.',
            'data' => $updatedMedication
        ]);
    }

    public function destroy(Medication $medication): JsonResponse
    {
        $this->medicationService->deleteMedication($medication);

        return response()->json([
            'status' => 'success',
            'message' => 'Medication deleted successfully.'
        ], 204);
    }

    public function missedDoses(Request $request): JsonResponse
    {
        $doctorId = $request->user()->id;
        $stats = $this->medicationService->getMissedDosesStats($doctorId);

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}