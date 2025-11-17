@extends('admin.layouts.app')

@section('page-title', 'Payments')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Financial</p>
            <h1 class="text-2xl font-bold text-gray-900">Payments</h1>
            <p class="text-sm text-gray-500 mt-1">
                Review all payment transactions with quick filtering.
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="admin-card">
        <form method="GET" id="payments-filter-form" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label class="form-label" for="search">Search</label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ $filters['search'] ?? '' }}"
                       class="form-input w-full"
                       placeholder="Transaction ID, user name, email">
            </div>
            <div>
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="">All statuses</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="method">Payment Method</label>
                <select name="method" id="method" class="form-select w-full">
                    <option value="">All methods</option>
                    @foreach($methodOptions as $method)
                        <option value="{{ $method }}" {{ ($filters['method'] ?? '') === $method ? 'selected' : '' }}>
                            {{ ucfirst($method) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="date_from">Date From</label>
                <input type="date"
                       name="date_from"
                       id="date_from"
                       value="{{ $filters['date_from'] ?? '' }}"
                       class="form-input w-full">
            </div>
            <div>
                <label class="form-label" for="date_to">Date To</label>
                <input type="date"
                       name="date_to"
                       id="date_to"
                       value="{{ $filters['date_to'] ?? '' }}"
                       class="form-input w-full">
            </div>
            <div class="md:col-span-2 xl:col-span-4 flex gap-2 items-end">
                <button type="submit" class="btn btn-primary">
                    Apply Filters
                </button>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="admin-card">
        <div class="admin-table-wrapper">
            {!! $dataTable->html()->table(['class' => 'admin-table', 'id' => $dataTable->getTableIdPublic()]) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function initPaymentFilters() {
                if (typeof window.$ === 'undefined') {
                    return setTimeout(initPaymentFilters, 100);
                }

                const $form = window.$('#payments-filter-form');
                const table = window.$('#{{ $dataTable->getTableIdPublic() }}').DataTable();

                $form.on('submit', function (event) {
                    event.preventDefault();
                    // Reload table - filters will be automatically included via ajax.data
                    table.ajax.reload(null, false);
                });

                // Auto-reload on filter change
                // Use debounce for search input to avoid too many requests
                let searchTimeout;
                window.$('#search').on('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        table.ajax.reload(null, false);
                    }, 500); // Wait 500ms after user stops typing
                });
                
                // Immediate reload for other filters
                window.$('#status, #method, #date_from, #date_to').on('change', function () {
                    table.ajax.reload(null, false);
                });
            }

            initPaymentFilters();
        });
    </script>
@endpush

