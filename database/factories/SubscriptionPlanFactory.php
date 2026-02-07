<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'plan_name' => fake()->words(2, true) . ' Plan',
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 29.99, 299.99),
            'duration' => fake()->numberBetween(1, 12),
            'duration_type' => fake()->randomElement(['monthly', 'yearly']),
            'features' => json_encode([
                'feature1' => fake()->sentence(),
                'feature2' => fake()->sentence(),
            ]),
        ];
    }
}

