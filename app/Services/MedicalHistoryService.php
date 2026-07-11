<?php

namespace App\Services;

use App\Models\User;
use App\Models\Condition;
use App\Models\Medication; 
use Illuminate\Support\Facades\DB;

class MedicalHistoryService
{
    private function getPatientForDoctor(int $doctorId, int $patientId): User
    {
        $doctor = User::where('role', 'doctor')->findOrFail($doctorId);
        if (! $doctor->patients()
        ->where('users.id', $patientId)
        ->exists()) {
        abort(403, 'Unauthorized access to this patient.');
    }
        return $doctor->patients()->findOrFail($patientId);
    }

    public function getPaginatedConditions(User $patient, int $perPage)
    {
        return Condition::where('patient_id', $patient->id)
            ->orderBy('diagnosed_at', 'desc')
            ->paginate($perPage);
    }

    public function manageCondition(int $doctorId, int $patientId, array $data, int $conditionId = null)
    {
        $patient = $this->getPatientForDoctor($doctorId, $patientId);

        return Condition::updateOrCreate(
            ['id' => $conditionId, 'patient_id' => $patient->id],
            [
                'disease_name' => $data['disease_name'] ?? null,
                'status' => $data['status'] ?? 'active',
                'diagnosed_at' => $data['diagnosed_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    public function deleteCondition(int $doctorId, int $patientId, int $conditionId): void
    {
        $patient = $this->getPatientForDoctor($doctorId, $patientId);
        Condition::where('patient_id', $patient->id)->where('id', $conditionId)->delete();
    }

    public function getPaginatedMedicationHistory(User $patient, int $perPage): array
    {
        $current = Medication::where('patient_id', $patient->id)
            ->whereNull('end_date')
            ->with('condition:id,disease_name,status')
            ->orderBy('start_date', 'desc')
            ->get();

        $past = Medication::where('patient_id', $patient->id)
            ->whereNotNull('end_date')
            ->with('condition:id,disease_name,status')
            ->orderBy('end_date', 'desc') 
            ->paginate($perPage);

        return [
            'current' => $current,
            'past' => $past
        ];
    }

    public function getConditions(int $doctorId, int $patientId, int $perPage = 15)
    {
        $patient = $this->getPatientForDoctor($doctorId, $patientId);
        return $this->getPaginatedConditions($patient, $perPage);
    }

    public function getMedicationHistory(int $doctorId, int $patientId, int $perPage = 15): array
    {
        $patient = $this->getPatientForDoctor($doctorId, $patientId);
        return $this->getPaginatedMedicationHistory($patient, $perPage);
    }

    public function stopMedication(int $doctorId, int $patientId, int $medId, string $stopReason): void
    {
        $patient = $this->getPatientForDoctor($doctorId, $patientId);
        
        Medication::where('patient_id', $patient->id)
            ->where('id', $medId)
            ->update([
                'end_date' => now(),
                'stop_reason' => $stopReason
            ]);
    }

    public function getFullHistoryForPatient(User $patient): array
    {
        $medications = Medication::where('patient_id', $patient->id)
            ->with('condition:id,disease_name')
            ->orderBy('start_date', 'desc')
            ->get();

        return [
            'patient_name' => $patient->name,
            'conditions' => Condition::where('patient_id', $patient->id)->orderBy('status')->get(),
            'medications' => [
                'current' => $medications->whereNull('end_date')->values(),
                'past' => $medications->whereNotNull('end_date')->values(),
            ]
        ];
    }

    public function getFullHistoryForFamily(int $familyId, int $patientId): array
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        $patient = $family->monitoredPatients()->findOrFail($patientId); 

        return $this->getFullHistoryForPatient($patient);
    }

    public function getFamilyPatient(int $familyId, int $patientId): User
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        return $family->monitoredPatients()->findOrFail($patientId);
    }
}