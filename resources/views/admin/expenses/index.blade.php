{{--
 | Expenses Index View
 |
 | Displays a list of all expense records with filtering capabilities.
 | Expenses represent outgoing financial transactions for the gym business.
 |
 | @var \App\DataTables\ExpenseDataTable $dataTable
 | @var array $filters - Current filter values (category, vendor, date_from, date_to, search)
 | @var array $categoryOptions - Available expense category options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by category, vendor, and date range
 | - Create new expense button (if user has permission)
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
@extends('admin.layouts.app')

@section('page-title', 'Expenses')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Financial</p>
            <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
            <p class="text-sm text-gray-500 mt-1">
                Record and review operational spending.
            </p>
        </div>
        @can('create expenses')
            <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">
                Add Expense
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
        'formId' => 'expense-filter-form',
        'clearRoute' => route('admin.expenses.index'),
        'filters' => $filters,
        'fields' => [
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'options' => collect($categoryOptions)->mapWithKeys(fn($c) => [$c => $c])->all()
            ],
            [
                'name' => 'vendor',
                'label' => 'Vendor',
                'type' => 'text',
                'placeholder' => 'Vendor name'
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
        'localStorageKey' => 'expense-filters-collapsed',
        'tableId' => $dataTable->getTableIdPublic(),
        'autoReloadSelectors' => ['category', 'vendor', 'date_from', 'date_to']
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

