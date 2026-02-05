<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for member notifications.
 *
 * Lists in-app and database notifications, mark as read, mark all as read.
 * All endpoints require authentication and member role.
 */
class MemberNotificationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly InAppNotificationRepositoryInterface $inAppNotificationRepository,
        private readonly NotificationRepositoryInterface $notificationRepository
    ) {
    }

    /**
     * List member notifications (in-app + database) with pagination and unread count.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $inAppPaginator = $this->inAppNotificationRepository->getForUser($user, $perPage);
        $dbPaginator = $this->notificationRepository->getForUser($user, $perPage);

        $inAppItems = $inAppPaginator->getCollection()->map(function ($notification) {
            $pivot = $notification->pivot;
            return [
                'id' => $notification->id,
                'type' => 'in_app',
                'title' => $notification->title,
                'message' => $notification->message,
                'read_at' => $pivot?->read_at?->toIso8601String(),
                'published_at' => $notification->published_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $dbItems = $dbPaginator->getCollection()->map(function ($notification) {
            $data = $notification->data ?? [];
            return [
                'id' => $notification->id,
                'type' => 'database',
                'title' => $data['title'] ?? null,
                'message' => $data['message'] ?? null,
                'notification_type' => $data['type'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'icon' => $data['icon'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $unreadCount = $this->inAppNotificationRepository->getUnreadCountForUser($user)
            + $this->notificationRepository->getUnreadCountForUser($user);

        return $this->successResponse('Notifications retrieved successfully', [
            'unread_count' => $unreadCount,
            'in_app' => [
                'items' => $inAppItems,
                'pagination' => [
                    'current_page' => $inAppPaginator->currentPage(),
                    'per_page' => $inAppPaginator->perPage(),
                    'total' => $inAppPaginator->total(),
                    'last_page' => $inAppPaginator->lastPage(),
                    'from' => $inAppPaginator->firstItem(),
                    'to' => $inAppPaginator->lastItem(),
                ],
            ],
            'database' => [
                'items' => $dbItems,
                'pagination' => [
                    'current_page' => $dbPaginator->currentPage(),
                    'per_page' => $dbPaginator->perPage(),
                    'total' => $dbPaginator->total(),
                    'last_page' => $dbPaginator->lastPage(),
                    'from' => $dbPaginator->firstItem(),
                    'to' => $dbPaginator->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Mark an in-app notification as read.
     *
     * @return JsonResponse
     */
    public function markInAppAsRead(Request $request, InAppNotification $notification): JsonResponse
    {
        $user = $request->user();

        if (!$notification->recipients()->where('user_id', $user->getKey())->exists()) {
            return $this->errorResponse('Notification not found or not assigned to you.', 404);
        }

        $notification->markAsReadFor($user);

        $unreadCount = $this->inAppNotificationRepository->getUnreadCountForUser($user)
            + $this->notificationRepository->getUnreadCountForUser($user);

        return $this->successResponse('Notification marked as read.', [
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a database (Laravel) notification as read.
     *
     * @param string $id Notification UUID
     * @return JsonResponse
     */
    public function markDatabaseAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $success = $this->notificationRepository->markAsRead($user, $id);

        if (!$success) {
            return $this->errorResponse('Notification not found.', 404);
        }

        $unreadCount = $this->inAppNotificationRepository->getUnreadCountForUser($user)
            + $this->notificationRepository->getUnreadCountForUser($user);

        return $this->successResponse('Notification marked as read.', [
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadInApp = $user->receivedNotifications()
            ->wherePivotNull('read_at')
            ->wherePivotNull('dismissed_at')
            ->get();

        foreach ($unreadInApp as $notification) {
            $notification->markAsReadFor($user);
        }

        $this->notificationRepository->markAllAsRead($user);

        return $this->successResponse('All notifications marked as read.', [
            'unread_count' => 0,
        ]);
    }
}
