<?php

namespace App\Services;

use App\Models\Medication;
use App\Models\MedicationDose;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicationService
{
    public function getAllMedications(int $userId, int $perPage = 15)
    {
        return Medication::with(['patient:id,name', 'doses' => function ($query) {
            $query->where('status', 'pending')
                  ->where('scheduled_at', '>=', now())
                  ->orderBy('scheduled_at', 'asc');
        }])
        ->whereHas('patient.doctors', function ($query) use ($userId) {
            $query->where('patient_id', $userId);
        })
        ->latest()
        ->paginate($perPage);
    }

    public function createMedication(array $data, int $doctorId): Medication
    {
        return DB::transaction(function () use ($data, $doctorId) {

            $existingMedication = Medication::where('patient_id', $data['patient_id'])
                ->where('name', $data['name'])
                ->where('dosage', $data['dosage'])
                ->where('start_date', $data['start_date'])
                ->first();

            if ($existingMedication) {
                return $existingMedication->load('doses');
            }
            $medication = Medication::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctorId,
                'name' => $data['name'],
                'condition_id' => $data['condition_id'] ?? null,
                'dosage' => $data['dosage'],
                'frequency' => $data['frequency'],
                'start_date' => $data['start_date'],
            ]);

            $dosesToInsert = $this->generateDoseSchedules(
                $medication->id,
                $data['start_date'],
                $data['scheduled_times']
            );

            MedicationDose::insert($dosesToInsert);

            return $medication->load('doses');
        });
    }

    public function updateMedication(Medication $medication, array $data): Medication
    {
        $medication->update($data);
        return $medication;
    }

    public function deleteMedication(Medication $medication): void
    {
        $medication->delete();
    }

    public function getMissedDosesStats(int $userId, int $perPage = 15)
    {
        return \App\Models\Medication::where('patient_id', $userId)
            ->whereHas('doses', function ($query) {
                $query->where('status', 'missed')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('scheduled_at', '<', now());
                      });
            })
            ->with(['doses' => function ($query) {
                $query->where('status', 'missed')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('scheduled_at', '<', now());
                      })
                      ->orderBy('scheduled_at', 'desc');
            }])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getTodaySchedule(int $patientId)
    {
        return MedicationDose::whereHas('medication', function ($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->whereDate('scheduled_at', now()->toDateString())
            ->whereIn('status', ['pending', 'missed', 'taken']) 
            ->with('medication:id,name,dosage') 
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    public function markDoseAsTaken(int $doseId, int $patientId)
    {
        $dose = MedicationDose::whereHas('medication', function ($query) use ($patientId) {
            $query->where('patient_id', $patientId);
        })->findOrFail($doseId);

        $dose->update([
            'status' => 'taken',
        ]);

        return $dose;
    }

    private function generateDoseSchedules(int $medicationId, string $startDate, array $times): array
    {
        $doses = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($startDate)->addDays(6); 

        for ($date = $start; $date->lte($end); $date->addDay()) {
            foreach ($times as $time) {
                $doses[] = [
                    'medication_id' => $medicationId,
                    'scheduled_at' => $date->format('Y-m-d') . ' ' . $time . ':00',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $doses;
    }

    public function confirmDose(int $doseId, int $patientId, string $action): \App\Models\MedicationDose
    {
        $dose = \App\Models\MedicationDose::whereHas('medication', function ($query) use ($patientId) {
            $query->where('patient_id', $patientId);
        })->findOrFail($doseId);

        if ($action === 'taken') {
            $dose->update([
                'status' => 'taken',
                'taken_at' => now(),
            ]);
        } elseif ($action === 'snooze') {
            $dose->update([
                'status' => 'pending',
                'taken_at' => null,
                'scheduled_at' => now()->addMinutes(15)
            ]);
        }

        return $dose;
    }
}