<?php

namespace App\Services;

use App\Models\User;
use App\Models\Medication;

class DoctorMedicationService
{
    public function getPatientMedications(int $doctorId, int $patientId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $doctor = User::where('role', 'doctor')->findOrFail($doctorId);
        $patient = $doctor->patients()->findOrFail($patientId);

        return Medication::where('patient_id', $patient->id)
            // ->with('doses') 
            ->with('condition:id,disease_name,status')
            ->orderBy('id', 'desc') 
            ->paginate($perPage);
    }
}