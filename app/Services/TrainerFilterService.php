<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Support\Collection;

class TrainerFilterService
{
    /**
     * Get member IDs assigned to a trainer.
     * Returns empty array if user is not a trainer or admin.
     */
    public static function getTrainerMemberIds(?int $trainerId = null): array
    {
        $user = $trainerId ? User::find($trainerId) : auth()->user();
        
        if (!$user || !$user->hasRole('trainer')) {
            return [];
        }

        return WorkoutPlan::where('trainer_id', $user->id)
            ->pluck('member_id')
            ->unique()
            ->toArray();
    }

    /**
     * Check if current user is a trainer.
     */
    public static function isTrainer(?int $userId = null): bool
    {
        $user = $userId ? User::find($userId) : auth()->user();
        
        return $user && $user->hasRole('trainer');
    }

    /**
     * Check if current user is an admin.
     */
    public static function isAdmin(?int $userId = null): bool
    {
        $user = $userId ? User::find($userId) : auth()->user();
        
        return $user && $user->hasRole('admin');
    }

    /**
     * Apply trainer filter to a query builder.
     * For trainers: filters by their assigned members
     * For admins: no filtering (returns query as-is)
     */
    public static function applyTrainerFilter($query, string $userIdColumn = 'user_id', ?int $trainerId = null)
    {
        $memberIds = self::getTrainerMemberIds($trainerId);
        
        if (!empty($memberIds)) {
            $query->whereIn($userIdColumn, $memberIds);
        }

        return $query;
    }

    /**
     * Get trainer context for exports.
     * Returns array with trainer_id and member_ids.
     */
    public static function getTrainerContext(?int $userId = null): array
    {
        $user = $userId ? User::find($userId) : auth()->user();
        
        if (!$user) {
            return [
                'is_trainer' => false,
                'is_admin' => false,
                'trainer_id' => null,
                'member_ids' => [],
            ];
        }

        $isTrainer = $user->hasRole('trainer');
        $isAdmin = $user->hasRole('admin');

        return [
            'is_trainer' => $isTrainer,
            'is_admin' => $isAdmin,
            'trainer_id' => $isTrainer ? $user->id : null,
            'member_ids' => $isTrainer ? self::getTrainerMemberIds($user->id) : [],
        ];
    }
}

