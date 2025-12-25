<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberNotificationController extends Controller
{
    public function __construct(
        private readonly InAppNotificationRepositoryInterface $inAppNotificationRepository,
        private readonly NotificationRepositoryInterface $dbNotificationRepository
    ) {
    }

    /**
     * Display member notifications page with pagination.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get paginated notifications
        $perPage = $request->get('per_page', 15);
        
        try {
            $inAppNotifications = $this->inAppNotificationRepository->getForUser($user, $perPage);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get in-app notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $inAppNotifications = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
        
        try {
            $dbNotifications = $this->dbNotificationRepository->getForUser($user, $perPage);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get database notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $dbNotifications = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        // Get unread counts
        try {
            $inAppUnreadCount = $this->inAppNotificationRepository->getUnreadCountForUser($user);
        } catch (\Exception $e) {
            $inAppUnreadCount = 0;
        }
        
        try {
            $dbUnreadCount = $this->dbNotificationRepository->getUnreadCountForUser($user);
        } catch (\Exception $e) {
            $dbUnreadCount = 0;
        }
        
        $totalUnreadCount = $inAppUnreadCount + $dbUnreadCount;

        return view('frontend.member.notifications.index', [
            'inAppNotifications' => $inAppNotifications,
            'dbNotifications' => $dbNotifications,
            'totalUnreadCount' => $totalUnreadCount,
        ]);
    }

    /**
     * Mark in-app notification as read.
     */
    public function markInAppAsRead(Request $request, InAppNotification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (!$notification->recipients()->where('user_id', $user->getKey())->exists()) {
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

