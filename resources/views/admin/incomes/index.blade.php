{{--
 | Incomes Index View
 |
 | Displays a list of all income records with filtering capabilities.
 | Income represents incoming financial transactions for the gym business.
 |
 | @var \App\DataTables\IncomeDataTable $dataTable
 | @var array $filters - Current filter values (category, source, date_from, date_to, search)
 | @var array $categoryOptions - Available income category options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by category, source, and date range
 | - Create new income button (if user has permission)
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
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
    @include('admin.components.filter-section', [
        'formId' => 'income-filter-form',
        'clearRoute' => route('admin.incomes.index'),
        'filters' => $filters,
        'fields' => [
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'options' => collect($categoryOptions)->mapWithKeys(fn($c) => [$c => $c])->all()
            ],
            [
                'name' => 'source',
                'label' => 'Source',
                'type' => 'text',
                'placeholder' => 'Source name'
            ],
            [
                'name' => 'date_from',
                'label' => 'Date From',
                'type' => 'date'
            ],
            [
                'name' => 'date_to',
                'label' => 'Date To',
                'type' => 'date'
            ]
        ],
        'localStorageKey' => 'income-filters-collapsed',
        'tableId' => $dataTable->getTableIdPublic(),
        'autoReloadSelectors' => ['category', 'source', 'date_from', 'date_to']
    ])

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
@endpush

