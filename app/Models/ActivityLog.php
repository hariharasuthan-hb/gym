<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'workout_summary',
        'duration_minutes',
        'calories_burned',
        'exercises_done',
        'performance_metrics',
        'check_in_method',
        'checked_in_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'exercises_done' => 'array',
            'performance_metrics' => 'array',
            'duration_minutes' => 'integer',
            'calories_burned' => 'decimal:2',
        ];
    }

    /**
     * Get the user (member) for this activity log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trainer/admin who checked in the member.
     */
    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Scope a query to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by trainer's members.
     */
    public function scopeForTrainerMembers($query, $trainerId)
    {
        // Get all workout plans for this trainer, then get their members
        $memberIds = \App\Models\WorkoutPlan::where('trainer_id', $trainerId)
            ->pluck('member_id')
            ->unique();
        
        return $query->whereIn('user_id', $memberIds);
    }
}

