<?php

namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use App\Models\SensorReading;

class FamilyMonitoringService
{
    public function getPatientStatusForFamily(int $familyId, int $patientId): array
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);
        
        $patient = $family->monitoredPatients()->where('patient_id', $patientId)->firstOrFail();

        $hasActiveAlert = Alert::where('patient_id', $patientId)
            ->where('is_resolved', false)
            ->exists();

        $status = $hasActiveAlert ? 'In Danger' : 'Safe';

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

    public function getLinkedPatientsWithStatus(int $familyId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $family = User::where('role', '!=', 'doctor')->findOrFail($familyId);

        $patients = $family->monitoredPatients()
            ->withExists(['alerts as in_danger' => function ($query) {
                $query->where('is_resolved', false);
            }])
            ->withMax('sensorReadings as latest_reading_at', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

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