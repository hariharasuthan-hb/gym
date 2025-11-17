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

    {{-- Advanced Filters Card --}}
    <div class="admin-card">
        <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
            <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <h2 class="text-base font-semibold text-gray-900">Advanced Filters</h2>
        </div>
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search
                </label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="User name, email, or plan..."
                    class="form-input w-full"
                >
            </div>

            {{-- Status Filter --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Status
                </label>
                <select id="status" name="status" class="form-select w-full">
                    <option value="">All Statuses</option>
                    <option value="trialing" {{ ($filters['status'] ?? '') === 'trialing' ? 'selected' : '' }}>Trialing</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="canceled" {{ ($filters['status'] ?? '') === 'canceled' ? 'selected' : '' }}>Canceled</option>
                    <option value="past_due" {{ ($filters['status'] ?? '') === 'past_due' ? 'selected' : '' }}>Past Due</option>
                    <option value="expired" {{ ($filters['status'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            {{-- Gateway Filter --}}
            <div>
                <label for="gateway" class="block text-sm font-medium text-gray-700 mb-1">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Payment Gateway
                </label>
                <select id="gateway" name="gateway" class="form-select w-full">
                    <option value="">All Gateways</option>
                    <option value="stripe" {{ ($filters['gateway'] ?? '') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="razorpay" {{ ($filters['gateway'] ?? '') === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                </select>
            </div>

            {{-- Filter Buttons --}}
            <div class="md:col-span-3 flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Apply Filters
                </button>
                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

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
    <script>
        // Wait for jQuery and DataTable to be available
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for jQuery to be available
            function initFilters() {
                if (typeof window.$ === 'undefined') {
                    setTimeout(initFilters, 100);
                    return;
                }
                
                const table = window.$('#{{ $dataTable->getTableIdPublic() }}').DataTable();
                
                // Reload table when filters change
                window.$('#filter-form').on('submit', function(e) {
                    e.preventDefault();
                    // Reload table - filters will be automatically included via ajax.data
                    table.ajax.reload(null, false);
                });

                // Auto-reload on filter change
                window.$('#search, #status, #gateway').on('change input', function() {
                    table.ajax.reload(null, false);
                });
            }
            
            initFilters();
        });
    </script>
@endpush
