<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\EmergencyService;

class EmergencyController extends Controller
{
    public function __construct(
        private readonly EmergencyService $emergencyService
    ) {}

    public function triggerSos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            $this->emergencyService->handleSosAlert(
                $request->user(), 
                $validated
            );

            return response()->json([
                'status' => 'success',
                'message' => 'SOS triggered successfully. Family has been alerted.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                // 'message' => 'An error occurred while processing the SOS request.',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
