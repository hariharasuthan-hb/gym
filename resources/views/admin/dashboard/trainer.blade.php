@extends('admin.layouts.app')

@section('page-title', 'Trainer Dashboard')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Trainer Dashboard</h1>
    
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Members</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalMembers }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Active Plans</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $activePlans }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Today's Check-ins</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $todayCheckIns }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">This Week</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $thisWeekCheckIns }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Recent Activities and Active Plans --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Activities --}}
        <div class="admin-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Activities</h2>
                <a href="{{ route('admin.trainer.workout-videos.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    View All
                </a>
            </div>
            <div class="space-y-3">
                @forelse($recentActivities as $activity)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $activity->user->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-600">{{ $activity->date->format('M d, Y') }}</p>
                        </div>
                        <div class="text-right">
                            @if($activity->check_in_time)
                                <p class="text-sm font-semibold text-gray-900">{{ $activity->check_in_time->format('H:i') }}</p>
                            @endif
                            @if($activity->duration_minutes)
                                <p class="text-xs text-gray-600">{{ $activity->duration_minutes }} min</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 text-center py-4">No recent activities</p>
                @endforelse
            </div>
        </div>
        
        {{-- Active Workout Plans --}}
        <div class="admin-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Active Workout Plans</h2>
                <a href="{{ route('admin.workout-plans.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    View All
                </a>
            </div>
            <div class="space-y-3">
                @forelse($activeWorkoutPlans as $plan)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $plan->plan_name }}</p>
                            <p class="text-xs text-gray-600">{{ $plan->member->name ?? 'Unknown' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 text-center py-4">No active workout plans</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

