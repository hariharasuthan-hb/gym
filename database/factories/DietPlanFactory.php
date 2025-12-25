<?php

namespace Database\Factories;

use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DietPlan>
 */
class DietPlanFactory extends Factory
{
    protected $model = DietPlan::class;

    public function definition(): array
    {
        return [
            'trainer_id' => User::factory(),
            'member_id' => User::factory(),
            'plan_name' => fake()->words(3, true) . ' Diet',
            'description' => fake()->paragraph(),
            'meal_plan' => [
                'breakfast' => fake()->sentence(),
                'lunch' => fake()->sentence(),
                'dinner' => fake()->sentence(),
            ],
            'nutritional_goals' => fake()->sentence(),
            'target_calories' => fake()->numberBetween(1500, 3000),
            'start_date' => now(),
            'end_date' => now()->addWeeks(4),
            'status' => 'active',
            'notes' => fake()->sentence(),
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

