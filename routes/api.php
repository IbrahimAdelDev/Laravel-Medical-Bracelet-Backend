<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\LocationTrackingController;
use App\Http\Controllers\NotificationController;
use App\Events\LocationUpdated;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\Doctor\DashboardController;
use App\Http\Controllers\Doctor\PatientController;
use App\Http\Controllers\Doctor\ProfileController;
use App\Http\Controllers\MedicationController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/user', [RegisterController::class, 'registerNormal']);
Route::post('/register/doctor', [RegisterController::class, 'registerDoctor']);

Route::middleware(['auto.refresh'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // 1. العائلة تطلب بدء التتبع
    Route::post('/patient/{id}/start-tracking', [LocationTrackingController::class, 'startTracking']);
    // 2. العائلة تطلب إيقاف التتبع
    Route::post('/patient/{id}/stop-tracking', [LocationTrackingController::class, 'stopTracking']);
    // 3. موبايل المريض يرسل الإحداثيات باستمرار
    Route::post('/patient/stream-location', [LocationTrackingController::class, 'streamLocation']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);

    Route::post('/patient/sos', [EmergencyController::class, 'triggerSos']);

    // Medication routes
    Route::get('/medication', [MedicationController::class, 'index']);
    
    // routes for doctors and their dashboard
    Route::middleware(['role:doctor,admin'])->group(function () {
        // Doctor Web Dashboard
        Route::get('/doctor/dashboard/stats', [DashboardController::class, 'stats']);

        // إدارة المرضى
        Route::get('/doctor/patients', [PatientController::class, 'index']);
        Route::get('/doctor/patients/{id}', [PatientController::class, 'show']);
        // Route::put('/doctor/patients/{id}', [PatientController::class, 'update']); // doctor change patient info!!!
        Route::post('/doctor/patients/{id}/notes', [PatientController::class, 'addNote']);
        
        // المؤشرات والخط الزمني
        Route::get('/doctor/patients/{id}/vitals-history', [PatientController::class, 'vitalsHistory']); // حسب للي هيتبعت بالظبط لسه
        Route::get('/doctor/patients/{id}/timeline', [PatientController::class, 'timeline']);

        // إعدادات الطبيب
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        // Route::put('/update-password', [ProfileController::class, 'updatePassword']); // doctor change password

        Route::get('/medication/missed-doses', [MedicationController::class, 'missedDoses']);
    
        // Medication routes
        Route::post('/medication', [MedicationController::class, 'store']);
        Route::put('/medication/{medication}', [MedicationController::class, 'update']);
        Route::delete('/medication/{medication}', [MedicationController::class, 'destroy']);

        Route::post('/register/secretary', [RegisterController::class, 'registerSecretary']);
    });

    // routes shared by patients and their families (regular users)
    Route::middleware(['role:user,admin'])->group(function () {
    });

    // routes for secretaries
    Route::middleware(['role:secretary,admin'])->group(function () {
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// endpoint to receive sensor data and trigger the LocationUpdated event
Route::post('/sensor/sync', [SensorController::class, 'store']);

// test route to trigger the LocationUpdated event and check if Reverb is receiving it correctly
Route::get('/test-reverb', function () {
    try {
        // بنرمي الإيفنت اللي إحنا متأكدين إن الكلاس بتاعه سليم 100%
        event(new LocationUpdated(11, 30.123, 31.456));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Event fired! Check Reverb logs now.'
        ]);
    } catch (\Exception $e) {
        // لو لارافيل مش عارف يوصل لـ Reverb، الإيرور هيظهر هنا
        return response()->json([
            'status' => 'error',
            'error_message' => $e->getMessage()
        ], 500);
    }
});