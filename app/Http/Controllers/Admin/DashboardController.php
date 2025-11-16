<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        
        return view('admin.dashboard.trainer', compact(
            'totalMembers',
            'activePlans',
            'todayCheckIns',
            'thisWeekCheckIns',
            'recentActivities',
            'activeWorkoutPlans'
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
        $monthlyRevenue = \App\Models\Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->where('status', 'completed')
            ->sum('final_amount');
        
        return view('admin.dashboard.index', compact(
            'totalMembers',
            'activeSubscriptions',
            'todayCheckIns',
            'monthlyRevenue'
        ));
    }
}

