<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Events\RealTimeNotificationBroadcast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppNotificationService
{
    /**
     * دالة شاملة لإرسال أي إشعار في النظام
     */
    public function send(
        $users, // ممكن يكون كوليكشن يوزرز أو يوزر واحد
        string $title, 
        string $message, 
        string $type = 'general', // alert, system, reminder, etc.
        array $payload = null, 
        int $relatedId = null, 
        string $relatedModel = null
    ): void {

        if ($users instanceof User) {
            $users = collect([$users]);
        }

        // لو مفيش يوزرز مفيش داعي نكمل
        if ($users->isEmpty()) {
            return;
        }
        
        // 1. إنشاء جسم الإشعار الأساسي في جدول notifications
        $notification = DB::transaction(function () use ($users, $title, $message, $type, $payload, $relatedId, $relatedModel) {
            
            // أ. إنشاء جسم الإشعار
            $notif = Notification::create([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'payload' => $payload,
                'related_id' => $relatedId,
                'related_model' => $relatedModel,
            ]);

            // ب. ربط الإشعار بالمستخدمين
            $notif->users()->attach($users->pluck('id')->toArray());

            return $notif; // إرجاع الإشعار عشان نستخدمه بره الترانزاكشن
        });

        foreach ($users as $user) {
            event(new RealTimeNotificationBroadcast($user->id, $notification));
        }
    }

    public function getUserNotifications(User $user): Collection
    {
        // استخدام notifications.created_at لتجنب أي تعارض (Ambiguity) مع الـ Pivot table
        return $user->notifications()
                    ->orderBy('notifications.created_at', 'desc')
                    ->get();
    }

    /**
     * تحديد إشعار معين كمقروء
     */
    public function markAsRead(User $user, int $notificationId): bool
    {
        // updateExistingPivot بترجع عدد الصفوف اللي اتعدلت
        $updatedRows = $user->notifications()->updateExistingPivot($notificationId, [
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $updatedRows > 0;
    }
}