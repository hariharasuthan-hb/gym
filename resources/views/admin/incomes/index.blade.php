@extends('admin.layouts.app')

@section('page-title', 'Incomes')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Financial</p>
            <h1 class="text-2xl font-bold text-gray-900">Incomes</h1>
            <p class="text-sm text-gray-500 mt-1">
                Record and review non-subscription income.
            </p>
        </div>
        @can('create incomes')
            <a href="{{ route('admin.incomes.create') }}" class="btn btn-primary">
                Add Income
            </a>
        @endcan
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="admin-card">
        <form method="GET" id="income-filter-form" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label class="form-label" for="search">Search</label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ $filters['search'] ?? '' }}"
                       class="form-input w-full"
                       placeholder="Category, source, notes">
            </div>
            <div>
                <label class="form-label" for="category">Category</label>
                <select name="category" id="category" class="form-select w-full">
                    <option value="">All categories</option>
                    @foreach($categoryOptions as $category)
                        <option value="{{ $category }}" {{ ($filters['category'] ?? '') === $category ? 'selected' : '' }}>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="source">Source</label>
                <input type="text"
                       name="source"
                       id="source"
                       value="{{ $filters['source'] ?? '' }}"
                       class="form-input w-full"
                       placeholder="Source name">
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
                <a href="{{ route('admin.incomes.index') }}" class="btn btn-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- DataTable --}}
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
            function initIncomeFilters() {
                if (typeof window.$ === 'undefined') {
                    return setTimeout(initIncomeFilters, 100);
                }

                const $form = window.$('#income-filter-form');
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
                window.$('#category, #source, #date_from, #date_to').on('change', function () {
                    table.ajax.reload(null, false);
                });
            }

            initIncomeFilters();
        });
    </script>
@endpush

