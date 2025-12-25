<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'gateway' => fake()->randomElement(['stripe', 'razorpay']),
            'gateway_customer_id' => 'cus_' . fake()->unique()->bothify('##########'),
            'gateway_subscription_id' => 'sub_' . fake()->unique()->bothify('##########'),
            'status' => 'active',
            'trial_end_at' => null,
            'next_billing_at' => now()->addMonth(),
            'started_at' => now(),
            'canceled_at' => null,
            'metadata' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trialing',
            'trial_end_at' => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'next_billing_at' => now()->subDays(1),
        ]);
    }
}

