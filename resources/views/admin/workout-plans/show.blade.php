@extends('admin.layouts.app')

@section('page-title', 'View Workout Plan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-semibold text-gray-900">{{ $workoutPlan->plan_name }}</h1>
        <div class="flex space-x-3">
            @can('edit workout plans')
            @if(!auth()->user()->hasRole('trainer') || $workoutPlan->trainer_id === auth()->id())
            <a href="{{ route('admin.workout-plans.edit', $workoutPlan->id) }}" class="btn btn-primary">
                Edit Plan
            </a>
            @endif
            @endcan
            <a href="{{ route('admin.workout-plans.index') }}" class="btn btn-secondary">
                Back to Plans
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Plan Information --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Plan Information</h2>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Plan Name</label>
                    <p class="text-sm text-gray-900 mt-1">{{ $workoutPlan->plan_name }}</p>
                </div>
                
                @if($workoutPlan->description)
                <div>
                    <label class="text-sm font-medium text-gray-600">Description</label>
                    <p class="text-sm text-gray-900 mt-1">{{ $workoutPlan->description }}</p>
                </div>
                @endif
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <p class="mt-1">
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'paused' => 'bg-yellow-100 text-yellow-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $color = $statusColors[$workoutPlan->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $color }}">
                                {{ ucfirst($workoutPlan->status) }}
                            </span>
                        </p>
                    </div>
                    
                    @if($workoutPlan->duration_weeks)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Duration</label>
                        <p class="text-sm text-gray-900 mt-1">{{ $workoutPlan->duration_weeks }} weeks</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Member & Dates --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Member & Schedule</h2>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Member</label>
                    <p class="text-sm text-gray-900 mt-1">{{ $workoutPlan->member->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $workoutPlan->member->email ?? '' }}</p>
                </div>
                
                @if(!auth()->user()->hasRole('trainer'))
                <div>
                    <label class="text-sm font-medium text-gray-600">Trainer</label>
                    <p class="text-sm text-gray-900 mt-1">{{ $workoutPlan->trainer->name ?? 'N/A' }}</p>
                </div>
                @endif
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Start Date</label>
                        <p class="text-sm text-gray-900 mt-1">{{ format_date($workoutPlan->start_date) }}</p>
                    </div>
                    
                    @if($workoutPlan->end_date)
                    <div>
                        <label class="text-sm font-medium text-gray-600">End Date</label>
                        <p class="text-sm text-gray-900 mt-1">{{ format_date($workoutPlan->end_date) }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($workoutPlan->exercises)
    <div class="admin-card">
        <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Exercises</h2>
        </div>
        <div class="space-y-2">
            @if(is_array($workoutPlan->exercises))
                @foreach($workoutPlan->exercises as $exercise)
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-900">{{ is_array($exercise) ? json_encode($exercise) : $exercise }}</p>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-600">{{ $workoutPlan->exercises }}</p>
            @endif
        </div>
    </div>
    @endif

    @if($workoutPlan->notes)
    <div class="admin-card">
        <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Notes</h2>
        </div>
        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $workoutPlan->notes }}</p>
    </div>
    @endif
</div>
@endsection

