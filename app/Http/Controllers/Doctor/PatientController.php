<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\DoctorPatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AddPatientDeviceRequest;

class PatientController extends Controller
{
    public function __construct(private readonly DoctorPatientService $patientService) {}

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $doctorId = $request->user()->id; 
        $searchQuery = $request->query('search'); 
        $perPage = $request->query('per_page', 15); 

        $patients = $this->patientService->getPatientsList($doctorId, $searchQuery, $perPage);
        
        $transformedData = collect($patients->items())->map(function ($patient) {
            $patientData = $patient->toArray();
            
            $hasActiveDevice = $patient->devices->contains('status', 'active');
            
            $patientData['device_status'] = $hasActiveDevice ? 'active' : 'inactive';
            
            unset($patientData['devices']);
            
            return $patientData;
        });
        
        return response()->json([
            'status' => 'success',
            'data' => $transformedData,
            'pagination' => [
                'total_items' => $patients->total(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
            ]
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $doctorId = $request->user()->id; 
        
        $patient = $this->patientService->getPatientDetails($doctorId, $id);
        
        return response()->json(['status' => 'success', 'data' => $patient]);
    }

    // public function update(Request $request, int $id): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'name' => 'sometimes|string|max:255',
    //         'email' => 'sometimes|email|unique:users,email,'.$id,
    //     ]);

    //     $patient = $this->patientService->updatePatientInfo($id, $validated);
    //     return response()->json(['status' => 'success', 'message' => 'Patient updated.', 'data' => $patient]);
    // }

    public function addNote(Request $request, int $id): JsonResponse
    {
        $doctorId = $request->user()->id;

        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $note = $this->patientService->addDoctorNote($doctorId, $id, $validated);
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Note added successfully.', 
            'data' => $note
        ]);
    }

    public function timeline(Request $request, int $id): JsonResponse
    {
        $doctorId = $request->user()->id; 
        
        $perPage = $request->query('per_page', 10); 

        $timeline = $this->patientService->getTimeline($doctorId, $id, $perPage);
        
        return response()->json([
            'status' => 'success', 
            'data' => $timeline->items(),
            'pagination' => [
                'total_items' => $timeline->total(),
                'current_page' => $timeline->currentPage(),
                'last_page' => $timeline->lastPage(),
                'per_page' => $timeline->perPage(),
            ]
        ]);
    }

    public function vitalsHistory(Request $request, int $id): JsonResponse
    {
        $doctorId = $request->user()->id;
        
        $validated = $request->validate([
            'period' => 'sometimes|in:24h,7d,30d'
        ]);

        $period = $validated['period'] ?? '24h';

        $vitals = $this->patientService->getVitalsHistory($doctorId, $id, $period);

        return response()->json([
            'status' => 'success',
            'period' => $period,
            'data' => $vitals
        ]);
    }

    public function searchByEmail(Request $request): JsonResponse
    {
        $doctorId = $request->user()->id;
        $email = $request->query('email');

        if (!$email) {
            return response()->json(['status' => 'error', 'message' => 'Email is required'], 400);
        }

        $patient = $this->patientService->findAvailablePatientByEmail($doctorId, $email);

        if (!$patient) {
            return response()->json(['status' => 'error', 'message' => 'Patient not found or already linked.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $patient], 200);
    }


    public function addPatient(AddPatientDeviceRequest $request, $patientId): \Illuminate\Http\JsonResponse
    {
        $doctorId = $request->user()->id;
        
        $this->patientService->attachPatientAndDevice($doctorId, $patientId, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Patient has been successfully linked and the medical device has been registered.'
        ], 201);
    }
}