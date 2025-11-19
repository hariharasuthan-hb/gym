@extends('frontend.layouts.app')

@php
    use Illuminate\Support\Facades\Storage;
    $hasDateFilter = !empty($startDate) || !empty($endDate);
@endphp

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm uppercase tracking-wide text-green-600 font-semibold">Workout Compliance</p>
                <h1 class="text-3xl font-bold text-gray-900">Workout Video Reviews</h1>
                <p class="mt-2 text-gray-600">
                    Track every video you have submitted and see which ones were approved, rejected, or are still waiting for trainer feedback.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('member.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Dashboard
                </a>
                <a href="{{ route('member.workout-plans') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg shadow-sm text-sm font-semibold hover:bg-green-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Go to Plans
                </a>
            </div>
        </div>

        {{-- Status Filters --}}
        @if($statusCounts['all'] > 0)
        <div class="mb-6 bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-3">
                @foreach(['all' => 'All', 'approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $statusKey => $label)
                    @php
                        $isActive = $selectedStatus === $statusKey;
                        $badgeClasses = match ($statusKey) {
                            'approved' => 'bg-green-100 text-green-700',
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-700'
                        };
                    @endphp
                    <a href="{{ route('member.workout-videos', array_filter([
                            'status' => $statusKey,
                            'plan_id' => $selectedPlanId,
                        ], fn($value) => !is_null($value) && $value !== '')) }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors {{ $isActive ? 'bg-green-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        <span>{{ $label }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $isActive ? 'bg-white bg-opacity-20' : $badgeClasses }}">
                            {{ $statusCounts[$statusKey] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Plan Filter --}}
        <div class="mb-8 bg-white rounded-lg shadow p-4">
            <form method="GET" action="{{ route('member.workout-videos') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @php
                    $canClearFilters = $selectedPlanId || $hasDateFilter;
                @endphp
                <div>
                    <label for="plan-filter" class="block text-sm font-medium text-gray-700 mb-1">Workout Plan</label>
                    <select id="plan-filter" name="plan_id" class="block w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Plans</option>
                        @foreach($workoutPlans as $plan)
                            <option value="{{ $plan->id }}" {{ $selectedPlanId === $plan->id ? 'selected' : '' }}>
                                {{ $plan->plan_name ?? 'Untitled Plan' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="start-date-filter" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="start-date-filter" name="start_date" value="{{ $startDate }}" class="block w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label for="end-date-filter" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="end-date-filter" name="end_date" value="{{ $endDate }}" class="block w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500">
                </div>
                <div class="flex items-end gap-2">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <button type="submit" class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 transition w-full hover:bg-gray-50 gap-2">
                        Apply Filters
                    </button>
                    <a href="{{ route('member.workout-videos', array_filter(['status' => $selectedStatus !== 'all' ? $selectedStatus : null], fn($value) => !is_null($value))) }}"
                       class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 transition w-full {{ $canClearFilters ? 'hover:bg-gray-50' : 'opacity-50 cursor-not-allowed pointer-events-none' }}"
                       aria-disabled="{{ $canClearFilters ? 'false' : 'true' }}">
                        Clear
                    </a>
                </div>
            </form>
            @if($hasDateFilter)
            <p class="mt-3 text-xs text-gray-500">
                Showing uploads
                @if($startDate)
                    from <span class="font-semibold text-gray-700">{{ format_date($startDate) }}</span>
                @endif
                @if($endDate)
                    @if($startDate) to @else up to @endif
                    <span class="font-semibold text-gray-700">{{ format_date($endDate) }}</span>
                @endif
            </p>
            @endif
        </div>

        {{-- Status Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg border-l-4 border-green-500 shadow p-4">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Approved</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $statusCounts['approved'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Ready for your next session</p>
            </div>
            <div class="bg-white rounded-lg border-l-4 border-yellow-500 shadow p-4">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Pending Review</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $statusCounts['pending'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Trainer will review soon</p>
            </div>
            <div class="bg-white rounded-lg border-l-4 border-red-500 shadow p-4">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Rejected</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $statusCounts['rejected'] ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Requires new recording</p>
            </div>
        </div>

        {{-- Videos List --}}
        @if($videos->count() > 0)
        <div class="space-y-6">
            @foreach($videos as $video)
            @php
                $statusBadge = match ($video->status) {
                    'approved' => 'bg-green-100 text-green-800',
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800'
                };
                $videoExists = $video->video_path ? Storage::disk('public')->exists($video->video_path) : false;
                $videoUrl = $videoExists ? Storage::url($video->video_path) : null;
            @endphp
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Exercise</p>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $video->exercise_name }}</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Part of <span class="font-semibold text-gray-900">{{ $video->workoutPlan->plan_name ?? 'Workout Plan' }}</span>
                        </p>
                        @if($video->workoutPlan && $video->workoutPlan->trainer)
                        <p class="text-sm text-gray-500">
                            Reviewed by Trainer: <span class="font-semibold text-gray-900">{{ $video->workoutPlan->trainer->name }}</span>
                        </p>
                        @endif
                    </div>
                    <span class="inline-flex items-center justify-center px-4 py-2 rounded-full text-sm font-semibold {{ $statusBadge }}">
                        {{ ucfirst($video->status ?? 'pending') }}
                    </span>
                </div>

                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Uploaded {{ format_datetime($video->created_at) }}
                        </div>
                        @if($video->reviewed_at)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Reviewed {{ format_datetime($video->reviewed_at) }}
                        </div>
                        @endif
                        @if($video->reviewer)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Reviewed by {{ $video->reviewer->name }}
                        </div>
                        @endif
                        @if($video->trainer_feedback)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-900 mb-1">Trainer Feedback</p>
                            <p class="text-sm text-gray-700">{{ $video->trainer_feedback }}</p>
                        </div>
                        @elseif($video->status === 'pending')
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800 text-sm">
                            Waiting for trainer feedback. You will be notified once it is reviewed.
                        </div>
                        @elseif($video->status === 'rejected')
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 text-sm">
                            Trainer requested a new recording. Please re-record this exercise.
                        </div>
                        @endif
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('member.workout-plans.show', $video->workout_plan_id) }}" class="inline-flex items-center px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m0 0l3-3m-3 3l3 3" />
                                </svg>
                                View Plan
                            </a>
                            @if($video->status === 'rejected')
                            <a href="{{ route('member.workout-plans.show', $video->workout_plan_id) }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                                </svg>
                                Record Again
                            </a>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($videoExists && $videoUrl)
                        <div class="bg-black rounded-lg overflow-hidden shadow">
                            <video controls preload="metadata" class="w-full" style="max-height: 360px;">
                                <source src="{{ $videoUrl }}" type="video/mp4">
                                <source src="{{ $videoUrl }}" type="video/webm">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        @else
                        <div class="bg-gray-100 border border-gray-200 rounded-lg p-6 text-center">
                            <svg class="mx-auto w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-700">Video preview unavailable</p>
                            <p class="text-xs text-gray-500 mt-1">Original file not found. Please contact support if this happens repeatedly.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($videos->hasPages())
        <div class="mt-8">
            {{ $videos->onEachSide(1)->links() }}
        </div>
        @endif
        @else
        {{-- Empty State --}}
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <svg class="mx-auto h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-6 text-xl font-semibold text-gray-900">No workout videos yet</h3>
            <p class="mt-2 text-sm text-gray-500">
                Record your exercises from any workout plan to see the approval history here.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('member.workout-plans') }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    Go to Workout Plans
                </a>
                <a href="{{ route('member.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                    Back to Dashboard
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection


