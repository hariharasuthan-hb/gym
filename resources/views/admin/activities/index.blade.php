{{--
 | Activity Logs Index View
 |
 | Displays a list of all activity logs (member check-ins) with filtering capabilities.
 | Activity logs record when members visit the gym and check in.
 | For trainers: shows only their assigned members' activities.
 |
 | @var \App\DataTables\ActivityLogDataTable $dataTable
 |
 | Features:
 | - DataTable with server-side processing
 | - Role-based filtering (trainers see only their members)
 | - View activity details
--}}
@extends('admin.layouts.app')

@section('page-title', 'Attendance & Activity')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Attendance & Activity</h1>
            <p class="text-sm text-gray-600">
                @if(auth()->user()->hasRole('trainer'))
                    Monitor check-ins and workout activities of your members
                @else
                    Monitor all member check-ins and workout activities
                @endif
            </p>
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

    {{-- DataTable Card --}}
    <div class="admin-card">
        <div class="admin-table-wrapper">
            {!! $dataTable->html()->table(['class' => 'admin-table', 'id' => $dataTable->getTableIdPublic()]) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush

