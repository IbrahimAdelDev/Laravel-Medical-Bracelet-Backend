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
                ->where('end_date', $data['end_date'])
                ->first();

            // لو الدواء موجود فعلاً، نرجعه مع جرعاته بدون ما نعمل Insert جديد
            if ($existingMedication) {
                return $existingMedication->load('doses');
            }
            // 1. إنشاء الدواء الأساسي
            $medication = Medication::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctorId,
                'name' => $data['name'],
                'dosage' => $data['dosage'],
                'frequency' => $data['frequency'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            // 2. توليد الجرعات (Batch Insert للـ High Performance)
            $dosesToInsert = $this->generateDoseSchedules(
                $medication->id,
                $data['start_date'],
                $data['end_date'],
                $data['scheduled_times']
            );

            // Insert لجميع الجرعات في Query واحد فقط
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
        // هيتم حذف الجرعات المرتبطة أوتوماتيكياً لو عامل CascadeOnDelete في الميجريشن
        $medication->delete();
    }

    public function getMissedDosesStats(int $doctorId, int $perPage = 15)
    {
        // 1. الاستعلام مع الباجينيشن
        $paginator = \App\Models\User::whereHas('doctors', function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId);
        })
        ->whereHas('medications.doses', function ($query) {
            $query->where('status', 'missed')
                  ->orWhere(function ($q) {
                      $q->where('status', 'pending')
                        ->where('scheduled_at', '<', now());
                  });
        })
        ->with(['medications.doses' => function ($query) {
            $query->where('status', 'missed')
                  ->orWhere(function ($q) {
                      $q->where('status', 'pending')
                        ->where('scheduled_at', '<', now());
                  })
                  ->orderBy('scheduled_at', 'desc');
        }, 'medications'])
        ->paginate($perPage); // <-- التعديل الأول هنا

        // 2. استخدام through لتعديل شكل كل مريض جوه الباجينيتور
        $paginator->through(function ($patient) {
            $missedDoses = $patient->medications->flatMap(function ($medication) {
                return $medication->doses->map(function ($dose) use ($medication) {
                    return [
                        'dose_id' => $dose->id,
                        'medication_name' => $medication->name,
                        'dosage' => $medication->dosage,
                        'scheduled_at' => $dose->scheduled_at->format('Y-m-d H:i'),
                    ];
                });
            })->values();

            return [
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'total_missed' => $missedDoses->count(),
                'missed_doses' => $missedDoses,
            ];
        });

        // هنرجع الـ Paginator نفسه للكنترولر
        return $paginator; 
    }

    /**
     * دالة مساعدة لتوليد مصفوفة الجرعات بذكاء
     */
    private function generateDoseSchedules(int $medicationId, string $startDate, string $endDate, array $times): array
    {
        $doses = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // لوب على الأيام
        for ($date = $start; $date->lte($end); $date->addDay()) {
            // لوب على الأوقات المحددة في اليوم
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
}