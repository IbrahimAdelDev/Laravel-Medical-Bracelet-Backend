<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorDashboardService
{
    public function getStatsMetrics(int $doctorId): array
    {
        $doctorPatientIds = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->pluck('patient_id')
            ->toArray();

        if (empty($doctorPatientIds)) {
            return [
                'total_patients' => 0,
                'active_alerts' => 0,
                'stable_cases' => 0,
                'patient_on_watch' => 0,
            ];
        }

        $totalPatients = count($doctorPatientIds);
        
        $activeAlerts = Alert::whereIn('patient_id', $doctorPatientIds)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        $patientsWithRecentAlerts = Alert::whereIn('patient_id', $doctorPatientIds)
            ->where('created_at', '>=', Carbon::now()->subDays(3))
            ->distinct('patient_id')
            ->pluck('patient_id')
            ->toArray();
        
        $patientOnWatch = count($patientsWithRecentAlerts);

        $stableCases = $totalPatients - $patientOnWatch;

        return [
            'total_patients' => $totalPatients,
            'active_alerts' => $activeAlerts,
            'stable_cases' => $stableCases,
            'patient_on_watch' => $patientOnWatch,
        ];
    }
}