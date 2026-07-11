<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveAlertsRequest;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function unresolved(Request $request, $patientId): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $userId = $request->user()->id;

        if(!$request->user()->monitoredPatients()->where('patient_id', $patientId)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view alerts for this patient.'
            ], 403);
        }
        
        $alerts = $this->alertService->getUnresolvedAlerts((int) $patientId, (int) $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $alerts->items(),
            'pagination' => [
                'total_items' => $alerts->total(),
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
            ]
        ], 200);
    }

    public function resolve(ResolveAlertsRequest $request): JsonResponse
    {
        if($request->user()->can_self_manage==false) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to resolve alerts.'
            ], 403);
        }
        $updatedCount = $this->alertService->resolveAlerts($request->validated()['alert_ids'], $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Successfully resolved {$updatedCount} alert(s)."
        ], 200);
    }

    public function familyresolve(ResolveAlertsRequest $request, $patientId): JsonResponse
    {
        if(!$request->user()->monitoredPatients()->where('patient_id', $patientId)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view alerts for this patient.'
            ], 403);
        }

        $patient = $request->user()->monitoredPatients()->where('patient_id', $patientId)->first();

        if($patient->can_self_manage==true) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to resolve alerts.'
            ], 403);
        }
        $updatedCount = $this->alertService->resolveAlerts($request->validated()['alert_ids'], $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Successfully resolved {$updatedCount} alert(s)."
        ], 200);
    }
}