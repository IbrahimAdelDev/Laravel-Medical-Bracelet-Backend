<?php

namespace App\Services;

use App\Models\SensorReading;

class VitalsService
{
    /**
     * جلب آخر قراءة للحساسات مع تقييم الحالة المرضية
     */
    public function getLatestVitals(int $patientId): ?array
    {
        // استعلام سريع جداً يجيب آخر صف للمريض
        $latestReading = SensorReading::where('patient_id', $patientId)
            ->latest('id')
            ->first();

        if (!$latestReading || !isset($latestReading->payload['vitals'])) {
            return null;
        }

        $vitals = $latestReading->payload['vitals'];

        return [
            'heart_rate' => $vitals['heart_rate'] ?? null,
            'temperature' => $vitals['body_temperature'] ?? null,
            'spo2' => $vitals['spo2'] ?? null,
            'systolic_bp' => $vitals['systolic_bp'] ?? null,
            'diastolic_bp' => $vitals['diastolic_bp'] ?? null,
            'status' => $this->evaluateVitalsStatus($vitals),
            'measured_at' => $latestReading->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * كبسلة منطق الأعمال لتقييم الحالة
     */
    private function evaluateVitalsStatus(array $vitals): string
    {
        $hr = $vitals['heart_rate'] ?? 0;
        $temp = $vitals['body_temperature'] ?? 0;
        $spo2 = $vitals['spo2'] ?? 100;

        // تقدر تضيف الشروط بتاعتك بناءً على الـ JSON اللي إنت باعته
        if ($hr > 120 || $temp > 39.0 || $spo2 < 90) {
            return 'Critical';
        }
        
        if ($hr < 60 || $temp < 35.0 || $spo2 < 95) {
            return 'Warning';
        }

        return 'Normal';
    }
}