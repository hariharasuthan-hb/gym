{{--
 | Orphaned Videos Index View
 |
 | Displays a list of videos that exist in storage but are not referenced
 | in the database. Allows admins to delete these orphaned videos to free up space.
 |
 | @var \App\DataTables\OrphanedVideoDataTable $dataTable
 | @var array $orphanedVideos - Array of orphaned video information
 | @var int $totalSize - Total size of orphaned videos in bytes
 | @var string $totalSizeFormatted - Formatted total size string
 |
 | Features:
 | - List all orphaned videos with details (path, size, directory, modified date)
 | - Delete single or multiple videos
 | - Show total space that can be freed
--}}
@extends('admin.layouts.app')

@section('page-title', 'Orphaned Videos Management')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Orphaned Videos</h1>
            <p class="text-sm text-gray-600">Videos in storage that are not referenced in the database</p>
        </div>
        <div class="flex items-center gap-3">
            @if(count($orphanedVideos) > 0)
                <form action="{{ route('admin.orphaned-videos.destroy-multiple') }}" method="POST" id="delete-all-form">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="video_paths" id="delete-all-paths" value="">
                    <button type="submit"
                            class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete all {{ count($orphanedVideos) }} orphaned videos? This action cannot be undone.')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete All ({{ count($orphanedVideos) }})
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.orphaned-videos.index') }}" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Warning Message --}}
    @if(session('warning'))
        <div class="alert alert-warning animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <div class="alert alert-danger animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Summary Card --}}
    <div class="admin-card">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="text-sm text-blue-600 font-medium mb-1">Total Orphaned Videos</div>
                <div class="text-2xl font-bold text-blue-900">{{ count($orphanedVideos) }}</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <div class="text-sm text-green-600 font-medium mb-1">Total Size</div>
                <div class="text-2xl font-bold text-green-900">{{ $totalSizeFormatted }}</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <div class="text-sm text-purple-600 font-medium mb-1">Directories Scanned</div>
                <div class="text-2xl font-bold text-purple-900">3</div>
                <div class="text-xs text-purple-500 mt-1">workout-videos, workout-plans/demo-videos, cms/videos</div>
            </div>
        </div>
    </div>

    {{-- DataTable Card --}}
    <div class="admin-card">
        <div class="admin-table-wrapper">
            <table class="admin-table" id="{{ $dataTable->getTableIdPublic() }}">
                <thead>
                    <tr>
                        <th class="text-left">Video Path</th>
                        <th class="text-left">Directory</th>
                        <th class="text-right">Size</th>
                        <th class="text-center">Modified At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables will populate this --}}
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
    {{-- DataTables CSS is imported via Vite in resources/js/admin/app.js --}}
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush

