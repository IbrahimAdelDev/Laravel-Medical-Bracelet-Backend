<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertService
{
    public function getUnresolvedAlerts(int $patientId, int $perPage = 15): LengthAwarePaginator
    {
        return Alert::where('patient_id', $patientId)
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function resolveAlerts(array $alertIds, int $resolvedById): int
    {
        return Alert::whereIn('id', $alertIds)
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_by' => $resolvedById
            ]);
    }
}