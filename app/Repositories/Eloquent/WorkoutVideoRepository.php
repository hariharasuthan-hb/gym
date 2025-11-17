<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkoutPlan;
use App\Models\WorkoutVideo;
use App\Models\User;
use App\Repositories\Interfaces\WorkoutVideoRepositoryInterface;
use Illuminate\Support\Collection;

class WorkoutVideoRepository extends BaseRepository implements WorkoutVideoRepositoryInterface
{
    public function __construct(WorkoutVideo $model)
    {
        parent::__construct($model);
    }

    /**
     * Find workout video by ID.
     */
    public function find(int $id): ?WorkoutVideo
    {
        return parent::find($id);
    }

    /**
     * Find workout video by ID or fail.
     */
    public function findOrFail(int $id): WorkoutVideo
    {
        return parent::findOrFail($id);
    }

    /**
     * Create a new workout video.
     */
    public function create(array $data): WorkoutVideo
    {
        return parent::create($data);
    }

    /**
     * Get all videos for a workout plan.
     */
    public function getByWorkoutPlan(WorkoutPlan $workoutPlan, ?User $user = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('workout_plan_id', $workoutPlan->id);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->latest()->get();
    }

    /**
     * Get video by workout plan, user, and exercise name.
     */
    public function getByExercise(
        WorkoutPlan $workoutPlan,
        User $user,
        string $exerciseName
    ): ?WorkoutVideo {
        return $this->model->where('workout_plan_id', $workoutPlan->id)
            ->where('user_id', $user->id)
            ->where('exercise_name', $exerciseName)
            ->first();
    }

    /**
     * Get latest video by workout plan, user, and exercise name.
     */
    public function getLatestByExercise(
        WorkoutPlan $workoutPlan,
        User $user,
        string $exerciseName
    ): ?WorkoutVideo {
        return $this->model->where('workout_plan_id', $workoutPlan->id)
            ->where('user_id', $user->id)
            ->where('exercise_name', $exerciseName)
            ->latest()
            ->first();
    }

    /**
     * Check if all exercises have videos uploaded for today.
     */
    public function checkAllExercisesUploadedToday(
        WorkoutPlan $workoutPlan,
        User $user
    ): bool {
        $exercises = is_array($workoutPlan->exercises) ? $workoutPlan->exercises : [];
        
        if (empty($exercises)) {
            return false;
        }

        $today = now()->toDateString();

        foreach ($exercises as $exercise) {
            $hasVideo = $this->model->where('workout_plan_id', $workoutPlan->id)
                ->where('user_id', $user->id)
                ->where('exercise_name', $exercise)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$hasVideo) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get pending videos for a workout plan.
     */
    public function getPendingVideos(WorkoutPlan $workoutPlan): Collection
    {
        return $this->model->where('workout_plan_id', $workoutPlan->id)
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    /**
     * Get approved videos for a workout plan.
     */
    public function getApprovedVideos(WorkoutPlan $workoutPlan): Collection
    {
        return $this->model->where('workout_plan_id', $workoutPlan->id)
            ->where('status', 'approved')
            ->latest()
            ->get();
    }

    /**
     * Get videos uploaded on a specific date for a workout plan.
     */
    public function getVideosUploadedOnDate(WorkoutPlan $workoutPlan, User $user, string $date): Collection
    {
        return $this->model->where('workout_plan_id', $workoutPlan->id)
            ->where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->latest()
            ->get();
    }
}

