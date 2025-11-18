{{--
 | Invoices Index View
 |
 | Displays a list of all invoices with filtering capabilities.
 | Invoices are backed by payment records and represent billing documents.
 |
 | @var \App\DataTables\InvoiceDataTable $dataTable
 | @var array $filters - Current filter values (status, method, date_from, date_to, search)
 | @var array $statusOptions - Available payment status options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by status, payment method, and date range
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
@extends('admin.layouts.app')

@section('page-title', 'Invoices')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Financial</p>
            <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
            <p class="text-sm text-gray-500 mt-1">
                Review generated invoices tied to subscription payments.
            </p>
        </div>
    </div>

    {{-- Filters --}}
    @include('admin.components.filter-section', [
        'formId' => 'invoices-filter-form',
        'clearRoute' => route('admin.invoices.index'),
        'filters' => $filters,
        'fields' => [
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => collect($statusOptions)->mapWithKeys(fn($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()
            ],
            [
                'name' => 'method',
                'label' => 'Payment Method',
                'type' => 'select',
                'options' => collect($methodOptions)->mapWithKeys(fn($m) => [$m => ucfirst($m)])->all()
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
        'localStorageKey' => 'invoices-filters-collapsed',
        'tableId' => $dataTable->getTableIdPublic(),
        'autoReloadSelectors' => ['status', 'method', 'date_from', 'date_to']
    ])

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
@endpush

