<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class UserActivityController extends Controller
{
    /**
     * Display a listing of users (members) with their activity summary.
     * For trainers: shows only their assigned members
     * For admins: shows all members
     */
    public function index(): View
    {
        $user = auth()->user();
        
        // Get members based on role
        if ($user->hasRole('trainer')) {
            // Get all members assigned to this trainer through workout plans
            $memberIds = \App\Models\WorkoutPlan::where('trainer_id', $user->id)
                ->pluck('member_id')
                ->unique();
            
            $members = User::whereIn('id', $memberIds)
                ->with(['roles'])
                ->get();
        } else {
            // Admin sees all members
            $members = User::role('member')
                ->with(['roles'])
                ->get();
        }
        
        // Add activity summary for each member
        $members = $members->map(function ($member) {
            $totalCheckIns = \App\Models\ActivityLog::where('user_id', $member->id)->count();
            $todayCheckIns = \App\Models\ActivityLog::where('user_id', $member->id)
                ->where('date', today())
                ->count();
            $lastActivity = \App\Models\ActivityLog::where('user_id', $member->id)
                ->latest('date')
                ->first();
            
            $member->total_check_ins = $totalCheckIns;
            $member->today_check_ins = $todayCheckIns;
            $member->last_activity = $lastActivity;
            
            return $member;
        });
        
        return view('admin.user-activity.index', compact('members'));
    }
}

