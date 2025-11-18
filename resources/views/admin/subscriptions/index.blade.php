{{--
 | Subscriptions Index View
 |
 | Displays a list of all user subscriptions with filtering capabilities.
 | Subscriptions represent active membership plans for gym members.
 |
 | @var \App\DataTables\SubscriptionDataTable $dataTable
 | @var array $filters - Current filter values (status, gateway, search)
 | @var array $statusOptions - Available subscription status options
 | @var array $gatewayOptions - Available payment gateway options
 |
 | Features:
 | - Filter by status, payment gateway, and search
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
 | - View, edit, and cancel subscription actions
--}}
@extends('admin.layouts.app')

@section('page-title', 'Subscriptions Management')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Subscriptions</h1>
            <p class="text-sm text-gray-600">Manage all user subscriptions with advanced filtering</p>
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

    @if(session('info'))
        <div class="alert alert-info animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Filters --}}
    @include('admin.components.filter-section', [
        'formId' => 'filter-form',
        'clearRoute' => route('admin.subscriptions.index'),
        'filters' => $filters,
        'fields' => [
            [
                'name' => 'search',
                'label' => 'Search',
                'type' => 'text',
                'placeholder' => 'User name, email, or plan...'
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => collect($statusOptions)->mapWithKeys(fn($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()
            ],
            [
                'name' => 'gateway',
                'label' => 'Payment Gateway',
                'type' => 'select',
                'options' => collect($gatewayOptions)->mapWithKeys(fn($g) => [$g => ucfirst($g)])->all()
            ]
        ],
        'localStorageKey' => 'subscriptions-filters-collapsed',
        'tableId' => $dataTable->getTableIdPublic(),
        'autoReloadSelectors' => ['search', 'status', 'gateway']
    ])

    {{-- DataTable Card --}}
    <div class="admin-card">
        <div class="admin-table-wrapper">
            {!! $dataTable->html()->table(['class' => 'admin-table', 'id' => $dataTable->getTableIdPublic()]) !!}
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
