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
        // Check for all notification types that can be duplicated
        if (in_array($type, [
            \App\Enums\NotificationType::USER_REGISTRATION,
            \App\Enums\NotificationType::USER_SUBSCRIPTION,
            \App\Enums\NotificationType::USER_UPLOAD,
            \App\Enums\NotificationType::ADMIN_APPROVAL,
            \App\Enums\NotificationType::ADMIN_REJECTION,
            \App\Enums\NotificationType::TRAINER_APPROVAL,
            \App\Enums\NotificationType::TRAINER_REJECTION,
        ])) {
            $timeWindow = match($type) {
                \App\Enums\NotificationType::USER_REGISTRATION => now()->subMinutes(5),
                \App\Enums\NotificationType::USER_SUBSCRIPTION => now()->subMinutes(10),
                \App\Enums\NotificationType::USER_UPLOAD => now()->subMinutes(2), // Short window for uploads
                \App\Enums\NotificationType::ADMIN_APPROVAL => now()->subMinutes(5),
                \App\Enums\NotificationType::ADMIN_REJECTION => now()->subMinutes(5),
                \App\Enums\NotificationType::TRAINER_APPROVAL => now()->subMinutes(5),
                \App\Enums\NotificationType::TRAINER_REJECTION => now()->subMinutes(5),
                default => now()->subMinutes(5),
            };
            
            // Simple duplicate check without lock to prevent timeouts
            try {
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
                } elseif (in_array($type, [
                    \App\Enums\NotificationType::ADMIN_APPROVAL,
                    \App\Enums\NotificationType::ADMIN_REJECTION,
                ]) && isset($additionalData['entity_id']) && isset($additionalData['entity_type'])) {
                    // Check by entity_id and entity_type for approval/rejection
                    $query->whereRaw("JSON_EXTRACT(data, '$.entity_id') = ?", [$additionalData['entity_id']])
                          ->whereRaw("JSON_EXTRACT(data, '$.entity_type') = ?", [$additionalData['entity_type']]);
                } elseif (in_array($type, [
                    \App\Enums\NotificationType::TRAINER_APPROVAL,
                    \App\Enums\NotificationType::TRAINER_REJECTION,
                ]) && isset($additionalData['user_id'])) {
                    // Check by user_id for trainer status changes
                    $query->whereRaw("JSON_EXTRACT(data, '$.user_id') = ?", [$additionalData['user_id']]);
                }
                
                $existingNotification = $query->limit(1)->first();
            } catch (\Exception $e) {
                // If query fails, log and continue (don't block notification)
                \Illuminate\Support\Facades\Log::warning('Duplicate check query failed, proceeding with notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $existingNotification = null;
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
            \App\Enums\NotificationType::USER_UPLOAD,
            \App\Enums\NotificationType::ADMIN_APPROVAL,
            \App\Enums\NotificationType::ADMIN_REJECTION,
            \App\Enums\NotificationType::TRAINER_APPROVAL,
            \App\Enums\NotificationType::TRAINER_REJECTION,
        ])) {
            $timeWindow = match($type) {
                \App\Enums\NotificationType::USER_REGISTRATION => now()->subMinutes(5),
                \App\Enums\NotificationType::USER_SUBSCRIPTION => now()->subMinutes(10),
                \App\Enums\NotificationType::USER_UPLOAD => now()->subMinutes(2),
                \App\Enums\NotificationType::ADMIN_APPROVAL => now()->subMinutes(5),
                \App\Enums\NotificationType::ADMIN_REJECTION => now()->subMinutes(5),
                \App\Enums\NotificationType::TRAINER_APPROVAL => now()->subMinutes(5),
                \App\Enums\NotificationType::TRAINER_REJECTION => now()->subMinutes(5),
                default => now()->subMinutes(5),
            };
            
            // Filter out users who already have this notification
            $users = $users->filter(function ($user) use ($type, $timeWindow, $message, $additionalData) {
                try {
                    $query = $user->notifications()
                        ->where('type', \App\Notifications\DatabaseNotification::class)
                        ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [$type->value])
                        ->where('created_at', '>=', $timeWindow);
                    
                    // For uploads, check by content_path if available
                    if ($type === \App\Enums\NotificationType::USER_UPLOAD && isset($additionalData['content_path'])) {
                        $query->whereRaw("JSON_EXTRACT(data, '$.content_path') = ?", [$additionalData['content_path']]);
                    } elseif (in_array($type, [
                        \App\Enums\NotificationType::ADMIN_APPROVAL,
                        \App\Enums\NotificationType::ADMIN_REJECTION,
                    ]) && isset($additionalData['entity_id']) && isset($additionalData['entity_type'])) {
                        // Check by entity_id and entity_type for approval/rejection
                        $query->whereRaw("JSON_EXTRACT(data, '$.entity_id') = ?", [$additionalData['entity_id']])
                              ->whereRaw("JSON_EXTRACT(data, '$.entity_type') = ?", [$additionalData['entity_type']]);
                    } elseif (in_array($type, [
                        \App\Enums\NotificationType::TRAINER_APPROVAL,
                        \App\Enums\NotificationType::TRAINER_REJECTION,
                    ]) && isset($additionalData['user_id'])) {
                        // Check by user_id for trainer status changes
                        $query->whereRaw("JSON_EXTRACT(data, '$.user_id') = ?", [$additionalData['user_id']]);
                    }
                    
                    $existing = $query->limit(1)->first();
                    
                    if ($existing) {
                        \Illuminate\Support\Facades\Log::info('Duplicate admin notification prevented', [
                            'admin_id' => $user->id,
                            'notification_type' => $type->value,
                        ]);
                        return false;
                    }
                } catch (\Exception $e) {
                    // If query fails, allow notification (don't block)
                    \Illuminate\Support\Facades\Log::warning('Duplicate check failed for admin notification', [
                        'admin_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
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

