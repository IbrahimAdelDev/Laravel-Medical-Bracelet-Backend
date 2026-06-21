<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\EmergencyService;

class EmergencyController extends Controller
{
    // حقن السيرفيس في الكنترولر (Dependency Injection)
    public function __construct(
        private readonly EmergencyService $emergencyService
    ) {}

    public function triggerSos(Request $request): JsonResponse
    {
        // (اختياري) لو الموبايل هيبعت اللوكيشن مع زرار الطوارئ
        $validated = $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            // تفويض المهمة بالكامل للـ Service
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
