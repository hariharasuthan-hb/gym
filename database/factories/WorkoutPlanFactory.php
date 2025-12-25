<?php

namespace Database\Factories;

use App\Models\WorkoutPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutPlan>
 */
class WorkoutPlanFactory extends Factory
{
    protected $model = WorkoutPlan::class;

    public function definition(): array
    {
        return [
            'trainer_id' => User::factory(),
            'member_id' => User::factory(),
            'plan_name' => fake()->words(3, true) . ' Workout',
            'description' => fake()->paragraph(),
            'exercises' => [
                ['name' => 'Push-ups', 'sets' => 3, 'reps' => 10],
                ['name' => 'Squats', 'sets' => 3, 'reps' => 15],
            ],
            'duration_weeks' => fake()->numberBetween(4, 12),
            'start_date' => now(),
            'end_date' => now()->addWeeks(8),
            'status' => 'active',
            'notes' => fake()->sentence(),
            'demo_video_path' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'end_date' => now()->subDays(1),
        ]);
    }
}

