<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controller for displaying the admin and trainer dashboards.
 * 
 * Handles the main dashboard view which displays different content based on
 * user role. Admins see overall system statistics while trainers see
 * statistics specific to their assigned members. Accessible by both admin
 * and trainer roles.
 */
class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        
        // Check if user is a trainer
        if ($user->hasRole('trainer')) {
            return $this->trainerDashboard($user);
        }
        
        // Admin dashboard
        return $this->adminDashboard();
    }
    
    /**
     * Display trainer-specific dashboard.
     */
    private function trainerDashboard($trainer): View
    {
        // Auto-complete expired diet plans
        \App\Models\DietPlan::autoCompleteExpired();
        
        // Get trainer's assigned members
        $memberIds = \App\Models\WorkoutPlan::where('trainer_id', $trainer->id)
            ->pluck('member_id')
            ->unique();
        
        // Statistics
        $totalMembers = $memberIds->count();
        $activePlans = \App\Models\WorkoutPlan::where('trainer_id', $trainer->id)
            ->where('status', 'active')
            ->count();
        $todayCheckIns = \App\Models\ActivityLog::forTrainerMembers($trainer->id)
            ->where('date', today())
            ->count();
        $thisWeekCheckIns = \App\Models\ActivityLog::forTrainerMembers($trainer->id)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        
        // Recent activities
        $recentActivities = \App\Models\ActivityLog::forTrainerMembers($trainer->id)
            ->with('user')
            ->latest('date')
            ->latest('check_in_time')
            ->limit(5)
            ->get();
        
        // Active workout plans
        $activeWorkoutPlans = \App\Models\WorkoutPlan::where('trainer_id', $trainer->id)
            ->where('status', 'active')
            ->with(['member'])
            ->latest()
            ->limit(5)
            ->get();
        
        // Active diet plans
        $activeDietPlans = \App\Models\DietPlan::where('trainer_id', $trainer->id)
            ->where('status', 'active')
            ->with(['member'])
            ->latest()
            ->limit(5)
            ->get();
        
        // Get trainer's assigned members
        $members = \App\Models\User::whereIn('id', $memberIds->toArray())
            ->with(['roles'])
            ->latest()
            ->limit(5)
            ->get();
        
        return view('admin.dashboard.trainer', compact(
            'totalMembers',
            'activePlans',
            'todayCheckIns',
            'thisWeekCheckIns',
            'recentActivities',
            'activeWorkoutPlans',
            'activeDietPlans',
            'members'
        ));
    }
    
    /**
     * Display admin dashboard.
     */
    private function adminDashboard(): View
    {
        // Add admin dashboard statistics here
        $totalMembers = \App\Models\User::role('member')->count();
        $activeSubscriptions = \App\Models\Subscription::active()->count();
        $todayCheckIns = \App\Models\ActivityLog::where('date', today())->count();
        // Use final_amount if exists, otherwise use amount
        $dateColumn = \App\Models\Payment::getDateColumn();
        $query = \App\Models\Payment::whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year);
        
        // Only filter by status if the column exists (accounting system may not have it)
        if (\App\Models\Payment::hasStatusColumn()) {
            $query->where('status', 'completed');
        }
        
        // Check if final_amount column exists
        $hasFinalAmount = \Illuminate\Support\Facades\Schema::hasColumn('payments', 'final_amount');
        
        if ($hasFinalAmount) {
            $monthlyRevenue = (float) $query->sum(DB::raw('COALESCE(final_amount, amount, 0)'));
        } else {
            $monthlyRevenue = (float) $query->sum('amount');
        }
        
        return view('admin.dashboard.index', compact(
            'totalMembers',
            'activeSubscriptions',
            'todayCheckIns',
            'monthlyRevenue'
        ));
    }
}

