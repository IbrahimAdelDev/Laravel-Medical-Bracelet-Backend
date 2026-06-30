<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertService
{
    /**
     * جلب الإنذارات غير المحلولة لمريض معين (High Performance Pagination)
     */
    public function getUnresolvedAlerts(int $patientId, int $perPage = 15): LengthAwarePaginator
    {
        return Alert::where('patient_id', $patientId)
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * تحويل حالة مجموعة من الإنذارات إلى "محلولة" في استعلام واحد (Bulk Update)
     */
    public function resolveAlerts(array $alertIds, int $resolvedById): int
    {
        // استخدام whereIn مع update بيعمل حركة الـ Bulk Update
        // دي بتعدل آلاف السجلات في جزء من الملي ثانية لأنها بتضرب Query واحد بس في الـ MySQL
        return Alert::whereIn('id', $alertIds)
            ->where('is_resolved', false) // عشان منعملش update للي محلول أصلاً ونوفر وقت
            ->update([
                'is_resolved' => true,
                'resolved_by' => $resolvedById
            ]);
    }
}