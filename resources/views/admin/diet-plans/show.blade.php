@extends('admin.layouts.app')

@section('page-title', 'View Diet Plan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-semibold text-gray-900">{{ $dietPlan->plan_name }}</h1>
        <div class="flex space-x-3">
            @can('edit diet plans')
            @if(!auth()->user()->hasRole('trainer') || $dietPlan->trainer_id === auth()->id())
            <a href="{{ route('admin.diet-plans.edit', $dietPlan->id) }}" class="btn btn-primary">
                Edit Plan
            </a>
            @endif
            @endcan
            <a href="{{ route('admin.diet-plans.index') }}" class="btn btn-secondary">
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
                    <p class="text-sm text-gray-900 mt-1">{{ $dietPlan->plan_name }}</p>
                </div>
                
                @if($dietPlan->description)
                <div>
                    <label class="text-sm font-medium text-gray-600">Description</label>
                    <p class="text-sm text-gray-900 mt-1">{{ $dietPlan->description }}</p>
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
                                $color = $statusColors[$dietPlan->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $color }}">
                                {{ ucfirst($dietPlan->status) }}
                            </span>
                        </p>
                    </div>
                    
                    @if($dietPlan->target_calories)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Target Calories</label>
                        <p class="text-sm text-gray-900 mt-1">{{ number_format($dietPlan->target_calories) }} cal</p>
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
                    <p class="text-sm text-gray-900 mt-1">{{ $dietPlan->member->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $dietPlan->member->email ?? '' }}</p>
                </div>
                
                @if(!auth()->user()->hasRole('trainer'))
                <div>
                    <label class="text-sm font-medium text-gray-600">Trainer</label>
                    <p class="text-sm text-gray-900 mt-1">{{ $dietPlan->trainer->name ?? 'N/A' }}</p>
                </div>
                @endif
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Start Date</label>
                        <p class="text-sm text-gray-900 mt-1">{{ format_date($dietPlan->start_date) }}</p>
                    </div>
                    
                    @if($dietPlan->end_date)
                    <div>
                        <label class="text-sm font-medium text-gray-600">End Date</label>
                        <p class="text-sm text-gray-900 mt-1">{{ format_date($dietPlan->end_date) }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($dietPlan->nutritional_goals)
    <div class="admin-card">
        <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Nutritional Goals</h2>
        </div>
        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $dietPlan->nutritional_goals }}</p>
    </div>
    @endif

    @if($dietPlan->meal_plan)
    <div class="admin-card">
        <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Meal Plan</h2>
        </div>
        <div class="space-y-2">
            @if(is_array($dietPlan->meal_plan))
                @foreach($dietPlan->meal_plan as $meal)
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-900">{{ is_array($meal) ? json_encode($meal, JSON_PRETTY_PRINT) : $meal }}</p>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $dietPlan->meal_plan }}</p>
            @endif
        </div>
    </div>
    @endif

    @if($dietPlan->notes)
    <div class="admin-card">
        <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Notes</h2>
        </div>
        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $dietPlan->notes }}</p>
    </div>
    @endif
</div>
@endsection

