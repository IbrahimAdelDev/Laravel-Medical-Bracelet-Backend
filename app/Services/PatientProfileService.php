<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PatientProfileService
{
    /**
     * تغيير حالة قدرة المريض على إدارة حسابه (عن طريق العائلة)
     */
    public function toggleSelfManageStatus(int $familyId, int $patientId, bool $status): void
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId); // التأكد من وجود صلة قرابة

        $patient->update(['can_self_manage' => $status]);
    }

    /**
     * تحديث بيانات المريض بواسطة العائلة
     */
    public function updateProfileByFamily(int $familyId, int $patientId, array $data): void
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId);

        // ⚠️ Business Rule: العائلة تعدل فقط إذا كان المريض لا يستطيع الإدارة
        if ($patient->can_self_manage) {
            abort(403, 'Unauthorized: This patient manages their own profile.');
        }

        $patient->update($data);
    }

    /**
     * تحديث بيانات المريض بواسطة المريض نفسه
     */
    public function updateProfileByPatient(User $patient, array $data): void
    {
        // ⚠️ Business Rule: المريض يعدل فقط إذا كان مسموح له
        if (!$patient->can_self_manage) {
            abort(403, 'Unauthorized: Your profile is managed by your family.');
        }

        $patient->update($data);
    }
}