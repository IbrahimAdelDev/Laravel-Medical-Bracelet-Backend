<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Events\LocationRequested;
use App\Events\LocationUpdated;
use App\Events\LocationStreamCommand;

class LocationTrackingController extends Controller
{
    public function startTracking(Request $request, int $patientId): JsonResponse
    {
        event(new LocationStreamCommand($patientId, 'start'));
        return response()->json(['message' => 'Tracking stream started.']);
    }

    public function stopTracking(Request $request, int $patientId): JsonResponse
    {
        event(new LocationStreamCommand($patientId, 'stop'));
        return response()->json(['message' => 'Tracking stream stopped.']);
    }

    public function streamLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
        ]);

        event(new LocationUpdated(
            $validated['patient_id'],
            $validated['latitude'],
            $validated['longitude']
        ));

        return response()->json(['status' => 'streaming']);
    }
}