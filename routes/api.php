<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/user', [RegisterController::class, 'registerNormal']);
Route::post('/register/doctor', [RegisterController::class, 'registerDoctor']);

Route::middleware(['auto.refresh'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // routes for doctors and their dashboard
    Route::middleware(['role:doctor'])->group(function () {
        Route::post('/register/secretary', [RegisterController::class, 'registerSecretary']);
    });

    // routes shared by patients and their families (regular users)
    Route::middleware(['role:user'])->group(function () {
    });

    // routes for secretaries
    Route::middleware(['role:secretary'])->group(function () {
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
