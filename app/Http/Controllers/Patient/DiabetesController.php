<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiabetesCheckRequest;
use App\Services\DiabetesPredictionService;
use Illuminate\Http\JsonResponse;

class DiabetesController extends Controller
{
    protected DiabetesPredictionService $diabetesService;

    public function __construct(DiabetesPredictionService $diabetesService)
    {
        $this->diabetesService = $diabetesService;
    }

    public function check(DiabetesCheckRequest $request): JsonResponse
    {
        $patient = $request->user();
        
        $result = $this->diabetesService->checkRisk($patient, $request->validated());


        return response()->json([
            'status' => 'success',
            'message' => 'Diabetes risk analysis completed.',
            'data' => $result
        ], 200);
    }
}