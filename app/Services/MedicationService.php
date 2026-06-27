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

    public function getMissedDosesStats(int $userId, int $perPage = 15)
    {
        return \App\Models\Medication::where('patient_id', $userId)
            // 1. نجيب الأدوية اللي ليها جرعات فائتة فقط
            ->whereHas('doses', function ($query) {
                $query->where('status', 'missed')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where('scheduled_at', '<', now());
                      });
            })
            // 2. نعمل Eager Loading للجرعات الفائتة دي نفسها مع ترتيبها من الأحدث للأقدم
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

    /**
     * جلب جرعات اليوم للمريض مرتبة زمنياً
     */
    public function getTodaySchedule(int $patientId)
    {
        // نستعلم من موديل الجرعات مباشرة
        return \App\Models\MedicationDose::whereHas('medication', function ($query) use ($patientId) {
                // نتأكد إن الدواء يخص المريض الحالي
                $query->where('patient_id', $patientId);
            })
            // نفلتر بجرعات اليوم فقط (هياخد تاريخ السيرفر أو بناءً على الـ Timezone)
            ->whereDate('scheduled_at', now()->toDateString())
            // نجيب الحالات المطلوبة (لو عندك حالات تانية وعايز تستثنيها)
            ->whereIn('status', ['pending', 'missed', 'taken']) 
            // نجيب بيانات الدواء مع الجرعة (الاسم، الجرعة، الخ)
            ->with('medication:id,name,dosage') 
            // الترتيب من الأقدم للأحدث (من الصبح لليل)
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * تحويل حالة الجرعة إلى "تم التناول"
     */
    public function markDoseAsTaken(int $doseId, int $patientId)
    {
        $dose = \App\Models\MedicationDose::whereHas('medication', function ($query) use ($patientId) {
            $query->where('patient_id', $patientId);
        })->findOrFail($doseId);

        // تحديث الحالة وممكن تسجل وقت التناول الفعلي لو عندك حقل taken_at
        $dose->update([
            'status' => 'taken',
            // 'taken_at' => now(), // شيل الكومنت لو ضايف الحقل ده في الداتابيز
        ]);

        return $dose;
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