<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Alert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SensorService
{
    public function processIncomingData(array $payload)
    {
        $device = Device::where('device_uid', $payload['device_uid'])->first();

        if (!$device || $device->status !== 'active') {
            return false; // يفضل ترجع false هنا بدل مفيش
        }

        try {
            // نبعت مصفوفة الـ movement كاملة للـ AI وهو هيقسمها جواه
            $response = Http::timeout(5)->post('http://ai_service:8000/predict-fall', [
                'movement' => $payload['movement']
            ]);

            if ($response->successful()) {
                $isFalling = $response->json('fall_detected');

                if ($isFalling) {
                    $this->registerFallAlert($device);
                }
                
                return $isFalling; 
            } else {
                Log::error('AI Service Error: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Failed to connect to AI Service: ' . $e->getMessage());
        }
        
        return false;
    }

    private function registerFallAlert(Device $device)
    {
        // استخدام Models لتسجيل الطوارئ بناءً على العلاقات
        Alert::create([
            'patient_id' => $device->patient_id,
            'device_id' => $device->id,
            'type' => 'fall_detected',
            'is_resolved' => false
        ]);

        // (لاحقاً هنا: كود إرسال الإشعارات للطبيب والعائلة عبر جدول notifications)
    }
}