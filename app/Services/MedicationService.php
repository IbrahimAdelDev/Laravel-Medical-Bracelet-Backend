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
    public function getAllMedications(int $doctorId)
    {
        // استرجاع أدوية المرضى التابعين لهذا الطبيب مع الجرعات القادمة فقط
        return Medication::with(['patient:id,name', 'doses' => function ($query) {
            $query->where('status', 'pending')
                  ->where('scheduled_at', '>=', now())
                  ->orderBy('scheduled_at', 'asc');
        }])
        ->whereHas('patient.doctors', function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId);
        })
        ->latest()
        ->get();
    }

    public function createMedication(array $data): Medication
    {
        return DB::transaction(function () use ($data) {
            // 1. إنشاء الدواء الأساسي
            $medication = Medication::create([
                'patient_id' => $data['patient_id'],
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

    public function getMissedDosesStats(int $doctorId): array
    {
        // نجيب المرضى اللي عندهم جرعات فائتة (Missed) التابعين للطبيب ده
        $patientsWithMissedDoses = User::whereHas('medications.doses', function ($query) {
            $query->where('status', 'missed');
        })
        ->whereHas('doctors', function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId);
        })
        ->with(['medications.doses' => function ($query) {
            $query->where('status', 'missed')->orderBy('scheduled_at', 'desc');
        }])
        ->get();

        return [
            'missed_patients_count' => $patientsWithMissedDoses->count(),
            'patients' => $patientsWithMissedDoses->map(function ($patient) {
                return [
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->name,
                    'missed_doses' => $patient->medications->flatMap->doses->map(function ($dose) {
                        return [
                            'medication_name' => $dose->medication->name,
                            'dosage' => $dose->medication->dosage,
                            'scheduled_at' => $dose->scheduled_at->format('Y-m-d H:i'),
                        ];
                    })
                ];
            })
        ];
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