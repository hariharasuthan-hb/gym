<?php

namespace App\Repositories\Eloquent;

use App\Models\InAppNotification;
use App\Models\User;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class InAppNotificationRepository extends BaseRepository implements InAppNotificationRepositoryInterface
{
    public function __construct(InAppNotification $model)
    {
        parent::__construct($model);
    }

    public function createNotification(array $data): InAppNotification
    {
        return $this->create($data);
    }

    public function updateNotification(InAppNotification $notification, array $data): bool
    {
        return $notification->update($data);
    }

    public function deleteNotification(InAppNotification $notification): bool
    {
        return $notification->delete();
    }

    public function queryForDataTable(array $filters = []): QueryBuilder
    {
        // Query from notifications table (Laravel database notifications)
        $query = \Illuminate\Support\Facades\DB::table('notifications')
            ->select([
                'notifications.id',
                'notifications.type',
                'notifications.notifiable_type',
                'notifications.notifiable_id',
                'notifications.data',
                'notifications.read_at',
                'notifications.created_at',
                'notifications.updated_at',
            ])
            ->where('notifications.type', 'App\Notifications\DatabaseNotification');
        
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySearch($query, $filters['search'] ?? null);

        return $query;
    }

    public function getForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        if ($user->hasRole('admin')) {
            return $this->model->newQuery()
                ->orderByDesc('published_at')
                ->paginate($perPage);
        }

        return $user->receivedNotifications()
            ->wherePivotNull('dismissed_at')
            ->published()
            ->orderByDesc('in_app_notifications.published_at')
            ->paginate($perPage);
    }

    public function getUnreadCountForUser(User $user): int
    {
        if ($user->hasRole('admin')) {
            return $this->model->newQuery()
                ->published()
                ->count();
        }

        return $user->receivedNotifications()
            ->wherePivotNull('dismissed_at')
            ->wherePivotNull('read_at')
            ->published()
            ->count();
    }

    public function getStatusOptions(): array
    {
        return [
            InAppNotification::STATUS_DRAFT => 'Draft',
            InAppNotification::STATUS_SCHEDULED => 'Scheduled',
            InAppNotification::STATUS_PUBLISHED => 'Published',
            InAppNotification::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function getAudienceOptions(): array
    {
        return [
            InAppNotification::AUDIENCE_ALL => 'Everyone',
            InAppNotification::AUDIENCE_TRAINER => 'Trainers',
            InAppNotification::AUDIENCE_MEMBER => 'Members',
            InAppNotification::AUDIENCE_USER => 'Specific User',
        ];
    }

    public function getReadStatusOptions(): array
    {
        return [
            'read' => 'Read',
            'unread' => 'Unread',
        ];
    }

    protected function applyFilters($query, array $filters)
    {
        // Filter by read status
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'read') {
                $query->whereNotNull('notifications.read_at');
            } elseif ($filters['status'] === 'unread') {
                $query->whereNull('notifications.read_at');
            }
        }

        return $query;
    }

    protected function applySearch($query, mixed $search)
    {
        $search = is_array($search) ? ($search['value'] ?? null) : $search;

        if (!is_string($search) || trim($search) === '') {
            return $query;
        }

        $search = trim($search);

        // Search in data JSON (title and message fields)
        return $query->where(function ($q) use ($search) {
            $q->whereRaw("JSON_EXTRACT(notifications.data, '$.title') LIKE ?", ["%{$search}%"])
                ->orWhereRaw("JSON_EXTRACT(notifications.data, '$.message') LIKE ?", ["%{$search}%"]);
        });
    }
}

