<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiabetesPredictionService
{
    public function checkRisk(User $patient, array $symptoms): array
    {
        $aiPayload = [
            'Age' => $patient->age ?? 30, 
            'Gender' => strtolower($patient->gender) === 'male' ? 1 : 0,
            
            'Polyuria' => (int) $symptoms['polyuria'],
            'Polydipsia' => (int) $symptoms['polydipsia'],
            'sudden_weight_loss' => (int) $symptoms['sudden_weight_loss'],
            'weakness' => (int) $symptoms['weakness'],
            'Polyphagia' => (int) $symptoms['polyphagia'],
            'Genital_thrush' => (int) $symptoms['genital_thrush'],
            'visual_blurring' => (int) $symptoms['visual_blurring'],
            'Itching' => (int) $symptoms['itching'],
            'Irritability' => (int) $symptoms['irritability'],
            'delayed_healing' => (int) $symptoms['delayed_healing'],
            'partial_paresis' => (int) $symptoms['partial_paresis'],
            'muscle_stiffness' => (int) $symptoms['muscle_stiffness'],
            'Alopecia' => (int) $symptoms['alopecia'],
            'Obesity' => (int) $symptoms['obesity'],
        ];


        try {
            $response = Http::timeout(10)->post('http://ai_service:8000/predict-diabetes', $aiPayload);

            if ($response->successful()) {
                return $response->json(); 
            }

            Log::error('Diabetes AI Service Error: ' . $response->body());
            abort(500, 'AI Service is currently unavailable.');

        } catch (\Exception $e) {
            Log::error('Failed to connect to Diabetes AI Service: ' . $e->getMessage());
            abort(500, 'Could not connect to AI Service.');
        }
    }
}