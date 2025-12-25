<?php

namespace App\Providers;

use App\Events\EntityApproved;
use App\Events\EntityRejected;
use App\Events\TrainerStatusChanged;
use App\Events\UserRegistered;
use App\Events\UserSubscribed;
use App\Events\UserUploadedContent;
use App\Events\WorkoutPlanCreated;
use App\Events\WorkoutVideoAssigned;
use Illuminate\Auth\Events\Registered;
use App\Listeners\SendEntityApprovalNotification;
use App\Listeners\SendEntityRejectionNotification;
use App\Listeners\SendLaravelRegisteredNotification;
use App\Listeners\SendTrainerStatusNotification;
use App\Listeners\SendUserRegistrationNotification;
use App\Listeners\SendUserSubscriptionNotification;
use App\Listeners\SendUserUploadNotification;
use App\Listeners\SendWorkoutPlanCreatedNotification;
use App\Listeners\SendWorkoutVideoNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendLaravelRegisteredNotification::class,
        ],
        UserRegistered::class => [
            SendUserRegistrationNotification::class,
        ],
        UserSubscribed::class => [
            SendUserSubscriptionNotification::class,
        ],
        UserUploadedContent::class => [
            SendUserUploadNotification::class,
        ],
        WorkoutVideoAssigned::class => [
            SendWorkoutVideoNotification::class,
        ],
        WorkoutPlanCreated::class => [
            SendWorkoutPlanCreatedNotification::class,
        ],
        TrainerStatusChanged::class => [
            SendTrainerStatusNotification::class,
        ],
        EntityApproved::class => [
            SendEntityApprovalNotification::class,
        ],
        EntityRejected::class => [
            SendEntityRejectionNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
        
        // Register model observers
        \App\Models\Subscription::observe(\App\Observers\SubscriptionObserver::class);
    }
}

