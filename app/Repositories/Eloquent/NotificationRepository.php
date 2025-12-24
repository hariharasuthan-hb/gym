<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function getUnreadCountForUser(User $user): int
    {
        try {
            return $user->unreadNotifications()->count();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to get unread notifications count', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function getForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get notifications for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Return empty paginator on error
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function getExpiringSubscriptions(int $days = 7): Collection
    {
        $expiryDate = now()->addDays($days);

        return DB::table('subscriptions')
            ->join('users', 'subscriptions.user_id', '=', 'users.id')
            ->whereIn('subscriptions.status', ['active', 'trialing'])
            ->where(function ($query) use ($expiryDate) {
                $query->where(function ($q) use ($expiryDate) {
                    $q->whereNotNull('subscriptions.next_billing_at')
                        ->where('subscriptions.next_billing_at', '<=', $expiryDate);
                })->orWhere(function ($q) use ($expiryDate) {
                    $q->whereNotNull('subscriptions.trial_end_at')
                        ->where('subscriptions.trial_end_at', '<=', $expiryDate);
                });
            })
            ->where('users.status', 'active')
            ->whereRaw("(subscriptions.metadata IS NULL OR JSON_EXTRACT(subscriptions.metadata, '$.auto_renew') IS NULL OR JSON_EXTRACT(subscriptions.metadata, '$.auto_renew') = false)")
            ->select('subscriptions.*', 'users.id as user_id', 'users.name', 'users.email')
            ->get();
    }
}

