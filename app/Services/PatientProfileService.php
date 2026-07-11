<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PatientProfileService
{
    public function toggleSelfManageStatus(int $familyId, int $patientId, bool $status): void
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId); 

        $patient->update(['can_self_manage' => $status]);
    }

    public function updateProfileByFamily(int $familyId, int $patientId, array $data): void
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId);

        if ($patient->can_self_manage) {
            abort(403, 'Unauthorized: This patient manages their own profile.');
        }

        $patient->update($data);
    }

    public function updateProfileByPatient(User $patient, array $data): void
    {
        if (!$patient->can_self_manage) {
            abort(403, 'Unauthorized: Your profile is managed by your family.');
        }

        $patient->update($data);
    }
}