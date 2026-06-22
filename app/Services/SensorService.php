<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Alert;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\SleepAnalytic; // تأكد إنك عامل use للموديل فوق

class SensorService
{
    public function __construct(private readonly AppNotificationService $notificationService) {}

    public function processIncomingData(Device $device, array $payload): void
    {
        // 1. حفظ المؤشرات الحيوية فقط في قاعدة البيانات (لتخفيف الحمل)
        $this->saveVitalsToDatabase($device, $payload);

        // 2. فحص الخطر الفوري (عشان لو في خطر ننبه فوراً قبل ما نكلم الـ AI)
        $this->checkVitalsThresholds($device, $payload['vitals']);

        // 3. التخاطب مع سيرفر الذكاء الاصطناعي (Microservice)
        $this->analyzeWithAIService($device, $payload);
    }

    private function saveVitalsToDatabase(Device $device, array $payload): void
    {
        $vitals = $payload['vitals'];
        $movement = $payload['movement'];
        unset($payload['movement']);
        
        SensorReading::create([
            'device_id' => $device->id,
            'patient_id' => $device->patient_id,
            'payload' => $payload,
        ]);
    }

    private function checkVitalsThresholds(Device $device, array $vitals): void
    {
        $danger = false;
        $message = '';

        if ($vitals['heart_rate'] < 50 || $vitals['heart_rate'] > 120) {
            $danger = true;
            $message .= "Heart rate is out of safe range. \n";
        }

        if ($vitals['spo2'] < 90) {
            $danger = true;
            $message .= "Oxygen saturation is below safe range. \n";
        }

        if ($vitals['body_temperature'] < 35.0 || $vitals['body_temperature'] > 39.0) {
            $danger = true;
            $message .= "Body temperature is out of safe range. ";
        }

        if ($danger) {
            $this->registerAlert($device, 'vitals_emergency', $message);
        }
    }

    private function analyzeWithAIService(Device $device, array $payload): void
    {
        try {
            $patient = $device->patient;

            $response = Http::timeout(10)->post('http://ai_service:8000/analyze-patient-state', [
                'device_uid' => $payload['device_uid'],
                'vitals' => $payload['vitals'],
                'uv_index' => $payload['environment']['uv_index'] ?? null,
                'movement' => $payload['movement'],
                // ضفنا الأوبجيكت ده هنا وهنبعته جاهز
                'patient_info' => [
                    'gender' => $patient->gender === 'male' ? 1 : 0,
                    'age' => $patient->age ?? 30,
                    'weight_kg' => $patient->weight ?? 70,
                    'height_cm' => $patient->height ?? 170,
                ]
            ]);

            if ($response->successful()) {
                $aiData = $response->json();

                // 1. التعامل مع السقوط
                if ($aiData['fall_detected'] ?? false) {
                    $this->registerAlert($device, 'fall_detected', 'AI detected a potential fall.');
                }

                // 2. التعامل مع تحليلات النوم (تتخزن في جدول منفصل أو تتحدث)
                if (isset($aiData['sleep_analysis'])) {
                    $this->saveSleepAnalytics($device->patient_id, $aiData['sleep_analysis']);
                }

            } else {
                Log::error('AI Service Error: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Failed to connect to AI Service: ' . $e->getMessage());
        }
    }

    private function saveSleepAnalytics(int $patientId, array $sleepData): void
    {
        // هنا بتخزن نتائج البايثون في جدول التحليلات (مثلاً sleep_duration, sleep_quality, category)
        $analytic = SleepAnalytic::firstOrCreate(
            ['patient_id' => $patientId, 'date' => now()->toDateString()],
            [
                'sleep_duration' => 0,
                'sleep_quality' => 0,
                'disorder_prediction' => 'None',
            ]
        );

        // 2. تحديث وتراكم البيانات (تحديث إجباري)
        $analytic->update([
            // بنجمع مدة النوم الجديدة على المدة السابقة
            'sleep_duration' => $analytic->sleep_duration + ($sleepData['duration'] ?? 0),
            // بنحدث جودة النوم والتشخيص بآخر حالة ظهرت
            'sleep_quality' => $sleepData['quality_score'] ?? $analytic->sleep_quality,
            'disorder_prediction' => $sleepData['disorder_prediction'] ?? $analytic->disorder_prediction,
        ]);
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