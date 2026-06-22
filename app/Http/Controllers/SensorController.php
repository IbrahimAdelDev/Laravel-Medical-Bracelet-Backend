<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SensorSyncRequest;
use App\Models\Device;
use App\Jobs\ProcessIncomingSensorData;
use Illuminate\Http\JsonResponse;

class SensorController extends Controller
{
    public function store(SensorSyncRequest $request): JsonResponse
    {
        $payload = $request->validated();
        
        $device = Device::where('device_uid', $payload['device_uid'])->first();

        if (!$device || $device->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Device inactive or not found.'], 403);
        }

        // رمي الداتا في الطابور (الـ Job بياخد الحاجات الخفيفة بس زي الـ IDs والـ Payload)
        ProcessIncomingSensorData::dispatch($device, $payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Data received and queued for processing.'
        ], 202); // 202 تعني تم القبول وجاري المعالجة في الخلفية
    }
}