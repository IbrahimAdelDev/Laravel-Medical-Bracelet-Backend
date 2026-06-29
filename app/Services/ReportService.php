<?php

namespace App\Services;

use App\Models\SensorReading;
use App\Models\MedicationDose;
use Carbon\Carbon;

class ReportService
{
    /**
     * توليد ملخص تقرير أسبوعي ذو أداء عالي
     */
    public function getWeeklySummary(int $patientId): array
    {
        $oneWeekAgo = Carbon::now()->subDays(7);

        // 1. حساب متوسطات الحساسات من داخل الـ JSON باستخدام دالة MySQL
        $vitalsSummary = SensorReading::where('patient_id', $patientId)
            ->where('created_at', '>=', $oneWeekAgo)
            ->selectRaw("
                AVG(JSON_EXTRACT(payload, '$.vitals.heart_rate')) as avg_heart_rate,
                AVG(JSON_EXTRACT(payload, '$.vitals.body_temperature')) as avg_temp,
                AVG(JSON_EXTRACT(payload, '$.vitals.spo2')) as avg_spo2
            ")
            ->first();

        // 2. إحصائيات الالتزام بالأدوية (نفس الكود اللي عملناه لأنه شغال على جدول الجرعات)
        $dosesStats = MedicationDose::whereHas('medication', function ($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->where('scheduled_at', '>=', $oneWeekAgo)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'taken' THEN 1 ELSE 0 END) as taken_count")
            ->first();

        $totalDoses = $dosesStats->total ?? 0;
        $takenDoses = $dosesStats->taken_count ?? 0;
        $adherenceRate = $totalDoses > 0 ? round(($takenDoses / $totalDoses) * 100, 2) : 100;

        return [
            'range' => [
                'from' => $oneWeekAgo->toFormattedDateString(),
                'to' => Carbon::now()->toFormattedDateString(),
            ],
            'vitals_averages' => [
                'heart_rate' => round($vitalsSummary->avg_heart_rate ?? 0, 1),
                'temperature' => round($vitalsSummary->avg_temp ?? 0, 1),
                'spo2' => round($vitalsSummary->avg_spo2 ?? 0, 1),
            ],
            'medication_adherence' => [
                'total_scheduled_doses' => $totalDoses,
                'taken_doses' => $takenDoses,
                'adherence_rate_percentage' => $adherenceRate,
            ]
        ];
    }
}