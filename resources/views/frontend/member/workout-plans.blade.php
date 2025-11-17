@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Workout Plans</h1>
            <p class="mt-2 text-gray-600">View and manage your personalized workout plans.</p>
        </div>

        {{-- Status Filter Tabs --}}
        @if($statusCounts['all'] > 0)
        <div class="mb-6 bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('member.workout-plans', ['status' => 'all']) }}" 
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status', 'all') === 'all' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    All ({{ $statusCounts['all'] }})
                </a>
                <a href="{{ route('member.workout-plans', ['status' => 'active']) }}" 
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status') === 'active' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Active ({{ $statusCounts['active'] }})
                </a>
                <a href="{{ route('member.workout-plans', ['status' => 'completed']) }}" 
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status') === 'completed' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Completed ({{ $statusCounts['completed'] }})
                </a>
                <a href="{{ route('member.workout-plans', ['status' => 'paused']) }}" 
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status') === 'paused' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Paused ({{ $statusCounts['paused'] }})
                </a>
                <a href="{{ route('member.workout-plans', ['status' => 'cancelled']) }}" 
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status') === 'cancelled' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Cancelled ({{ $statusCounts['cancelled'] }})
                </a>
            </div>
        </div>
        @endif

        {{-- Workout Plans Grid --}}
        @if($workoutPlans->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($workoutPlans as $index => $plan)
                @php
                    // Rotate through gradient colors: blue, green, purple
                    $gradientColors = [
                        ['from' => 'from-blue-500', 'to' => 'to-blue-600', 'button' => 'bg-blue-600 hover:bg-blue-700'],
                        ['from' => 'from-green-500', 'to' => 'to-green-600', 'button' => 'bg-green-600 hover:bg-green-700'],
                        ['from' => 'from-purple-500', 'to' => 'to-purple-600', 'button' => 'bg-purple-600 hover:bg-purple-700'],
                    ];
                    $colorIndex = $index % 3;
                    $colors = $gradientColors[$colorIndex];
                    
                    // Determine difficulty based on exercises count or status
                    $exerciseCount = is_array($plan->exercises) ? count($plan->exercises) : 0;
                    $difficulty = 'Intermediate';
                    $difficultyColor = 'bg-orange-100 text-orange-800';
                    if ($exerciseCount <= 5) {
                        $difficulty = 'Beginner';
                        $difficultyColor = 'bg-green-100 text-green-800';
                    } elseif ($exerciseCount >= 10) {
                        $difficulty = 'Advanced';
                        $difficultyColor = 'bg-red-100 text-red-800';
                    }
                    
                    // Calculate duration in minutes (estimate: 5 min per exercise)
                    $durationMinutes = $exerciseCount * 5;
                @endphp
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="bg-gradient-to-r {{ $colors['from'] }} {{ $colors['to'] }} p-6">
                        <h3 class="text-xl font-bold text-white">{{ $plan->plan_name ?? 'Untitled Plan' }}</h3>
                        <p class="text-white text-sm mt-1 opacity-90">
                            {{ $plan->description ? Str::limit($plan->description, 50) : 'Personalized Workout Plan' }}
                        </p>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium text-gray-500">Duration</span>
                            <span class="text-sm font-semibold text-gray-900">
                                @if($durationMinutes > 0)
                                    {{ $durationMinutes }} min
                                @elseif($plan->duration_weeks)
                                    {{ $plan->duration_weeks }} {{ $plan->duration_weeks == 1 ? 'week' : 'weeks' }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium text-gray-500">Difficulty</span>
                            <span class="px-2 py-1 {{ $difficultyColor }} text-xs font-semibold rounded">
                                {{ $difficulty }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium text-gray-500">Exercises</span>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ $exerciseCount }} {{ $exerciseCount == 1 ? 'exercise' : 'exercises' }}
                            </span>
                        </div>
                        @if($plan->trainer)
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium text-gray-500">Trainer</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $plan->trainer->name }}</span>
                        </div>
                        @endif
                        @if($plan->start_date)
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium text-gray-500">Start Date</span>
                            <span class="text-sm font-semibold text-gray-900">{{ format_date($plan->start_date) }}</span>
                        </div>
                        @endif
                        <div class="pt-4 border-t border-gray-200">
                            <a href="{{ route('member.workout-plans.show', $plan->id) }}" class="block w-full text-center px-4 py-2 {{ $colors['button'] }} text-white rounded-lg transition-colors font-medium">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($workoutPlans->hasPages())
        <div class="mt-8">
            {{ $workoutPlans->links() }}
        </div>
        @endif
        @else
        {{-- Empty State --}}
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">
                @if(request('status') && request('status') !== 'all')
                    No {{ ucfirst(request('status')) }} Workout Plans
                @else
                    No Workout Plans Yet
                @endif
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                @if(request('status') && request('status') !== 'all')
                    You don't have any {{ request('status') }} workout plans at the moment.
                @else
                    Your assigned workout plans will appear here once your trainer creates them for you.
                @endif
            </p>
            @if(request('status') && request('status') !== 'all')
            <div class="mt-6">
                <a href="{{ route('member.workout-plans', ['status' => 'all']) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    View All Plans
                </a>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
