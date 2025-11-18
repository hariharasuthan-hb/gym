<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietPlan extends Model
{
    use HasFactory;

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
        'meal_plan',
        'nutritional_goals',
        'target_calories',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal_plan' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'target_calories' => 'integer',
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
     * Determine if the plan has reached its end date.
     */
    public function hasEnded(): bool
    {
        return $this->end_date && now()->startOfDay()->gt($this->end_date->copy()->endOfDay());
    }

    /**
     * Automatically mark expired plans as completed.
     */
    public static function autoCompleteExpired(): void
    {
        $today = now()->startOfDay();

        static::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today)
            ->update(['status' => 'completed']);
    }
}

