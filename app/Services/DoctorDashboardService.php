<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorDashboardService
{
    public function getStatsMetrics(int $doctorId): array
    {
        // 1. هنجيب أرقام الـ IDs بتاعت مرضى هذا الطبيب فقط من الجدول الوسيط
        $doctorPatientIds = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->pluck('patient_id')
            ->toArray();

        // لو الدكتور لسه جديد ومعندوش مرضى، نرجع أصفار فوراً عشان منعملش لود على الداتابيز
        if (empty($doctorPatientIds)) {
            return [
                'total_patients' => 0,
                'active_alerts' => 0,
                'stable_cases' => 0,
                'patient_on_watch' => 0,
            ];
        }

        // إجمالي مرضى هذا الطبيب
        $totalPatients = count($doctorPatientIds);
        
        // 2. الأليرتس النشطة (لمرضى هذا الطبيب فقط) في آخر 24 ساعة
        $activeAlerts = Alert::whereIn('patient_id', $doctorPatientIds)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        // 3. المرضى (بتوع الدكتور ده) اللي حصلهم طوارئ في آخر 3 أيام (للمراقبة)
        $patientsWithRecentAlerts = Alert::whereIn('patient_id', $doctorPatientIds)
            ->where('created_at', '>=', Carbon::now()->subDays(3))
            ->distinct('patient_id')
            ->pluck('patient_id')
            ->toArray();
        
        $patientOnWatch = count($patientsWithRecentAlerts);

        // 4. الحالات المستقرة = إجمالي مرضى الدكتور - اللي تحت المراقبة
        $stableCases = $totalPatients - $patientOnWatch;

        return [
            'total_patients' => $totalPatients,
            'active_alerts' => $activeAlerts,
            'stable_cases' => $stableCases,
            'patient_on_watch' => $patientOnWatch,
        ];
    }
}