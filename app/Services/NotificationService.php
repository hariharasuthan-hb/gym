<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function send(User $user, NotificationType $type, string $message, ?string $actionUrl = null, array $additionalData = []): void
    {
        // Check if a similar notification already exists to prevent duplicates
        // Check for USER_REGISTRATION, USER_SUBSCRIPTION, and USER_UPLOAD types
        if (in_array($type, [
            \App\Enums\NotificationType::USER_REGISTRATION,
            \App\Enums\NotificationType::USER_SUBSCRIPTION,
            \App\Enums\NotificationType::USER_UPLOAD
        ])) {
            $timeWindow = match($type) {
                \App\Enums\NotificationType::USER_REGISTRATION => now()->subMinutes(5),
                \App\Enums\NotificationType::USER_SUBSCRIPTION => now()->subMinutes(10),
                \App\Enums\NotificationType::USER_UPLOAD => now()->subMinutes(2), // Short window for uploads
                default => now()->subMinutes(5),
            };
            
            // Use database transaction with lock to prevent race conditions
            try {
                $existingNotification = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $type, $timeWindow, $additionalData) {
                    // For registration, check by user name in message
                    // For subscription, check by subscription_id if available
                    $query = $user->notifications()
                        ->where('type', \App\Notifications\DatabaseNotification::class)
                        ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [$type->value])
                        ->where('created_at', '>=', $timeWindow)
                        ->lockForUpdate(); // Lock to prevent race conditions
                    
                    if ($type === \App\Enums\NotificationType::USER_REGISTRATION) {
                        $query->whereRaw("JSON_EXTRACT(data, '$.message') LIKE ?", ["%{$user->name}%"]);
                    } elseif ($type === \App\Enums\NotificationType::USER_SUBSCRIPTION && isset($additionalData['subscription_id'])) {
                        $query->whereRaw("JSON_EXTRACT(data, '$.subscription_id') = ?", [$additionalData['subscription_id']]);
                    } elseif ($type === \App\Enums\NotificationType::USER_UPLOAD && isset($additionalData['content_path'])) {
                        $query->whereRaw("JSON_EXTRACT(data, '$.content_path') = ?", [$additionalData['content_path']]);
                    }
                    
                    return $query->first();
                });
            } catch (\Exception $e) {
                // If locking fails, fall back to regular check (but log it)
                \Illuminate\Support\Facades\Log::warning('Failed to acquire lock for duplicate check, using regular check', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                
                $query = $user->notifications()
                    ->where('type', \App\Notifications\DatabaseNotification::class)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [$type->value])
                    ->where('created_at', '>=', $timeWindow);
                
                if ($type === \App\Enums\NotificationType::USER_REGISTRATION) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.message') LIKE ?", ["%{$user->name}%"]);
                } elseif ($type === \App\Enums\NotificationType::USER_SUBSCRIPTION && isset($additionalData['subscription_id'])) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.subscription_id') = ?", [$additionalData['subscription_id']]);
                } elseif ($type === \App\Enums\NotificationType::USER_UPLOAD && isset($additionalData['content_path'])) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.content_path') = ?", [$additionalData['content_path']]);
                }
                
                $existingNotification = $query->first();
            }
            
            if ($existingNotification) {
                \Illuminate\Support\Facades\Log::info('Duplicate notification prevented', [
                    'user_id' => $user->id,
                    'notification_type' => $type->value,
                    'existing_notification_id' => $existingNotification->id,
                ]);
                return;
            }
        }
        
        $user->notify(new DatabaseNotification($type, $message, $actionUrl, $additionalData));
    }

    public function sendToMany(array|\Illuminate\Support\Collection $users, NotificationType $type, string $message, ?string $actionUrl = null, array $additionalData = []): void
    {
        if (empty($users)) {
            return;
        }

        // Convert to collection if array, ensure we have User models
        if (is_array($users)) {
            $users = collect($users)->filter(fn($user) => $user instanceof User);
        }

        if ($users->isEmpty()) {
            return;
        }

        // For admin notifications, check for duplicates per admin user
        // This prevents duplicate notifications when events are dispatched multiple times
        if (in_array($type, [
            \App\Enums\NotificationType::USER_REGISTRATION,
            \App\Enums\NotificationType::USER_SUBSCRIPTION,
            \App\Enums\NotificationType::USER_UPLOAD
        ])) {
            $timeWindow = match($type) {
                \App\Enums\NotificationType::USER_REGISTRATION => now()->subMinutes(5),
                \App\Enums\NotificationType::USER_SUBSCRIPTION => now()->subMinutes(10),
                \App\Enums\NotificationType::USER_UPLOAD => now()->subMinutes(2),
                default => now()->subMinutes(5),
            };
            
            // Filter out users who already have this notification
            $users = $users->filter(function ($user) use ($type, $timeWindow, $message, $additionalData) {
                $query = $user->notifications()
                    ->where('type', \App\Notifications\DatabaseNotification::class)
                    ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [$type->value])
                    ->where('created_at', '>=', $timeWindow);
                
                // For uploads, check by content_path if available
                if ($type === \App\Enums\NotificationType::USER_UPLOAD && isset($additionalData['content_path'])) {
                    $query->whereRaw("JSON_EXTRACT(data, '$.content_path') = ?", [$additionalData['content_path']]);
                }
                
                $existing = $query->first();
                
                if ($existing) {
                    \Illuminate\Support\Facades\Log::info('Duplicate admin notification prevented', [
                        'admin_id' => $user->id,
                        'notification_type' => $type->value,
                    ]);
                    return false;
                }
                
                return true;
            });
            
            if ($users->isEmpty()) {
                return;
            }
        }

        $notification = new DatabaseNotification($type, $message, $actionUrl, $additionalData);
        Notification::send($users, $notification);
    }

    /**
     * Get all admin users.
     */
    public function getAdmins(): \Illuminate\Support\Collection
    {
        return User::role('admin')->get();
    }

    /**
     * Get all trainer users.
     */
    public function getTrainers(): \Illuminate\Support\Collection
    {
        return User::role('trainer')->get();
    }

    /**
     * Get trainer assigned to a member (if any).
     */
    public function getMemberTrainer(User $member): ?User
    {
        $workoutPlan = \App\Models\WorkoutPlan::where('member_id', $member->id)
            ->where('status', 'active')
            ->with('trainer')
            ->first();
        
        return $workoutPlan?->trainer;
    }

    /**
     * Send notification to user, admins, and optionally trainer.
     */
    public function sendToUserAndAdmins(
        User $user,
        NotificationType $type,
        string $userMessage,
        ?string $adminMessage = null,
        ?string $actionUrl = null,
        array $additionalData = [],
        bool $includeTrainer = false
    ): void {
        // Send to user
        $this->send($user, $type, $userMessage, $actionUrl, $additionalData);

        // Send to admins
        $admins = $this->getAdmins();
        if ($admins->isNotEmpty()) {
            $adminMsg = $adminMessage ?? $userMessage;
            $this->sendToMany($admins, $type, $adminMsg, $actionUrl, $additionalData);
        }

        // Send to trainer if requested and trainer exists
        if ($includeTrainer) {
            $trainer = $this->getMemberTrainer($user);
            if ($trainer) {
                $trainerMsg = $adminMessage ?? $userMessage;
                $this->send($trainer, $type, $trainerMsg, $actionUrl, $additionalData);
            }
        }
    }
}

