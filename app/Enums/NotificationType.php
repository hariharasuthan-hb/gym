<?php

namespace App\Enums;

enum NotificationType: string
{
    case USER_REGISTRATION = 'user_registration';
    case USER_SUBSCRIPTION = 'user_subscription';
    case SUBSCRIPTION_CANCELED = 'subscription_canceled';
    case USER_UPLOAD = 'user_upload';
    case WORKOUT_VIDEO = 'workout_video';
    case TRAINER_APPROVAL = 'trainer_approval';
    case TRAINER_REJECTION = 'trainer_rejection';
    case ADMIN_APPROVAL = 'admin_approval';
    case ADMIN_REJECTION = 'admin_rejection';
    case SUBSCRIPTION_EXPIRY_REMINDER = 'subscription_expiry_reminder';
    case DAILY_WORKOUT_DIET_PLAN = 'daily_workout_diet_plan';
    case WORKOUT_PLAN_CREATED = 'workout_plan_created';

    public function getTitle(): string
    {
        return match ($this) {
            self::USER_REGISTRATION => 'Welcome!',
            self::USER_SUBSCRIPTION => 'Subscription Activated',
            self::SUBSCRIPTION_CANCELED => 'Subscription Cancelled',
            self::USER_UPLOAD => 'Upload Successful',
            self::WORKOUT_VIDEO => 'Workout Video',
            self::TRAINER_APPROVAL => 'Trainer Approved',
            self::TRAINER_REJECTION => 'Trainer Rejected',
            self::ADMIN_APPROVAL => 'Approved',
            self::ADMIN_REJECTION => 'Rejected',
            self::SUBSCRIPTION_EXPIRY_REMINDER => 'Subscription Expiring Soon',
            self::DAILY_WORKOUT_DIET_PLAN => 'Daily Workout & Diet Plan',
            self::WORKOUT_PLAN_CREATED => 'Workout Plan Created',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::USER_REGISTRATION => 'user-plus',
            self::USER_SUBSCRIPTION => 'credit-card',
            self::SUBSCRIPTION_CANCELED => 'alert-circle',
            self::USER_UPLOAD => 'upload',
            self::WORKOUT_VIDEO => 'video',
            self::TRAINER_APPROVAL => 'check-circle',
            self::TRAINER_REJECTION => 'x-circle',
            self::ADMIN_APPROVAL => 'check-circle',
            self::ADMIN_REJECTION => 'x-circle',
            self::SUBSCRIPTION_EXPIRY_REMINDER => 'alert-circle',
            self::DAILY_WORKOUT_DIET_PLAN => 'calendar',
            self::WORKOUT_PLAN_CREATED => 'dumbbell',
        };
    }
}

