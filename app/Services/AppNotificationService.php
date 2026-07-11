<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Events\RealTimeNotificationBroadcast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppNotificationService
{
    public function send(
        $users,
        string $title, 
        string $message, 
        string $type = 'general', 
        array $payload = null, 
        int $relatedId = null, 
        string $relatedModel = null
    ): void {

        if ($users instanceof User) {
            $users = collect([$users]);
        }

        if ($users->isEmpty()) {
            return;
        }
        
        $notification = DB::transaction(function () use ($users, $title, $message, $type, $payload, $relatedId, $relatedModel) {
            
            $notif = Notification::create([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'payload' => $payload,
                'related_id' => $relatedId,
                'related_model' => $relatedModel,
            ]);

            $notif->users()->attach($users->pluck('id')->toArray());

            return $notif; 
        });

        foreach ($users as $user) {
            event(new RealTimeNotificationBroadcast($user->id, $notification));
        }
    }

    public function getUserNotifications(User $user, int $perPage = 20): array
    {
        $notifications = $user->notifications()
            ->withPivot('is_read', 'read_at') 
            ->orderBy('notifications.created_at', 'desc')
            ->paginate($perPage);

        $today = [];
        $yesterday = [];
        $older = [];

        foreach ($notifications->items() as $notification) {
            $createdAt = \Carbon\Carbon::parse($notification->created_at);

            $data = [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'payload' => $notification->payload,
                'is_read' => (bool) $notification->pivot->is_read,
                'read_at' => $notification->pivot->read_at,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'time_ago' => $createdAt->diffForHumans(), 
            ];

            if ($createdAt->isToday()) {
                $today[] = $data;
            } elseif ($createdAt->isYesterday()) {
                $yesterday[] = $data;
            } else {
                $older[] = $data;
            }
        }

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

    public function markAllAsRead(int $userId): bool
    {
        $user = User::find($userId);
        $updatedRows = $user->notifications()
            ->wherePivot('is_read', false) 
            ->updateExistingPivot($user->notifications->pluck('id'), [
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $updatedRows > 0;
    }

    public function markAsRead(User $user, int $notificationId): bool
    {
        $updatedRows = $user->notifications()->updateExistingPivot($notificationId, [
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $updatedRows > 0;
    }
}