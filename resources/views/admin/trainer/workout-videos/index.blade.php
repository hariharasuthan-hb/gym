@extends('admin.layouts.app')

@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Workout Video Reviews</h1>
            <p class="text-sm text-gray-600">Review and approve member exercise videos.</p>
        </div>
    </div>

    {{-- Status Filters --}}
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-wrap gap-3">
            @php
                $statuses = [
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'all' => 'All',
                ];
            @endphp
            @foreach($statuses as $key => $label)
                <a href="{{ route('admin.trainer.workout-videos.index', ['status' => $key]) }}"
                   class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium {{ $status === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
                </a>
            @endforeach
        </div>
    </div>

    {{-- Videos List --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($videos as $video)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500">{{ $video->user->name ?? 'Member' }}</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $video->exercise_name }}</p>
                        <p class="text-xs text-gray-500">
                            Uploaded {{ format_datetime_admin($video->created_at) }}
                            @if($video->workoutPlan)
                                • Plan: {{ $video->workoutPlan->plan_name }}
                            @endif
                        </p>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                        {{ $video->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $video->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $video->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($video->status) }}
                    </span>
                </div>

                @php
                    $videoPath = $video->video_path ? Storage::disk('public')->exists($video->video_path) ? Storage::disk('public')->url($video->video_path) : null : null;
                @endphp

                @if($videoPath)
                    <video controls preload="metadata" class="w-full rounded-lg bg-gray-900 mb-4" style="max-height: 320px;">
                        <source src="{{ $videoPath }}" type="video/mp4">
                        <source src="{{ $videoPath }}" type="video/webm">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <div class="p-4 bg-red-50 border border-red-100 rounded mb-4">
                        <p class="text-sm text-red-700">Video file not found.</p>
                    </div>
                @endif

                @if($video->trainer_feedback)
                    <div class="mb-4 p-3 bg-gray-50 rounded border border-gray-100">
                        <p class="text-xs font-semibold text-gray-500">Trainer Feedback</p>
                        <p class="text-sm text-gray-700">{{ $video->trainer_feedback }}</p>
                    </div>
                @endif

                <div class="flex flex-col gap-3">
                    @if($video->status === 'pending')
                        <form method="POST" action="{{ route('admin.trainer.workout-videos.review', $video) }}" class="flex flex-col sm:flex-row gap-3">
                            @csrf
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.trainer.workout-videos.review', $video) }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <textarea name="trainer_feedback" rows="2" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Reason for rejection" required></textarea>
                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 w-full">
                                Reject
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-gray-500">
                            Reviewed {{ format_datetime_admin($video->reviewed_at) }}
                            @if($video->reviewer)
                                • By {{ $video->reviewer->name }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-1 lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="mt-4 text-sm text-gray-600">No workout videos found for this filter.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div>
        {{ $videos->links() }}
    </div>
</div>
@endsection

