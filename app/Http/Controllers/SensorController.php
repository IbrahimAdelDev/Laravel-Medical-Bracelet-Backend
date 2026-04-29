<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorDataRequest;
use App\Services\SensorService;

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorDataRequest;
use App\Services\SensorService;

class SensorController extends Controller
{
    // تعريف المتغير واستقباله بيحصل في سطر واحد جوه الـ Constructor
    public function __construct(protected SensorService $sensorService)
    {
    }

    public function store(StoreSensorDataRequest $request)
    {
        // $this->sensorService->processIncomingData($request->validated());

        // return response()->json(['status' => 'success', 'message' => 'Data received']);
        $isFalling = $this->sensorService->processIncomingData($request->validated());

        return response()->json([
            'status' => 'success', 
            'message' => 'Data received',
            'ai_fall_detected' => $isFalling // النتيجة هتظهر هنا في بوستمان
        ]);
    }
}