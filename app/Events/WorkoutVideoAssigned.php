<?php

namespace App\Events;

use App\Models\WorkoutPlan;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkoutVideoAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkoutPlan $workoutPlan,
        public User $user
    ) {
    }
}

