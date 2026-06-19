<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AppNotificationService;

class NotificationController extends Controller
{
    // حقن السيرفيس في الكنترولر
    public function __construct(private AppNotificationService $notificationService) {}

    /**
     * عرض كل الإشعارات
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $this->notificationService->getUserNotifications($request->user());

            return response()->json([
                'status' => 'success',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديد كـ مقروء
     */
    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        try{
            $success = $this->notificationService->markAsRead($request->user(), $notificationId);

        if (!$success) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found or already marked as read.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.'
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}