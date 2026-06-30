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

    public function getUserNotifications(User $user, int $perPage = 20): array
    {
        // 1. الباجينيشن مع جلب بيانات الـ Pivot
        $notifications = $user->notifications()
            ->withPivot('is_read', 'read_at') // ضروري عشان نجيب حالة القراءة لكل يوزر
            ->orderBy('notifications.created_at', 'desc')
            ->paginate($perPage);

        $today = [];
        $yesterday = [];
        $older = [];

        // 2. تقسيم الإشعارات للصفحة الحالية فقط (High Performance)
        foreach ($notifications->items() as $notification) {
            $createdAt = \Carbon\Carbon::parse($notification->created_at);

            $data = [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'payload' => $notification->payload,
                // بنقرا حالة القراءة من الـ Pivot Table
                'is_read' => (bool) $notification->pivot->is_read,
                'read_at' => $notification->pivot->read_at,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'time_ago' => $createdAt->diffForHumans(), // إضافة ممتازة لـ UI الموبايل (منذ 5 دقائق)
            ];

            // التقسيم الزمني
            if ($createdAt->isToday()) {
                $today[] = $data;
            } elseif ($createdAt->isYesterday()) {
                $yesterday[] = $data;
            } else {
                $older[] = $data;
            }
        }

        // 3. إرجاع الداتا جاهزة للكنترولر
        return [
            'list' => [
                'today' => $today,
                'yesterday' => $yesterday,
                'older' => $older,
            ],
            'pagination' => [
                'total_items' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
            ]
        ];
    }

    /**
     * تحديد جميع إشعارات المستخدم كمقروءة دفعة واحدة
     */
    public function markAllAsRead(int $userId): bool
    {
        $user = User::find($userId);
        $updatedRows = $user->notifications()
            ->wherePivot('is_read', false) // نحدث اللي مش مقروء بس عشان نوفر وقت الداتابيز
            ->updateExistingPivot($user->notifications->pluck('id'), [
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $updatedRows > 0;
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