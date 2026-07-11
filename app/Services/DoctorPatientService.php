<?php

namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use App\Models\MedicalHistory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Events\RealTimeNotificationBroadcast;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use App\Models\Device;

class DoctorPatientService
{
    public function getPatientsList(int $doctorId, ?string $searchQuery, int $perPage = 15): LengthAwarePaginator
    {
        $patientIds = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->pluck('patient_id')
            ->toArray();

        $query = User::with('devices')->whereIn('id', $patientIds)->where('role', 'user');
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                  ->orWhereHas('phones', function ($phoneQuery) use ($searchQuery) {
                      $phoneQuery->where('phone_number', 'LIKE', "%{$searchQuery}%");
                  });
            });
        }

        return $query->paginate($perPage);
    }

    public function getPatientDetails(int $doctorId, int $patientId): User
    {
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        return User::where('role', 'user')->findOrFail($patientId);
    }

    public function updatePatientInfo(int $id, array $data): User
    {
        $patient = User::findOrFail($id);
        $patient->update($data);
        return $patient;
    }

    public function addDoctorNote(int $doctorId, int $patientId, array $data): MedicalHistory
    {
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        try {
            DB::beginTransaction();

            $note = MedicalHistory::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'condition_title' => 'Doctor Note',
                'description' => $data['note'],
                'date_recorded' => now()->toDateString(),
            ]);

            $doctor = User::find($doctorId);

            $notification = Notification::create([
                'title' => 'Doctor Note',
                'message' => "Dr. {$doctor->name} added a new note for you.",
                'type' => 'general',
                'payload' => [
                    'note_id' => $note->id,
                    'doctor_id' => $doctorId
                ]
            ]);

            $patient = User::find($patientId);
            $patient->notifications()->attach($notification->id, [
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new RealTimeNotificationBroadcast($patientId, $notification));

            DB::commit();
            return $note;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getTimeline(int $doctorId, int $patientId, int $perPage = 10): LengthAwarePaginator
    {
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        $alerts = Alert::where('patient_id', $patientId)->get()->map(function ($item) {
            return [
                'type' => 'alert',
                'title' => '🚨 حالة طوارئ: ' . $item->type,
                'description' => $item->message,
                'date' => $item->created_at,
            ];
        });

        $histories = MedicalHistory::where('patient_id', $patientId)->get()->map(function ($item) {
            return [
                'type' => 'clinical_note',
                'title' => '👨‍⚕️ ' . $item->condition_title,
                'description' => $item->description,
                'date' => $item->created_at,
            ];
        });

        $mergedCollection = $alerts->merge($histories)->sortByDesc('date')->values();

        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        
        $currentPageItems = $mergedCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $mergedCollection->count(), 
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function getVitalsHistory(int $doctorId, int $patientId, string $period): \Illuminate\Support\Collection
    {
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        $query = DB::table('sensor_readings')
            ->where('patient_id', $patientId)
            ->whereNotNull('payload'); 

        $selectData = "
            ROUND(AVG(payload->>'$.vitals.heart_rate')) as heart_rate,
            ROUND(AVG(payload->>'$.vitals.spo2')) as oxygen_level,
            ROUND(AVG(payload->>'$.vitals.body_temperature'), 1) as temperature,
            ROUND(AVG(payload->>'$.vitals.hrv_rmssd'), 1) as hrv_rmssd,
            CONCAT(MAX(ROUND(payload->>'$.vitals.systolic_bp')), '/', MAX(ROUND(payload->>'$.vitals.diastolic_bp'))) as blood_pressure
        ";

        switch ($period) {
            case '7d':
                $query->where('created_at', '>=', Carbon::now()->subDays(7))
                      ->selectRaw("
                          DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at)/7200)*7200), '%Y-%m-%d %H:%i') as time_label,
                          $selectData
                      ")
                      ->groupBy('time_label');
                break;

            case '30d':
                $query->where('created_at', '>=', Carbon::now()->subDays(30))
                      ->selectRaw("
                          DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at)/28800)*28800), '%Y-%m-%d %H:%i') as time_label,
                          $selectData
                      ")
                      ->groupBy('time_label');
                break;

            case '24h':
            default:
                $query->where('created_at', '>=', Carbon::now()->subDay())
                      ->selectRaw("
                          DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at)/900)*900), '%Y-%m-%d %H:%i') as time_label,
                          $selectData
                      ")
                      ->groupBy('time_label');
                break;
        }

        return $query->orderBy('time_label', 'asc')->get();
    }

    public function findAvailablePatientByEmail(int $doctorId, string $email): ?User
    {
        return User::where('role', '!=', 'doctor') 
            ->where('email', $email)
            ->whereDoesntHave('doctors', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            })
            ->first();
    }

    public function attachPatientAndDevice(int $doctorId, int $patientId, array $deviceData): void
    {
        DB::transaction(function () use ($doctorId, $patientId, $deviceData) {
            
            $doctor = User::findOrFail($doctorId);
            $patient = User::where('role', '!=', 'doctor')->findOrFail($patientId);

            $doctor->patients()->syncWithoutDetaching([$patient->id]);

            if (!empty($deviceData['device_uid'])) {
                Device::create([
                'device_uid' => $deviceData['device_uid'],
                'patient_id' => $patient->id,
                'status' => $deviceData['status'] ?? 'active',
            ]);
            }
            
        });
    }
}