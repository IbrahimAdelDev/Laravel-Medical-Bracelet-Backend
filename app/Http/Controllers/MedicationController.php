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
        $userId = $request->user()->id;
        
        $perPage = $request->query('per_page', 15);
        $perPage = min((int) $perPage, 100);

        $medications = $this->medicationService->getAllMedications($userId, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $medications->items(), 
            'pagination' => [
                'total_items' => $medications->total(),        
                'count' => $medications->count(),                
                'per_page' => $medications->perPage(),           
                'current_page' => $medications->currentPage(), 
                'total_pages' => $medications->lastPage(),       
                'has_more_pages' => $medications->hasMorePages(),
            ]
        ]);
    }

    public function store(StoreMedicationRequest $request): JsonResponse
    {
        $doctorId = $request->user()->id;
        $medication = $this->medicationService->createMedication($request->validated(), $doctorId);

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
        $userId = $request->user()->id;

        $perPage = $request->query('per_page', 15);
        $perPage = min((int) $perPage, 100);

        $missedDosesPaginator = $this->medicationService->getMissedDosesStats($userId, $perPage);
        return response()->json([
            'status' => 'success',
            'data' => $missedDosesPaginator->items(), // الداتا المنسقة
            'pagination' => [
                'total_items' => $missedDosesPaginator->total(),        
                'count' => $missedDosesPaginator->count(),                
                'per_page' => $missedDosesPaginator->perPage(),           
                'current_page' => $missedDosesPaginator->currentPage(), 
                'total_pages' => $missedDosesPaginator->lastPage(),       
                'has_more_pages' => $missedDosesPaginator->hasMorePages(),
            ]
        ]);
    }
}