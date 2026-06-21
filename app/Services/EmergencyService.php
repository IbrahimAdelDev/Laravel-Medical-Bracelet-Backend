<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Notification;
use App\Events\RealTimeNotificationBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyService
{
    /**
     * معالجة نداء الاستغاثة (SOS)
     */
    public function handleSosAlert($patient, ?array $locationData = null): bool
    {
        try {
            DB::beginTransaction();

            // 1. تسجيل حالة الطوارئ كـ Alert في السيستم
            $alert = Alert::create([
                'patient_id' => $patient->id,
                // 'device_id' => $patient->device_id,
                'type' => 'sos_pressed',
                'message' => "🚨 نداء استغاثة (SOS): المريض {$patient->name} يحتاج إلى مساعدة فورية!",
                'payload' => [
                    'source' => 'mobile_app_sos_button',
                    'timestamp' => now()->toIso8601String(),
                    'location' => $locationData // لو الموبايل بعت اللوكيشن وقت الضغطة
                ]
            ]);

            // 2. تغيير حالة المريض (مفيدة جداً لو عندك لوحة تحكم للدكاترة)
            // $patient->update(['health_status' => 'critical']);

            // 3. إرسال الإشعارات لعائلة المريض
            $this->notifyFamilyMembers($patient, $alert);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SOS Emergency Failed: ' . $e->getMessage(), [
                'patient_id' => $patient->id
            ]);
            
            throw $e; // رمي الإيرور للكنترولر عشان يهندله
        }
    }

    /**
     * دالة مساعدة (Private) مخصصة فقط لتوزيع الإشعارات (تطبيق لـ SRP)
     */
    private function notifyFamilyMembers($patient, Alert $alert): void
    {
        // افترض إن دي العلاقة اللي بتجيب بيها العيلة (عدلها حسب الداتابيز عندك)
        $familyMembers = $patient->familyMembers; 

        if ($familyMembers->isEmpty()) {
            return; // لو مفيش عيلة مربوطة، هنكتفي بتسجيل الألرت في الداتابيز
        }

        foreach ($familyMembers as $familyMember) {
            // أ. إنشاء الإشعار في الداتابيز
            $notification = Notification::create([
                'title' => 'Extreme Emergency (SOS)!',
                'message' => $alert->message,
                'type' => 'alert',
                'payload' => $alert->payload
            ]);

            // ب. ربط الإشعار بالعضو في الجدول الوسيط
            $familyMember->notifications()->attach($notification->id, [
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ج. تطبيق الـ Observer Pattern بإطلاق حدث الويب سوكت
            event(new RealTimeNotificationBroadcast($familyMember->id, $notification));
        }
    }
}