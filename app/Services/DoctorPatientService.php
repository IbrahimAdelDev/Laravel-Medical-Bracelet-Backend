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

class DoctorPatientService
{
    public function getPatientsList(int $doctorId, ?string $searchQuery, int $perPage = 15): LengthAwarePaginator
    {
        // 1. هنجيب الـ IDs بتاعت مرضى الدكتور ده بس
        $patientIds = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->pluck('patient_id')
            ->toArray();

        // 2. نبني الـ Query الأساسي ونجبره إنه مايخرجش بره المرضى دول
        $query = User::whereIn('id', $patientIds)->where('role', 'user');

        // 3. تطبيق فلتر البحث المتقدم (لو المستخدم كتب حاجة في السيرش)
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                  ->orWhereHas('phones', function ($phoneQuery) use ($searchQuery) {
                      $phoneQuery->where('phone_number', 'LIKE', "%{$searchQuery}%");
                  });
            });
        }

        // 4. التنفيذ وإرجاع 15 مريض في كل صفحة (Pagination)
        return $query->paginate($perPage);
    }

    public function getPatientDetails(int $doctorId, int $patientId): User
    {
        // 1. نتأكد إن المريض ده متسجل تبع الدكتور ده في الجدول الوسيط
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        // 2. لو مش تبعه (أو بيحاول يهكر السيستم)، نطرده بـ 404
        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        // 3. لو تبعه، نجيب بيانات المريض بأمان
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
        // 1. الحماية (Security Check): التأكد إن المريض ده تبع الدكتور
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        // 2. إنشاء النوتة في جدول medical_histories
        try {
            DB::beginTransaction();

            // 2. إنشاء النوتة في جدول medical_histories
            $note = MedicalHistory::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'condition_title' => 'Doctor Note',
                'description' => $data['note'],
                'date_recorded' => now()->toDateString(),
            ]);

            $doctor = User::find($doctorId);

            // 3. إنشاء الإشعار في جدول notifications
            $notification = Notification::create([
                'title' => 'Doctor Note',
                'message' => "Dr. {$doctor->name} added a new note for you.",
                'type' => 'general',
                'payload' => [
                    'note_id' => $note->id,
                    'doctor_id' => $doctorId
                ]
            ]);

            // 4. ربط الإشعار بالمريض في الجدول الوسيط (notification_users)
            $patient = User::find($patientId);
            $patient->notifications()->attach($notification->id, [
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. إطلاق حدث الويب سوكت عشان الإشعار يوصل للموبايل في نفس اللحظة
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
        // 1. الحماية (Security Check): التأكد إن المريض ده تبع الدكتور
        $isAssigned = DB::table('doctor_patients')
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();

        if (!$isAssigned) {
            abort(404, 'Patient not found or not assigned to you.');
        }

        // 2. جلب حالات الطوارئ (Alerts)
        $alerts = Alert::where('patient_id', $patientId)->get()->map(function ($item) {
            return [
                'type' => 'alert',
                'title' => '🚨 حالة طوارئ: ' . $item->type,
                'description' => $item->message,
                'date' => $item->created_at,
            ];
        });

        // 3. جلب الملاحظات الطبية (Medical Histories)
        $histories = MedicalHistory::where('patient_id', $patientId)->get()->map(function ($item) {
            return [
                'type' => 'clinical_note',
                'title' => '👨‍⚕️ ' . $item->condition_title,
                'description' => $item->description,
                'date' => $item->created_at,
            ];
        });

        // 4. دمج المصفوفتين وترتيبهم تنازلياً (الأحدث فوق)
        $mergedCollection = $alerts->merge($histories)->sortByDesc('date')->values();

        // 5. تطبيق الـ Pagination يدوياً على الـ Collection
        $currentPage = Paginator::resolveCurrentPage() ?: 1; // معرفة الصفحة الحالية من الـ URL أوتوماتيك
        
        // قص العناصر الخاصة بالصفحة الحالية فقط
        $currentPageItems = $mergedCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // بناء الـ Paginator وإرجاعه
        return new LengthAwarePaginator(
            $currentPageItems,
            $mergedCollection->count(), // إجمالي العناصر كلها
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(), // الحفاظ على رابط الـ URL الحالي للباجينيشن
                'pageName' => 'page',
            ]
        );
    }

    public function getVitalsHistory(int $doctorId, int $patientId, string $period): \Illuminate\Support\Collection
    {
        // 1. فحص الأمان
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

        // 2. تجميع الداتا (الـ Aggregation Logic المشترك)
        $selectData = "
            ROUND(AVG(payload->>'$.vitals.heart_rate')) as heart_rate,
            ROUND(AVG(payload->>'$.vitals.spo2')) as oxygen_level,
            ROUND(AVG(payload->>'$.vitals.body_temperature'), 1) as temperature,
            ROUND(AVG(payload->>'$.vitals.hrv_rmssd'), 1) as hrv_rmssd,
            CONCAT(MAX(ROUND(payload->>'$.vitals.systolic_bp')), '/', MAX(ROUND(payload->>'$.vitals.diastolic_bp'))) as blood_pressure
        ";

        // 3. تقسيم الفترات الزمنية (Time Buckets) لضمان رسم حوالي 100 نقطة في الكيرف
        switch ($period) {
            case '7d':
                // تجميع كل ساعتين (2 hours = 7200 seconds) -> هيرجع حوالي 84 عينة
                $query->where('created_at', '>=', Carbon::now()->subDays(7))
                      ->selectRaw("
                          DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at)/7200)*7200), '%Y-%m-%d %H:%i') as time_label,
                          $selectData
                      ")
                      ->groupBy('time_label');
                break;

            case '30d':
                // تجميع كل 8 ساعات (8 hours = 28800 seconds) -> هيرجع حوالي 90 عينة
                $query->where('created_at', '>=', Carbon::now()->subDays(30))
                      ->selectRaw("
                          DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at)/28800)*28800), '%Y-%m-%d %H:%i') as time_label,
                          $selectData
                      ")
                      ->groupBy('time_label');
                break;

            case '24h':
            default:
                // تجميع كل 15 دقيقة (15 minutes = 900 seconds) -> هيرجع حوالي 96 عينة
                $query->where('created_at', '>=', Carbon::now()->subDay())
                      ->selectRaw("
                          DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at)/900)*900), '%Y-%m-%d %H:%i') as time_label,
                          $selectData
                      ")
                      ->groupBy('time_label');
                break;
        }

        // 4. الترتيب الزمني
        return $query->orderBy('time_label', 'asc')->get();
    }
}