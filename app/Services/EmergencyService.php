<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Notification;
use App\Events\RealTimeNotificationBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyService
{
    public function handleSosAlert($patient, ?array $locationData = null): bool
    {
        try {
            DB::beginTransaction();

            $alert = Alert::create([
                'patient_id' => $patient->id,
                // 'device_id' => $patient->device_id,
                'type' => 'sos_pressed',
                'message' => "Extreme Emergency (SOS) Alert from Patient: {$patient->name}!",
                'payload' => [
                    'source' => 'mobile_app_sos_button',
                    'timestamp' => now()->toIso8601String(),
                    'location' => $locationData 
                ]
            ]);

            $this->notifyFamilyMembers($patient, $alert);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SOS Emergency Failed: ' . $e->getMessage(), [
                'patient_id' => $patient->id
            ]);
            
            throw $e; 
        }
    }

    private function notifyFamilyMembers($patient, Alert $alert): void
    {
        $familyMembers = $patient->familyMembers; 

        if ($familyMembers->isEmpty()) {
            return; 
        }

        foreach ($familyMembers as $familyMember) {
            $notification = Notification::create([
                'title' => 'Extreme Emergency (SOS)!',
                'message' => $alert->message,
                'type' => 'alert',
                'payload' => $alert->payload
            ]);

            $familyMember->notifications()->attach($notification->id, [
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new RealTimeNotificationBroadcast($familyMember->id, $notification));
        }
    }
}