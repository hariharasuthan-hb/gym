@php
    /**
     * Reusable Plan Card Component
     * 
     * Displays a workout or diet plan in a consistent card format.
     * 
     * @param object $plan - The plan object (WorkoutPlan or DietPlan)
     * @param string $type - Plan type: 'workout' or 'diet'
     * @param string|null $viewRoute - Route name for viewing the plan (optional)
     */
    $plan = $plan ?? null;
    $type = $type ?? 'workout';
    $viewRoute = $viewRoute ?? null;
    
    // Determine colors and icons based on type
    $colors = [
        'workout' => [
            'bg' => 'bg-gradient-to-br from-green-50 to-emerald-50',
            'border' => 'border-green-200',
            'icon' => 'text-green-600',
            'badge' => 'bg-green-100 text-green-800',
            'iconBg' => 'bg-green-100',
        ],
        'diet' => [
            'bg' => 'bg-gradient-to-br from-purple-50 to-pink-50',
            'border' => 'border-purple-200',
            'icon' => 'text-purple-600',
            'badge' => 'bg-purple-100 text-purple-800',
            'iconBg' => 'bg-purple-100',
        ],
    ];
    
    $theme = $colors[$type] ?? $colors['workout'];
    
    // Status colors
    $statusColors = [
        'active' => 'bg-green-100 text-green-800',
        'completed' => 'bg-blue-100 text-blue-800',
        'paused' => 'bg-yellow-100 text-yellow-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
    $statusColor = $statusColors[$plan->status ?? 'active'] ?? 'bg-gray-100 text-gray-800';
@endphp

@if($plan)
<div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-200 border {{ $theme['border'] }} overflow-hidden">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-3 flex-1">
                <div class="flex-shrink-0 {{ $theme['iconBg'] }} rounded-lg p-3">
                    @if($type === 'workout')
                        <svg class="w-6 h-6 {{ $theme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    @else
                        <svg class="w-6 h-6 {{ $theme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $plan->plan_name ?? 'Untitled Plan' }}</h3>
                    @if($plan->trainer)
                        <p class="text-sm text-gray-600 mt-1">Trainer: {{ $plan->trainer->name }}</p>
                    @endif
                </div>
            </div>
            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusColor }} whitespace-nowrap ml-2">
                {{ ucfirst($plan->status ?? 'active') }}
            </span>
        </div>
        
        {{-- Description --}}
        @if($plan->description)
        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ Str::limit($plan->description, 100) }}</p>
        @endif
        
        {{-- Plan Details --}}
        <div class="space-y-3 mb-4">
            {{-- Dates --}}
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="font-medium">Start:</span>
                <span class="ml-2">{{ format_date_smart($plan->start_date) }}</span>
                @if($plan->end_date)
                    <span class="mx-2">-</span>
                    <span>{{ format_date_smart($plan->end_date) }}</span>
                @endif
            </div>
            
            {{-- Duration (for workout plans) --}}
            @if($type === 'workout' && $plan->duration_weeks)
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Duration:</span>
                <span class="ml-2">{{ $plan->duration_weeks }} {{ $plan->duration_weeks == 1 ? 'week' : 'weeks' }}</span>
            </div>
            @endif
            
            {{-- Target Calories (for diet plans) --}}
            @if($type === 'diet' && $plan->target_calories)
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span class="font-medium">Target:</span>
                <span class="ml-2">{{ number_format($plan->target_calories) }} calories/day</span>
            </div>
            @endif
            
            {{-- Exercises/Meals Count --}}
            @if($type === 'workout' && $plan->exercises && is_array($plan->exercises))
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                <span class="font-medium">Exercises:</span>
                <span class="ml-2">{{ count($plan->exercises) }} {{ count($plan->exercises) == 1 ? 'exercise' : 'exercises' }}</span>
            </div>
            @elseif($type === 'diet' && $plan->meal_plan && is_array($plan->meal_plan))
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <span class="font-medium">Meals:</span>
                <span class="ml-2">{{ count($plan->meal_plan) }} {{ count($plan->meal_plan) == 1 ? 'meal' : 'meals' }}</span>
            </div>
            @endif
        </div>
        
        {{-- Action Button --}}
        @if($viewRoute)
        <a href="{{ route($viewRoute, $plan->id) }}" 
           class="block w-full text-center px-4 py-2.5 {{ $type === 'workout' ? 'bg-green-600 hover:bg-green-700' : 'bg-purple-600 hover:bg-purple-700' }} text-white rounded-lg font-medium transition-colors duration-200">
            View Details
        </a>
        @endif
    </div>
</div>
@endif

