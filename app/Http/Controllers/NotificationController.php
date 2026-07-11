<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AppNotificationService;

class NotificationController extends Controller
{
    public function __construct(private AppNotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 20);
        
            $notificationsData = $this->notificationService->getUserNotifications($request->user(), (int) $perPage);
            return response()->json([
                'status' => 'success',
                'data' => $notificationsData['list'],
                'pagination' => $notificationsData['pagination']
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearAll(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read successfully.'
        ], 200);
    }

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