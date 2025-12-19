<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Models\DietPlan;
use App\Models\User;
use App\Models\WorkoutVideo;
use App\Repositories\Interfaces\ActivityRepositoryInterface;
use Carbon\Carbon;

class ActivityRepository implements ActivityRepositoryInterface
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
    ): array {
        $activities = [];
        $allTypes = empty($types) ? ['workout', 'video', 'diet'] : $types;

        // Get workout activities if requested
        if (in_array('workout', $allTypes)) {
            $activities = array_merge($activities, $this->getWorkoutActivities($user, $limit, $fromDate, $toDate));
        }

        // Get video activities if requested
        if (in_array('video', $allTypes)) {
            $activities = array_merge($activities, $this->getVideoActivities($user, $limit, $fromDate, $toDate));
        }

        // Get diet activities if requested
        if (in_array('diet', $allTypes)) {
            $activities = array_merge($activities, $this->getDietActivities($user, $limit, $fromDate, $toDate));
        }

        // Sort all activities by timestamp (most recent first)
        usort($activities, function ($a, $b) {
            return $b['timestamp']->timestamp <=> $a['timestamp']->timestamp;
        });

        // Return only the most recent activities
        return array_slice($activities, 0, $limit);
    }

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
    ): array {
        $activities = [];

        $query = ActivityLog::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNotNull('workout_summary')
                    ->orWhereNotNull('check_out_time');
            });

        // Apply date filters
        if ($fromDate) {
            $query->where('date', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->where('date', '<=', $toDate->toDateString());
        }

        $activityLogs = $query
            ->latest('date')
            ->latest('check_out_time')
            ->latest('check_in_time')
            ->limit($limit)
            ->get();

        foreach ($activityLogs as $log) {
            // Use check_out_time if available, otherwise check_in_time, otherwise created_at
            // Since check_in_time and check_out_time are time fields, combine with date
            if ($log->check_out_time) {
                $timestamp = $log->date->copy()->setTimeFromTimeString($log->check_out_time->format('H:i:s'));
            } elseif ($log->check_in_time) {
                $timestamp = $log->date->copy()->setTimeFromTimeString($log->check_in_time->format('H:i:s'));
            } else {
                $timestamp = $log->created_at;
            }
            
            $activities[] = [
                'type' => 'workout',
                'title' => $log->workout_summary ? 'Workout completed' : 'Checked out',
                'description' => $log->workout_summary ?: 'Gym session completed',
                'timestamp' => $timestamp,
                'icon' => 'workout',
                'color' => 'blue',
            ];
        }

        return $activities;
    }

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
    ): array {
        $activities = [];

        $query = WorkoutVideo::where('user_id', $user->id);

        // Apply date filters
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $workoutVideos = $query
            ->latest('created_at')
            ->limit($limit)
            ->get();

        foreach ($workoutVideos as $video) {
            $activities[] = [
                'type' => 'video',
                'title' => 'Workout video uploaded',
                'description' => $video->exercise_name ?? 'Exercise video',
                'timestamp' => $video->created_at,
                'icon' => 'video',
                'color' => 'green',
            ];
        }

        return $activities;
    }

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
    ): array {
        $activities = [];

        $query = DietPlan::where('member_id', $user->id);

        // Default to last 30 days if no date range specified
        if (!$fromDate && !$toDate) {
            $query->where(function ($q) {
                $q->where('created_at', '>=', now()->subDays(30))
                    ->orWhere('updated_at', '>=', now()->subDays(30));
            });
        } else {
            if ($fromDate) {
                $query->where(function ($q) use ($fromDate) {
                    $q->where('created_at', '>=', $fromDate)
                        ->orWhere('updated_at', '>=', $fromDate);
                });
            }
            if ($toDate) {
                $query->where(function ($q) use ($toDate) {
                    $q->where('created_at', '<=', $toDate)
                        ->orWhere('updated_at', '<=', $toDate);
                });
            }
        }

        $dietPlans = $query
            ->latest('updated_at')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        foreach ($dietPlans as $plan) {
            $timestamp = $plan->updated_at->gt($plan->created_at) ? $plan->updated_at : $plan->created_at;
            $activities[] = [
                'type' => 'diet',
                'title' => $plan->updated_at->gt($plan->created_at) ? 'Diet plan updated' : 'Diet plan assigned',
                'description' => $plan->plan_name ?? 'Diet plan',
                'timestamp' => $timestamp,
                'icon' => 'diet',
                'color' => 'purple',
            ];
        }

        return $activities;
    }
}

