{{--
 | Diet Plans Index View
 |
 | Displays a list of all diet plans with management capabilities.
 | Diet plans are assigned to members by trainers or admins and include meal plans.
 | For trainers: shows only their assigned members' plans.
 |
 | @var \App\DataTables\DietPlanDataTable $dataTable
 |
 | Features:
 | - Create new diet plan button (if user has permission)
 | - DataTable with server-side processing
 | - View, edit, and delete plan actions
 | - Role-based filtering (trainers see only their plans)
--}}
@extends('admin.layouts.app')

@section('page-title', 'Diet Plans')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Diet Plans</h1>
            <p class="text-sm text-gray-600">
                @if(auth()->user()->hasRole('trainer'))
                    Manage diet plans for your members
                @else
                    Manage all diet plans
                @endif
            </p>
        </div>
        @can('create diet plans')
        <a href="{{ route('admin.diet-plans.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Plan
        </a>
        @endcan
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

    {{-- Error Message --}}
    @if(session('error'))
        <div class="alert alert-danger animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('error') }}</span>
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

