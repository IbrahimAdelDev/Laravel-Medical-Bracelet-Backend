<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\LocationTrackingController;
use App\Http\Controllers\NotificationController;
use App\Events\LocationUpdated;

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
    
    // routes for doctors and their dashboard
    Route::middleware(['role:doctor,admin'])->group(function () {
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


Route::post('/sensor/sync', [SensorController::class, 'store']);

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