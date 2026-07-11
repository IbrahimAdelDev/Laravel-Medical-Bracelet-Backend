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
use App\Http\Controllers\Patient\PatientVitalsController;
use App\Http\Controllers\Patient\PatientMedicationController;
use App\Http\Controllers\Patient\PatientReportController;
use App\Http\Controllers\Patient\PatientFamilyController;
use App\Http\Controllers\Family\FamilyMonitoringController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\Family\FamilyProfileController;
use App\Http\Controllers\Patient\PatientProfileController;
use App\Http\Controllers\Family\FamilyMedicationController;
use App\Http\Controllers\Doctor\DoctorMedicationController;
use App\Http\Controllers\Patient\DiabetesController;
use App\Http\Controllers\Family\FamilyMedicalHistoryController;
use App\Http\Controllers\Doctor\DoctorMedicalHistoryController;
use App\Http\Controllers\Patient\PatientMedicalHistoryController;
use App\Http\Controllers\Doctor\DoctorPatientController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/user', [RegisterController::class, 'registerNormal']);
Route::post('/register/doctor', [RegisterController::class, 'registerDoctor']);

Route::middleware(['auto.refresh'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/patient/{id}/start-tracking', [LocationTrackingController::class, 'startTracking']);
    Route::post('/patient/{id}/stop-tracking', [LocationTrackingController::class, 'stopTracking']);
    Route::post('/patient/stream-location', [LocationTrackingController::class, 'streamLocation']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll']);

    Route::post('/patient/sos', [EmergencyController::class, 'triggerSos']);

    // Medication routes
    Route::get('/medication', [MedicationController::class, 'index']);
    Route::get('/medication/missed-doses', [MedicationController::class, 'missedDoses']);
    Route::get('/medications/schedule/today', [MedicationController::class, 'todaySchedule']);
    Route::patch('/medications/doses/{doseId}/take', [MedicationController::class, 'takeDose']);

    // family confirm medication taken
    Route::patch('/family/patients/{patientId}/doses/{doseId}/take', [MedicationController::class, 'familyConfirmDose']);


    // Patient-specific routes
    Route::get('/patient/vitals/latest', [PatientVitalsController::class, 'latest']);
    
    Route::post('/patient/medications/{id}/confirm', [PatientMedicationController::class, 'confirm']);
    
    Route::get('/patient/reports/weekly', [PatientReportController::class, 'weekly']);

    // patient family management routes
    Route::post('/patient/family/search', [PatientFamilyController::class, 'searchByEmail']);
    Route::post('/patient/family/{familyId}/add', [PatientFamilyController::class, 'addFamilyMember']);

    // family patient monitoring routes
    Route::get('/family/patient/{id}/status', [FamilyMonitoringController::class, 'patientStatus']);
    Route::get('/family/patients', [FamilyMonitoringController::class, 'index']);
    Route::get('/family/patients/{id}/doses/today', [FamilyMedicationController::class, 'dailyDoses']);

    // manage alerts
    Route::get('/family/patients/{patientId}/alerts/unresolved', [AlertController::class, 'unresolved']);
    
    Route::patch('/patient/alerts/resolve', [AlertController::class, 'resolve']);
    Route::patch('/family/patients/{patientId}/alerts/resolve', [AlertController::class, 'familyresolve']);

    // profile management routes for patients and families
    Route::put('/patient/profile', [PatientProfileController::class, 'updateMyProfile']);
    Route::put('/family/patient/{id}/profile', [FamilyProfileController::class, 'updatePatientProfile']);
    
    // make sure to add the toggle self-management route for families
    Route::patch('/family/patient/{id}/toggle-manage', [FamilyProfileController::class, 'toggleManage']);

    // check diabetes risk route for patients
    Route::post('/patient/diabetes-check', [DiabetesController::class, 'check']);

    // medical history routes for patients and families
    Route::get('/patient/medical-history', [PatientMedicalHistoryController::class, 'index']);
    Route::get('/family/patient/{id}/medical-history', [FamilyMedicalHistoryController::class, 'show']);

    Route::delete('/patient/family/{id}/unlink', [PatientFamilyController::class, 'unlinkFamily']);

    Route::get('test-tokens', function (Request $request) {
        $user = $request->user();
        
        return response()->json([
            'user_id' => $user->id,
            'access_token' => $request->header('New-Access-Token'),
            'refresh_token' => $request->header('X-Refresh-Token'),
            'user' => $user,
        ]);
    });

    // routes for doctors and their dashboard
    Route::middleware(['role:doctor,admin'])->group(function () {
        // Doctor Web Dashboard
        Route::get('/doctor/dashboard/stats', [DashboardController::class, 'stats']);

        Route::get('/doctor/patients', [PatientController::class, 'index']);
        // patient management routes for doctors
        Route::get('/doctor/patients/available', [PatientController::class, 'searchByEmail']);
        Route::post('/doctor/patients/{id}/add', [PatientController::class, 'addPatient']);

        Route::get('/doctor/patients/{id}', [PatientController::class, 'show']);
        // Route::put('/doctor/patients/{id}', [PatientController::class, 'update']); // doctor change patient info!!!
        Route::post('/doctor/patients/{id}/notes', [PatientController::class, 'addNote']);
        
        Route::get('/doctor/patients/{id}/vitals-history', [PatientController::class, 'vitalsHistory']); // حسب للي هيتبعت بالظبط لسه
        Route::get('/doctor/patients/{id}/timeline', [PatientController::class, 'timeline']);

        Route::put('/profile', [ProfileController::class, 'updateProfile']);

        
    
        // Medication routes
        Route::post('/medication', [MedicationController::class, 'store']);
        Route::put('/medication/{medication}', [MedicationController::class, 'update']);
        Route::delete('/medication/{medication}', [MedicationController::class, 'destroy']);
        Route::get('/doctor/patients/{patientId}/medications', [DoctorMedicationController::class, 'index']);

        // Medical History routes
        Route::get('/doctor/patients/{patientId}/conditions', [DoctorMedicalHistoryController::class, 'getConditions']);
        Route::post('/doctor/patients/{patientId}/conditions', [DoctorMedicalHistoryController::class, 'storeCondition']);
        Route::put('/doctor/patients/{patientId}/conditions/{condition_id}', [DoctorMedicalHistoryController::class, 'updateCondition']);
        Route::delete('/doctor/patients/{patientId}/conditions/{condition_id}', [DoctorMedicalHistoryController::class, 'destroyCondition']);
        
        Route::get('/doctor/patients/{patientId}/medication-history', [DoctorMedicalHistoryController::class, 'getMedicationHistory']);
        // Route::post('/medication-history', [DoctorMedicalHistoryController::class, 'storeMedicationHistory']);
        Route::put('/doctor/patients/{patientId}/medication-history/{med_id}/stop', [DoctorMedicalHistoryController::class, 'stopMedication']);

        Route::delete('/doctor/patients/{id}/unlink', [DoctorPatientController::class, 'unlinkPatient']);

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
        event(new LocationUpdated(11, 30.123, 31.456));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Event fired! Check Reverb logs now.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error_message' => $e->getMessage()
        ], 500);
    }
});