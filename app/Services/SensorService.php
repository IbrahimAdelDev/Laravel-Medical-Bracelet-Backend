<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Alert;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SensorService
{
    public function __construct(private AppNotificationService $notificationService) {}

    public function processIncomingData(array $payload)
    {
        $device = Device::where('device_uid', $payload['device_uid'])->first();

        if (!$device || $device->status !== 'active') {
            return false;
        }

        $this->saveReadingsToDatabase($device, $payload);
        // if ($payload['alerts']['sos_pressed'] === true) {
        //     $this->alertService->triggerAlert($device, 'sos_pressed', 'SOS button pressed on the device.');
        // }
        $this->checkVitalsThresholds($device, $payload['vitals']);

        try {
            $response = Http::timeout(5)->post('http://ai_service:8000/predict-fall', [
                'movement' => $payload['movement']
            ]);

            if ($response->successful()) {
                $isFalling = $response->json('fall_detected');

                if ($isFalling) {
                    $this->registerAlert($device, 'fall_detected', 'AI detected a potential fall based on movement data.');
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

    private function saveReadingsToDatabase(Device $device, array $movementData)
    {
        SensorReading::create([
            'device_id' => $device->id,
            'patient_id' => $device->patient_id,
            'payload' => $movementData, 
        ]);
    }

    private function checkVitalsThresholds(Device $device, array $vitals)
    {
        $heartRate = $vitals['heart_rate'];
        $spo2 = $vitals['spo2'];
        $temperature = $vitals['body_temperature'];
        $danger = false;
        $message = '';

        // حدود الخطر للنبض (أقل من 50 أو أعلى من 120)
        if ($heartRate < 50 || $heartRate > 120) {
            $danger = true;
            $message .= "Heart rate is out of safe range. \n";
        }

        // حدود الخطر للأكسجين (أقل من 90%)
        if ($spo2 < 90) {
            $danger = true;
            $message .= "Oxygen saturation is below safe range. \n";
        }

        if ($temperature < 35.0 || $temperature > 39.0) {
            $danger = true;
            $message .= "Body temperature is out of safe range. ";
        }

        // لو فيه خطر، هنسجل تنبيه فوري
        if ($danger) {
            $this->registerAlert($device, 'vitals_emergency', $message);
        }
    }

    private function registerAlert(Device $device, string $type, string $notes = null): void
    {
        // تغليف العملية بالكامل في Transaction
        DB::transaction(function () use ($device, $type, $notes) {
            
            // 1. إنشاء الألرت (هياخد ID فوراً في نفس الجلسة)
            $alert = Alert::create([
                'patient_id' => $device->patient_id,
                'device_id' => $device->id,
                'type' => $type,
                'is_resolved' => false,
                'notes' => $notes
            ]);

            // 2. إرسال الإشعار للمريض
            $patient = User::find($device->patient_id);
            if ($patient) {
                $this->notificationService->send(
                    users: $patient,
                    title: 'Urgent medical alert',
                    message: 'Dear patient, ' . ($notes ?? 'An abnormal reading has been detected in your vital signs.'),
                    type: 'alert',
                    payload: ['alert_id' => $alert->id, 'device_uid' => $device->device_uid, 'patient_name' => $patient->name],
                    relatedId: $alert->id,
                    relatedModel: Alert::class 
                );
            }

            // 3. إرسال الإشعار للعائلة
            $familyMembers = $patient->familyMembers;

            if ($familyMembers->isNotEmpty()) {
                $patientName = $patient ? $patient->name : 'Unknown Patient';
                
                $this->notificationService->send(
                    users: $familyMembers,
                    title: 'Emergency Alert for ' . $patientName,
                    message: "Emergency Alert for " . $patientName . ": " . ($notes ?? 'An abnormal reading has been detected in your vital signs.'),
                    type: 'alert',
                    payload: [
                        'alert_id' => $alert->id,
                        'patient_id' => $device->patient_id,
                        'patient_name' => $patientName,
                        'device_uid' => $device->device_uid
                    ],
                    relatedId: $alert->id,
                    relatedModel: Alert::class 
                );
            }
        });
    }
}