<?php

namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use App\Models\SensorReading;

class FamilyMonitoringService
{
    /**
     * جلب حالة المريض الحالية لتابع العائلة (Safe / In Danger + Last Seen)
     */
    public function getPatientStatusForFamily(int $familyId, int $patientId): array
    {
        // 1. التحقق من الصلاحية والأمان: هل فرد العائلة مرتبط بهذا المريض؟
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        
        // بافتراض إن العلاقة في موديل اليوزر اسمها patients
        $patient = $family->monitoredPatients()->where('patient_id', $patientId)->firstOrFail();

        // 2. تقييم الحالة (High Performance Check): هل يوجد أي ألرت نشط وغير محلول للمريض؟
        $hasActiveAlert = Alert::where('patient_id', $patientId)
            ->where('is_resolved', false)
            ->exists(); // دالة exists سريعة جداً لأنها بتقف عند أول صف تلاقيه

        $status = $hasActiveAlert ? 'In Danger' : 'Safe';

        // 3. جلب آخر ظهور (تاريخ آخر قراءة وصلت من الأسورة الذكية)
        $latestReading = SensorReading::where('patient_id', $patientId)
            ->latest('id')
            ->first();

        $lastSeen = $latestReading 
            ? $latestReading->created_at->diffForHumans() 
            : 'No activity recorded';

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'status' => $status,
            'last_seen' => $lastSeen
        ];
    }

    /**
     * جلب جميع المرضى المرتبطين بتابع العائلة مع حالتهم اللحظية (بدون N+1 Problem)
     */
    public function getLinkedPatientsWithStatus(int $familyId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);

        // هنا السحر الهندسي: بنجيب المرضى ومعاهم الحالة وآخر ظهور في استعلام واحد بس!
        $patients = $family->monitoredPatients()
            // 1. بنسأل الداتابيز: هل المريض ده عنده ألرت مش محلول؟ (هترجع true أو false في حقل in_danger)
            ->withExists(['alerts as in_danger' => function ($query) {
                $query->where('is_resolved', false);
            }])
            // 2. بنجيب أحدث تاريخ قراءة من حساسات المريض في حقل latest_reading_at
            ->withMax('sensorReadings as latest_reading_at', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // 3. تحويل شكل الداتا (Data Mapping) لتناسب الموبايل
        $patients->getCollection()->transform(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->name,
                'status' => $patient->in_danger ? 'In Danger' : 'Safe',
                'last_seen' => $patient->latest_reading_at 
                    ? \Carbon\Carbon::parse($patient->latest_reading_at)->diffForHumans() 
                    : 'No activity recorded',
                'can_self_manage' => (bool) $patient->can_self_manage,
            ];
        });

        return $patients;
    }
}