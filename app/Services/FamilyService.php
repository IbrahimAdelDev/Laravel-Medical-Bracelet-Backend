<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FamilyService
{
    public function findFamilyByEmail(int $patientId, string $email): ?\App\Models\User
    {
        return User::where('role', '!=', 'doctor')
            ->where('email', $email)
            ->whereDoesntHave('patients', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->first();
    }

    public function attachFamilyToPatient(int $patientId, int $familyId): void
    {
        $patient = User::where('role', '!=', 'doctor')->findOrFail($patientId);
        
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);

        $patient->familyMembers()->syncWithoutDetaching([$family->id]);
    }
}