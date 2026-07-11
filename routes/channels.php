<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('patient.{patientId}.device', function ($user, $patientId) {
    return (int) $user->id === (int) $patientId;
});

Broadcast::channel('patient.{patientId}.location', function ($user, $patientId) {
    return true; 
});