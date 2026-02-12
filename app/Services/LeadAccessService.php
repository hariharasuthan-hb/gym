<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadAccessService
{
    /**
     * Check if user can access a specific lead.
     * Admins can access all leads, trainers can only access their assigned leads.
     * 
     * @param Lead $lead
     * @param User|null $user
     * @return bool
     */
    public static function canAccessLead(Lead $lead, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Admins have full access
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Trainers can only access their assigned leads
        if ($user->hasRole('trainer')) {
            return $lead->assigned_to === $user->id;
        }
        
        return false;
    }

    /**
     * Ensure user can access a specific lead, abort if not.
     * 
     * @param Lead $lead
     * @param User|null $user
     * @param string $action
     * @return void
     */
    public static function ensureCanAccessLead(Lead $lead, ?User $user = null, string $action = 'access'): void
    {
        if (!self::canAccessLead($lead, $user)) {
            abort(403, "Unauthorized. You can only {$action} leads assigned to you.");
        }
    }

    /**
     * Apply trainer filter to leads query.
     * For trainers: filters by their assigned leads
     * For admins: no filtering (returns query as-is)
     * 
     * @param Builder $query
     * @param User|null $user
     * @return Builder
     */
    public static function applyTrainerFilter(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return $query;
        }
        
        // Admins see all leads
        if ($user->hasRole('admin')) {
            return $query;
        }
        
        // Trainers see only their assigned leads
        if ($user->hasRole('trainer')) {
            return $query->where('assigned_to', $user->id);
        }
        
        return $query;
    }

    /**
     * Check if current user is admin.
     * 
     * @param User|null $user
     * @return bool
     */
    public static function isAdmin(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        return $user && $user->hasRole('admin');
    }

    /**
     * Check if current user is trainer (but not admin).
     * 
     * @param User|null $user
     * @return bool
     */
    public static function isTrainerOnly(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        return $user && $user->hasRole('trainer') && !$user->hasRole('admin');
    }
}
