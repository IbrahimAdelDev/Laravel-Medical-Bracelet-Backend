<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    // يتأكد إن اللي بيحاول يتصل هو صاحب الإشعارات نفسه
    return (int) $user->id === (int) $userId;
});

// 2. قناة استقبال الأوامر لموبايل المريض (عشان الـ GPS)
Broadcast::channel('patient.{patientId}.device', function ($user, $patientId) {
    // يتأكد إن اللي فاتح القناة دي هو المريض بس
    return (int) $user->id === (int) $patientId;
});

// 3. قناة بث الموقع اللحظي (عشان العائلة تشوف المريض)
Broadcast::channel('patient.{patientId}.location', function ($user, $patientId) {
    // هنا مؤقتاً بنسمح بالاتصال، لكن في الـ Production
    // المفروض تتأكد إن اليوزر ده له علاقة بالمريض ده في الداتابيز
    return true; 
});