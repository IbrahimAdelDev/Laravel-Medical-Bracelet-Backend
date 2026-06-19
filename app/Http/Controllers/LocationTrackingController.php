<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Events\LocationRequested;
use App\Events\LocationUpdated;
use App\Events\LocationStreamCommand;

class LocationTrackingController extends Controller
{
    public function startTracking(Request $request, int $patientId): JsonResponse
    {
        // نأمر موبايل المريض بفتح الـ GPS والبدء في الإرسال
        event(new LocationStreamCommand($patientId, 'start'));
        return response()->json(['message' => 'Tracking stream started.']);
    }

    // الفاميلي تضغط "إيقاف التتبع"
    public function stopTracking(Request $request, int $patientId): JsonResponse
    {
        // نأمر موبايل المريض بإيقاف الـ GPS توفيراً للبطارية
        event(new LocationStreamCommand($patientId, 'stop'));
        return response()->json(['message' => 'Tracking stream stopped.']);
    }

    // موبايل المريض هيفضل يضرب الـ Endpoint دي كل 3 ثواني طول ما الجلسة شغالة
    public function streamLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
        ]);

        // السيرفر بياخد اللوكيشن ويذيعه فوراً في الويب سوكت لخريطة الفاميلي
        event(new LocationUpdated(
            $validated['patient_id'],
            $validated['latitude'],
            $validated['longitude']
        ));

        return response()->json(['status' => 'streaming']);
    }
    /**
     * The Ping: عائلة المريض تطلب تحديث الموقع
     * (يقوم بإرسال إشعار لحظي لإيقاظ هاتف المريض وقراءة الـ GPS)
     */
    // public function pingLocation(Request $request, int $patientId): JsonResponse
    // {
    //     // استخراج معرف المستخدم الحالي (العائلة أو الطبيب)
    //     $familyId = $request->user()->id;
    //     // 💡 ملاحظة هندسية: في بيئة الإنتاج الفعلي، يجب هنا التحقق 
    //     // من أن هذا المستخدم (familyId) لديه صلاحية لتتبع هذا المريض بالذات.

    //     // إطلاق الحدث في الـ WebSockets
    //     event(new LocationRequested($patientId, $familyId));

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Successfully requested patient location. The patient device should respond shortly.'
    //     ]);
    // }

    /**
     * The Pong: هاتف المريض يرسل الإحداثيات (GPS) للخادم
     * (يقوم الخادم باستلامها وبثها فوراً لتحديث خريطة العائلة)
     */
    // public function pongLocation(Request $request): JsonResponse
    // {
    //     // تحقق صارم من صحة الإحداثيات الجغرافية
    //     $validated = $request->validate([
    //         // التأكد من أن المريض موجود في الداتابيز (عدل 'users' باسم جدول المرضى الفعلي إذا اختلف)
    //         'patient_id' => ['required', 'integer', 'exists:users,id'], 
    //         'latitude'   => ['required', 'numeric', 'between:-90,90'],
    //         'longitude'  => ['required', 'numeric', 'between:-180,180'],
    //     ]);

    //     // 💡 ملاحظة: إذا كنت ترغب في الاحتفاظ بـ "تاريخ مسار المريض"، 
    //     // يمكنك عمل Insert لهذه الإحداثيات في جدول (مثلاً: location_histories) هنا قبل إطلاق الحدث.

    //     // إطلاق الحدث لبث الإحداثيات إلى شاشة العائلة/الطبيب
    //     event(new LocationUpdated(
    //         $validated['patient_id'],
    //         $validated['latitude'],
    //         $validated['longitude']
    //     ));

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Location updated successfully. The family/doctor should see the new location on the map shortly.'
    //     ]);
    // }
}