<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\DoctorPatientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    public function __construct(private readonly DoctorPatientService $patientService) {}

    public function index(Request $request): JsonResponse
    {
        $doctorId = $request->user()->id; 
        $searchQuery = $request->query('search'); 
        
        // لو الموبايل مبعتش per_page، هنخليه 15 كديفولت
        $perPage = $request->query('per_page', 15); 

        // نبعت المتغير الجديد للسيرفيس
        $patients = $this->patientService->getPatientsList($doctorId, $searchQuery, $perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $patients->items(),
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
        $doctorId = $request->user()->id; // جبنا ID الدكتور
        
        // بعتنا ID الدكتور و ID المريض للسيرفيس
        $patient = $this->patientService->getPatientDetails($doctorId, $id);
        
        return response()->json(['status' => 'success', 'data' => $patient]);
    }

    // public function update(Request $request, int $id): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'name' => 'sometimes|string|max:255',
    //         'email' => 'sometimes|email|unique:users,email,'.$id,
    //         // أضف أي حقول أخرى هنا
    //     ]);

    //     $patient = $this->patientService->updatePatientInfo($id, $validated);
    //     return response()->json(['status' => 'success', 'message' => 'Patient updated.', 'data' => $patient]);
    // }

    public function addNote(Request $request, int $id): JsonResponse
    {
        $doctorId = $request->user()->id; // 1. جلب ID الطبيب من التوكن

        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        // 2. إرسال ID الطبيب، و ID المريض، والداتا للسيرفيس
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
        
        // لو الموبايل مبعتش per_page، هنرجع 10 عناصر في الصفحة كديفولت
        $perPage = $request->query('per_page', 10); 

        // السيرفيس هترجع Paginator
        $timeline = $this->patientService->getTimeline($doctorId, $id, $perPage);
        
        // فصل الداتا عن الترقيم زي ما عملنا قبل كده
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
        $period = $request->query('period', '24h');
        // هنا يتم استدعاء بيانات الحساسات (Sensor Data) وتجميعها حسب الفترة
        // بافتراض وجود موديل للحساسات، يمكنك فلترتها بالـ created_at
        return response()->json([
            'status' => 'success', 
            'message' => "Vitals history for period: {$period}",
            'data' => [] // داتا الحساسات المجمعة توضع هنا
        ]);
    }
}