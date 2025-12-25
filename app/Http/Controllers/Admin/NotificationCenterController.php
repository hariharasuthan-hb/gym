<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationCenterController extends Controller
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
        private readonly InAppNotificationRepositoryInterface $inAppNotificationRepository,
        private readonly NotificationRepositoryInterface $dbNotificationRepository
    ) {
    }

    public function index()
    {
        $user = Auth::user();

        $announcements = $this->announcementRepository->getRecentForUser($user);

        $inAppNotifications = $this->inAppNotificationRepository->getForUser($user, 15);
        $dbNotifications = $this->dbNotificationRepository->getForUser($user, 15);

        return view('admin.notifications.center', [
            'announcements' => $announcements,
            'notifications' => $inAppNotifications,
            'dbNotifications' => $dbNotifications,
            'showMarkRead' => true, // Allow admins and trainers to mark as read
        ]);
    }

    public function markAsRead(Request $request, InAppNotification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $notification->recipients()->where('user_id', $user->getKey())->exists()) {
            abort(403);
        }

        $notification->markAsReadFor($user);

        if ($request->wantsJson() || $request->ajax()) {
            $inAppCount = $this->inAppNotificationRepository->getUnreadCountForUser($user);
            $dbCount = $this->dbNotificationRepository->getUnreadCountForUser($user);
            $unreadCount = $inAppCount + $dbCount;

            return response()->json([
                'success' => true,
                'count' => $unreadCount,
            ]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark database notification as read.
     */
    public function markDbAsRead(Request $request, string $notificationId): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $success = $this->dbNotificationRepository->markAsRead($user, $notificationId);

        if ($request->wantsJson() || $request->ajax()) {
            $inAppCount = $this->inAppNotificationRepository->getUnreadCountForUser($user);
            $dbCount = $this->dbNotificationRepository->getUnreadCountForUser($user);
            $unreadCount = $inAppCount + $dbCount;

            return response()->json([
                'success' => $success,
                'count' => $unreadCount,
            ]);
        }

        if ($success) {
            return back()->with('success', 'Notification marked as read.');
        }

        return back()->with('error', 'Notification not found.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Mark all unread in-app notifications as read
        $unreadInAppNotifications = $user->receivedNotifications()
            ->wherePivotNull('read_at')
            ->wherePivotNull('dismissed_at')
            ->published()
            ->get();
        
        foreach ($unreadInAppNotifications as $notification) {
            $notification->markAsReadFor($user);
        }

        // Mark all database notifications as read
        $this->dbNotificationRepository->markAllAsRead($user);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count' => 0,
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}

