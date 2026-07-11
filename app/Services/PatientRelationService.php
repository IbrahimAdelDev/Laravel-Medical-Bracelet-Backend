<?php

namespace App\Services;

use App\Models\User;

class PatientRelationService
{
    public function unlinkDoctorPatient(int $doctorId, int $patientId): void
    {
        $doctor = User::where('role', 'doctor')->findOrFail($doctorId);
        $patient = User::where('role', '!=', 'doctor')->findOrFail($patientId);

        $detached = $doctor->patients()->detach($patient->id);

        if ($detached === 0) {
            abort(404, 'This patient is not linked to this doctor.');
        }
    }

    public function unlinkFamilyFromPatient(int $patientId, int $familyId): void
    {
        $patient = User::where('role', '!=', 'doctor')->findOrFail($patientId);
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);

        $detached = $family->monitoredPatients()->detach($patient->id);

        if ($detached === 0) {
            abort(404, 'This family member is not linked to your account.');
        }
    }
}