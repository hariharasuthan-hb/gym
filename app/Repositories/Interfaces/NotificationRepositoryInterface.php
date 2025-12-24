<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function getUnreadCountForUser(User $user): int;

    public function getForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function markAsRead(User $user, string $notificationId): bool;

    public function markAllAsRead(User $user): int;

    public function getExpiringSubscriptions(int $days = 7): Collection;
}

