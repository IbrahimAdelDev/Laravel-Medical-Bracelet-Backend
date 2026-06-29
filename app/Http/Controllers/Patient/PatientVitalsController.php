<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\VitalsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientVitalsController extends Controller
{
    protected VitalsService $vitalsService;

    public function __construct(VitalsService $vitalsService)
    {
        $this->vitalsService = $vitalsService;
    }

    public function latest(Request $request): JsonResponse
    {
        $vitals = $this->vitalsService->getLatestVitals($request->user()->id);

        if (!$vitals) {
            return response()->json([
                'status' => 'success',
                'message' => 'No vitals recorded yet.',
                'data' => null
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $vitals
        ], 200);
    }
}