<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $notifications = $this->notificationRepository->getForUser($user, 15);

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications->items(),
                'unread_count' => $this->notificationRepository->getUnreadCountForUser($user),
            ]);
        }

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = Auth::user();
        $success = $this->notificationRepository->markAsRead($user, $notificationId);

        if (!$success) {
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        return response()->json([
            'success' => true,
            'unread_count' => $this->notificationRepository->getUnreadCountForUser($user),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationRepository->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'count' => $count,
            'unread_count' => 0,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationRepository->getUnreadCountForUser($user);

        return response()->json(['count' => $count]);
    }
}

