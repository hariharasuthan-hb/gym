<?php

namespace App\Events;

use App\Models\WorkoutPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkoutPlanCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkoutPlan $workoutPlan
    ) {
    }
}
