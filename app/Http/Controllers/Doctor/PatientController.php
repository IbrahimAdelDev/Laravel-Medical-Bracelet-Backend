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
            
            // 1. البحث داخل مصفوفة الأجهزة عن أي جهاز حالته active
            // دالة contains هترجع true لو لقت جهاز نشط، و false لو مفيش أو المصفوفة فاضية
            $hasActiveDevice = $patient->devices->contains('status', 'active');
            
            // 2. تعيين حالة الجهاز بناءً على نتيجة البحث
            $patientData['device_status'] = $hasActiveDevice ? 'active' : 'inactive';
            
            // 3. مسح مصفوفة الأجهزة بالكامل من الرد النهائي
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
        $doctorId = $request->user()->id;
        
        // فاليديشن للفترة الزمنية المسموحة
        $validated = $request->validate([
            'period' => 'sometimes|in:24h,7d,30d'
        ]);

        $period = $validated['period'] ?? '24h';

        // استدعاء السيرفيس
        $vitals = $this->patientService->getVitalsHistory($doctorId, $id, $period);

        return response()->json([
            'status' => 'success',
            'period' => $period,
            'data' => $vitals
        ]);
    }

    public function availablePatients(Request $request): JsonResponse
    {
        $doctorId = $request->user()->id; 
        $searchQuery = $request->query('search'); 
        $perPage = $request->query('per_page', 15); 

        $patients = $this->patientService->getAvailablePatients($doctorId, $searchQuery, $perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $patients->items(), // مفيش داعي نعمل Map للـ Device هنا لأن الدكتور لسه بيتعرف عليهم
            'pagination' => [
                'total_items' => $patients->total(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
            ]
        ], 200);
    }

    /**
     * إضافة مريض لقائمة الدكتور
     */
    public function addPatient(AddPatientDeviceRequest $request, $patientId): \Illuminate\Http\JsonResponse
    {
        $doctorId = $request->user()->id;
        
        $this->patientService->attachPatientAndDevice($doctorId, $patientId, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Patient has been successfully linked and the medical device has been registered.'
        ], 201); // استخدمنا 201 Created لأننا عملنا Insert لديفايس جديد
    }
}