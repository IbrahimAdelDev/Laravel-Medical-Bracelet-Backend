<?php

namespace App\Services;

use App\Models\User;
use App\Models\Medication;
use Carbon\Carbon;

class FamilyMedicationService
{
    public function getPatientDailyDoses(int $familyId, int $patientId): array
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId);

        $medications = Medication::where('patient_id', $patient->id)
            ->whereHas('doses', function ($query) {
                $query->whereDate('scheduled_at', Carbon::today());
            })
            ->with(['doses' => function ($query) {
                $query->whereDate('scheduled_at', Carbon::today())
                      ->orderBy('scheduled_at', 'asc');
            }])
            ->get();

        return [
            'patient_name' => $patient->name,
            'can_self_manage' => (bool) $patient->can_self_manage,
            'medications' => $medications 
        ];
    }
}