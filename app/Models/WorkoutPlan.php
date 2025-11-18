<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    use HasFactory;

    /**
     * Default video recording duration in seconds
     */
    public static int $defaultVideoRecordingDuration = 30;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainer_id',
        'member_id',
        'plan_name',
        'description',
        'exercises',
        'duration_weeks',
        'start_date',
        'end_date',
        'status',
        'notes',
        'demo_video_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exercises' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_weeks' => 'integer',
        ];
    }

    /**
     * Get the trainer that created this plan.
     */
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get the member this plan is for.
     */
    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by trainer.
     */
    public function scopeForTrainer($query, $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    /**
     * Get the workout videos for this plan.
     */
    public function workoutVideos()
    {
        return $this->hasMany(WorkoutVideo::class);
    }

    /**
     * Get the video recording duration for this plan.
     * Returns the plan-specific duration or the default static duration.
     */
    public function getVideoRecordingDuration(): int
    {
        return $this->video_recording_duration ?? self::$defaultVideoRecordingDuration;
    }
}

