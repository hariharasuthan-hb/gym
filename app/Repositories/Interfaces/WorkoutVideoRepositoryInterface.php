<?php

namespace App\Repositories\Interfaces;

use App\Models\WorkoutPlan;
use App\Models\WorkoutVideo;
use App\Models\User;
use Illuminate\Support\Collection;

interface WorkoutVideoRepositoryInterface
{
    /**
     * Get all videos for a workout plan.
     */
    public function getByWorkoutPlan(WorkoutPlan $workoutPlan, ?User $user = null): Collection;

    /**
     * Get video by workout plan, user, and exercise name.
     */
    public function getByExercise(
        WorkoutPlan $workoutPlan,
        User $user,
        string $exerciseName
    ): ?WorkoutVideo;

    /**
     * Get latest video by workout plan, user, and exercise name.
     */
    public function getLatestByExercise(
        WorkoutPlan $workoutPlan,
        User $user,
        string $exerciseName
    ): ?WorkoutVideo;

    /**
     * Check if all exercises have videos uploaded for today.
     */
    public function checkAllExercisesUploadedToday(
        WorkoutPlan $workoutPlan,
        User $user
    ): bool;

    /**
     * Check if all exercises have approved videos for today (no need to upload again).
     */
    public function checkAllExercisesApprovedForToday(
        WorkoutPlan $workoutPlan,
        User $user
    ): bool;

    /**
     * Get pending videos for a workout plan.
     */
    public function getPendingVideos(WorkoutPlan $workoutPlan): Collection;

    /**
     * Get approved videos for a workout plan.
     */
    public function getApprovedVideos(WorkoutPlan $workoutPlan): Collection;

    /**
     * Get videos uploaded on a specific date for a workout plan.
     */
    public function getVideosUploadedOnDate(WorkoutPlan $workoutPlan, User $user, string $date): Collection;
}

