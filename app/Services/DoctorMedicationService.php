<?php

namespace App\Services;

use App\Models\User;
use App\Models\Medication;

class DoctorMedicationService
{
    /**
     * جلب قائمة الأدوية الخاصة بمريض معين (للطبيب المعالج فقط)
     */
    public function getPatientMedications(int $doctorId, int $patientId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // 1. الحماية الصلبة (Security & Authorization): 
        // نتأكد إن الطبيب ده موجود، وإن المريض ده موجود جوه قائمة مرضاه بالفعل
        $doctor = User::where('role', 'doctor')->findOrFail($doctorId);
        $patient = $doctor->patients()->findOrFail($patientId);

        // 2. الاستعلام السريع: جلب الأدوية بدون الجرعات
        return Medication::where('patient_id', $patient->id)
            // ->with('doses') 
            ->with('condition:id,disease_name,status')
            ->orderBy('id', 'desc') // ترتيب من الأحدث للأقدم
            ->paginate($perPage);
    }
}