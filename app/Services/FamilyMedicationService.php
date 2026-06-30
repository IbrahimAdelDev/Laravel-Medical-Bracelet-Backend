<?php

namespace App\Services;

use App\Models\User;
use App\Models\Medication;
use Carbon\Carbon;

class FamilyMedicationService
{
    /**
     * جلب أدوية اليوم مع جرعاتها لمريض معين (مخصصة للعائلة)
     */
    public function getPatientDailyDoses(int $familyId, int $patientId): array
    {
        // 1. التأكد من صلة القرابة (Security First)
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId);

        // 2. الاستعلام من موديل Medication مباشرة
        $medications = Medication::where('patient_id', $patient->id)
            // (أ) نضمن إننا مش هنجيب دواء إلا لو ليه جرعة النهاردة
            ->whereHas('doses', function ($query) {
                $query->whereDate('scheduled_at', Carbon::today());
            })
            // (ب) نجيب الجرعات بتاعة النهاردة بس ونرتبها
            ->with(['doses' => function ($query) {
                $query->whereDate('scheduled_at', Carbon::today())
                      ->orderBy('scheduled_at', 'asc');
            }])
            ->get();

        return [
            'patient_name' => $patient->name,
            'can_self_manage' => (bool) $patient->can_self_manage,
            // غيرنا الكلمة لـ medications لأن الداتا شكلها اتغير
            'medications' => $medications 
        ];
    }
}