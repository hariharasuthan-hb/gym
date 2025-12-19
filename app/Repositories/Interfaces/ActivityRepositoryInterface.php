<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Carbon\Carbon;

interface ActivityRepositoryInterface
{
    /**
     * Get recent activities for a user.
     * Combines activity logs, workout videos, and diet plan updates.
     *
     * @param User $user
     * @param int $limit Maximum number of activities to return
     * @param array $types Filter by activity types: 'workout', 'video', 'diet'. Empty array returns all types.
     * @param Carbon|null $fromDate Filter activities from this date
     * @param Carbon|null $toDate Filter activities until this date
     * @return array Array of activity items with type, title, description, timestamp, icon, and color
     */
    public function getRecentActivities(
        User $user, 
        int $limit = 5, 
        array $types = [], 
        ?Carbon $fromDate = null, 
        ?Carbon $toDate = null
    ): array;

    /**
     * Get workout activities (check-ins, check-outs, workout summaries).
     *
     * @param User $user
     * @param int $limit
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return array
     */
    public function getWorkoutActivities(
        User $user, 
        int $limit = 5, 
        ?Carbon $fromDate = null, 
        ?Carbon $toDate = null
    ): array;

    /**
     * Get workout video activities.
     *
     * @param User $user
     * @param int $limit
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return array
     */
    public function getVideoActivities(
        User $user, 
        int $limit = 5, 
        ?Carbon $fromDate = null, 
        ?Carbon $toDate = null
    ): array;

    /**
     * Get diet plan activities (assignments and updates).
     *
     * @param User $user
     * @param int $limit
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return array
     */
    public function getDietActivities(
        User $user, 
        int $limit = 5, 
        ?Carbon $fromDate = null, 
        ?Carbon $toDate = null
    ): array;
}

