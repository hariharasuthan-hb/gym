<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_plan_id',
        'user_id',
        'exercise_name',
        'video_path',
        'duration_seconds',
        'status',
        'trainer_feedback',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the workout plan this video belongs to.
     */
    public function workoutPlan()
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    /**
     * Get the user (member) who uploaded the video.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trainer who reviewed the video.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope a query to only include pending videos.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved videos.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected videos.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
