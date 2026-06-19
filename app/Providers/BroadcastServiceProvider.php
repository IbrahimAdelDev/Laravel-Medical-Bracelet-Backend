<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تحديد الـ middleware بتاع الـ Sanctum 
        // عشان الـ WebSockets تعرف تصادق (Authenticate) اليوزرز عن طريق الـ API
        Broadcast::routes(['middleware' => ['api', 'auth:sanctum']]);

        require base_path('routes/channels.php');
    }
}