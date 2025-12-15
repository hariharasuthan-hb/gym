@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">My Diet Plans</h1>
            <p class="mt-2 text-gray-600">Track and follow the nutrition plans shared by your trainer.</p>
        </div>

        @if(($statusCounts['all'] ?? 0) > 0)
        <div class="mb-6 bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-3">
                @php
                    $statuses = [
                        'all' => 'All',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'paused' => 'Paused',
                        'cancelled' => 'Cancelled',
                    ];
                    $currentStatus = request('status', 'all');
                @endphp
                @foreach($statuses as $key => $label)
                    <a href="{{ route('member.diet-plans', ['status' => $key]) }}"
                       class="px-4 py-2 rounded-full text-sm font-semibold transition-colors
                              {{ $currentStatus === $key ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($dietPlans->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-12">
            @foreach($dietPlans as $plan)
            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase text-gray-500">Plan</p>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $plan->plan_name ?? 'Untitled Plan' }}</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Created {{ format_date_smart($plan->start_date) ?? 'N/A' }}
                                @if($plan->end_date)
                                    • Ends {{ format_date_smart($plan->end_date) }}
                                @endif
                            </p>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            {{ $plan->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $plan->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $plan->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $plan->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                            {{ !in_array($plan->status, ['active','completed','paused','cancelled']) ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ ucfirst($plan->status ?? 'active') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <p class="text-xs uppercase font-semibold text-gray-500">Plan Information</p>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Duration</span>
                                <span class="font-semibold text-gray-900">
                                    @if($plan->start_date && $plan->end_date)
                                        {{ format_date_smart($plan->start_date) }} - {{ format_date_smart($plan->end_date) }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Target Calories</span>
                                <span class="font-semibold text-gray-900">
                                    {{ $plan->target_calories ? number_format($plan->target_calories) . ' kcal/day' : 'Not specified' }}
                                </span>
                            </div>
                            @if($plan->description)
                                <div>
                                    <p class="text-xs uppercase font-semibold text-gray-500">Description</p>
                                    <p class="text-sm text-gray-700">{{ $plan->description }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <p class="text-xs uppercase font-semibold text-gray-500">Additional Information</p>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Trainer</span>
                                <span class="font-semibold text-gray-900">{{ $plan->trainer->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Created</span>
                                <span class="font-semibold text-gray-900">{{ format_date_member($plan->created_at) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Updated</span>
                                <span class="font-semibold text-gray-900">{{ format_date_member($plan->updated_at) }}</span>
                            </div>
                            @if($plan->nutritional_goals)
                                <div>
                                    <p class="text-xs uppercase font-semibold text-gray-500">Goals</p>
                                    <p class="text-sm text-gray-700">{{ $plan->nutritional_goals }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($plan->meal_plan && is_array($plan->meal_plan))
                        <div class="mb-6">
                            <p class="text-sm font-semibold text-gray-900 mb-3">Meal Plan</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($plan->meal_plan as $meal => $details)
                                    @if($details)
                                    <div class="border border-gray-100 rounded-lg p-3 h-full">
                                        <p class="text-xs font-semibold uppercase text-gray-500 mb-1">{{ ucfirst(str_replace('_',' ', $meal)) }}</p>
                                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ is_array($details) ? implode("\n", $details) : $details }}</p>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($plan->notes)
                        <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                            <p class="text-xs uppercase font-semibold text-green-700">Trainer Notes</p>
                            <p class="text-sm text-green-800 whitespace-pre-line mt-1">{{ $plan->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $dietPlans->links() }}
        </div>
        @else
        <div class="bg-white rounded-lg shadow p-10 text-center">
            <svg class="mx-auto h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-4 text-xl font-semibold text-gray-900">No Diet Plans Yet</h3>
            <p class="mt-2 text-sm text-gray-500">
                Your diet plans will appear here once assigned by your trainer.
            </p>
            <div class="mt-6">
                <a href="{{ route('member.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Back to Dashboard
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

