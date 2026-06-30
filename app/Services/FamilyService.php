<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FamilyService
{
    /**
     * جلب قائمة أفراد العائلة المتاحين للربط (مع البحث والباجينيشن)
     */
    public function findFamilyByEmail(int $patientId, string $email): ?\App\Models\User
    {
        return User::where('role', '!=', 'doctor')
            ->where('email', $email)
            ->whereDoesntHave('patients', function ($q) use ($patientId) {
                // نتأكد إنه مش مربوط بالمريض ده قبل كده
                $q->where('patient_id', $patientId);
            })
            ->first(); // بنرجع يوزر واحد فقط (أو null)
    }

    /**
     * ربط فرد من العائلة بالمريض الحالي
     */
    public function attachFamilyToPatient(int $patientId, int $familyId): void
    {
        $patient = User::where('role', '!=', 'doctor')->findOrFail($patientId);
        
        // نتأكد إن الحساب اللي بنربطه هو حساب عيلة فعلاً
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);

        // استخدام syncWithoutDetaching لمنع تكرار الربط في تابل الـ pivot
        // تأكد إن اسم العلاقة في موديل اليوزر هو familyMembers
        $patient->familyMembers()->syncWithoutDetaching([$family->id]);
    }
}