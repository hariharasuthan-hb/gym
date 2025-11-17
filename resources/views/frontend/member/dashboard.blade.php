@extends('frontend.layouts.app')

@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Member Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back! Here's your overview.</p>
        </div>

        {{-- Dashboard Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Active Subscription Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 {{ $activeSubscription ? 'bg-blue-100' : 'bg-gray-100' }} rounded-lg p-3">
                        <svg class="w-6 h-6 {{ $activeSubscription ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Subscription</p>
                        @if($activeSubscription && $activeSubscription->subscriptionPlan)
                            <p class="text-2xl font-semibold text-gray-900">{{ $activeSubscription->subscriptionPlan->plan_name }}</p>
                            @if($activeSubscription->next_billing_at)
                                <p class="text-xs text-gray-500">Next billing: {{ $activeSubscription->next_billing_at->format('M d, Y') }}</p>
                            @elseif($activeSubscription->trial_end_at)
                                <p class="text-xs text-gray-500">Trial ends: {{ $activeSubscription->trial_end_at->format('M d, Y') }}</p>
                            @endif
                        @else
                            <p class="text-2xl font-semibold text-gray-900">None</p>
                            <p class="text-xs text-red-500">No active plan</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Workout Plans Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Workout Plans</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $totalWorkoutPlans ?? 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Active plans</p>
                    </div>
                </div>
            </div>

            {{-- Diet Plans Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Diet Plans</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $totalDietPlans ?? 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Active plans</p>
                    </div>
                </div>
            </div>

            {{-- Activities Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Activities</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $totalActivities ?? 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total check-ins</p>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($todayRecordingProgress))
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Today's Workout Progress</p>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $todayRecordingProgress['plan']->plan_name ?? 'Workout Plan' }}</h2>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-gray-900">{{ $todayRecordingProgress['percent'] }}%</p>
                    <p class="text-xs text-gray-500">{{ $todayRecordingProgress['recorded_count'] }} / {{ $todayRecordingProgress['total_exercises'] }} exercises recorded</p>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 mb-4 overflow-hidden">
                <div class="bg-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $todayRecordingProgress['percent'] }}%"></div>
            </div>
            <div class="flex flex-wrap items-center justify-between text-sm text-gray-600">
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                        Recorded today
                    </span>
                    <span class="inline-flex items-center">
                        <span class="w-3 h-3 bg-gray-300 rounded-full mr-2"></span>
                        Pending
                    </span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 {{ $todayRecordingProgress['attendance_marked'] ? 'text-green-600' : 'text-yellow-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    @if($todayRecordingProgress['attendance_marked'])
                        <span class="font-semibold text-green-700">Attendance marked for today</span>
                    @else
                        <span class="font-semibold text-yellow-700">Complete recordings to auto-mark attendance</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Subscription Plans Section (Show if user has no active subscription) --}}
        @if(!$activeSubscription && $subscriptionPlans && $subscriptionPlans->count() > 0)
        <div class="bg-white rounded-lg shadow mb-8 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Choose Your Plan</h2>
                    <p class="mt-1 text-gray-600">Select a subscription plan to get started</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($subscriptionPlans as $plan)
                <div class="border-2 rounded-xl p-6 hover:shadow-lg transition-all duration-300 {{ $plan->price == $subscriptionPlans->min('price') ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                    @if($plan->price == $subscriptionPlans->min('price'))
                    <div class="inline-block bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full mb-4">
                        Most Popular
                    </div>
                    @endif
                    
                    @if($plan->image)
                    <div class="mb-4">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($plan->image) }}" 
                             alt="{{ $plan->plan_name }}" 
                             class="w-full h-32 object-cover rounded-lg">
                    </div>
                    @endif
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan->plan_name }}</h3>
                    
                    <div class="mb-4">
                        <span class="text-3xl font-bold text-gray-900">${{ $plan->formatted_price }}</span>
                        <span class="text-gray-600">/{{ $plan->formatted_duration }}</span>
                    </div>
                    
                    @if($plan->description)
                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($plan->description, 100) }}</p>
                    @endif
                    
                    @if($plan->features && count($plan->features) > 0)
                    <ul class="space-y-2 mb-6">
                        @foreach(array_slice($plan->features, 0, 5) as $feature)
                            @if(!empty($feature))
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-700">{{ $feature }}</span>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                    @endif
                    
                    <a href="{{ route('member.subscription.checkout', $plan->id) }}" class="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-center">
                        Subscribe Now
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Active Plans Section --}}
        @if(($activeWorkoutPlans && $activeWorkoutPlans->count() > 0) || ($activeDietPlans && $activeDietPlans->count() > 0))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {{-- Active Workout Plans --}}
            @if($activeWorkoutPlans && $activeWorkoutPlans->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Active Workout Plans</h2>
                    <a href="{{ route('member.workout-plans') }}" class="text-sm text-green-600 hover:text-green-800 font-medium">
                        View All →
                    </a>
                </div>
                <div class="space-y-4">
                    @foreach($activeWorkoutPlans as $plan)
                        @include('frontend.components.plan-card', [
                            'plan' => $plan,
                            'type' => 'workout',
                            'viewRoute' => null // Add route if needed
                        ])
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Active Diet Plans --}}
            @if($activeDietPlans && $activeDietPlans->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Active Diet Plans</h2>
                    <a href="{{ route('member.diet-plans') }}" class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                        View All →
                    </a>
                </div>
                <div class="space-y-4">
                    @foreach($activeDietPlans as $plan)
                        @include('frontend.components.plan-card', [
                            'plan' => $plan,
                            'type' => 'diet',
                            'viewRoute' => null // Add route if needed
                        ])
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg shadow mb-8 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('member.workout-plans') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="font-medium text-gray-900">View Workout Plans</span>
                </a>
                <a href="{{ route('member.diet-plans') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="font-medium text-gray-900">View Diet Plans</span>
                </a>
                <a href="{{ route('member.profile') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-medium text-gray-900">Edit Profile</span>
                </a>
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Activities</h2>
            <div class="space-y-4">
                <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-900">Workout completed</p>
                        <p class="text-sm text-gray-500">2 hours ago</p>
                    </div>
                </div>
                <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-900">Diet plan updated</p>
                        <p class="text-sm text-gray-500">1 day ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

