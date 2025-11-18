{{--
 | Finances Overview Index View
 |
 | Displays financial overview with monthly breakdowns of income, expenses, and net profit.
 | Provides financial analytics with configurable time ranges (3, 6, or 12 months).
 |
 | @var int $range - Selected time range in months (3, 6, or 12)
 | @var array $rangeOptions - Available range options [3, 6, 12]
 | @var array $monthlyOverview - Monthly financial data
 | @var array $currentMonth - Current month's financial summary
 | @var array $trailingTotals - Totals for the selected range
 | @var \App\DataTables\MonthlyBreakdownDataTable $monthlyDataTable
 |
 | Features:
 | - Monthly revenue, expenses, and margin breakdown
 | - Configurable time range selector
 | - DataTable with monthly breakdown
 | - Quick access to create expenses
--}}
@extends('admin.layouts.app')

@section('page-title', 'Finances Overview')

@section('content')
<div class="space-y-6">
    {{-- Page Heading --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Financial Health</p>
            <h1 class="text-2xl font-bold text-gray-900">Finances Overview</h1>
            <p class="text-sm text-gray-500 mt-1">
                Track monthly expenses, revenue, and profitability at a glance.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <form method="GET" class="flex items-center gap-3">
                <label for="range" class="text-sm font-medium text-gray-600">Range</label>
                <select id="range" name="range"
                        class="form-select min-w-[140px]"
                        onchange="this.form.submit()">
                    @foreach($rangeOptions as $option)
                        <option value="{{ $option }}" {{ (int)$range === $option ? 'selected' : '' }}>
                            Last {{ $option }} months
                        </option>
                    @endforeach
                </select>
            </form>
            @can('create expenses')
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Expense
                </a>
            @endcan
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Current Month Revenue</p>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($currentMonth['revenue'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Current Month Expenses</p>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($currentMonth['expenses'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Current Month Net Profit</p>
            <p class="text-3xl font-bold {{ $currentMonth['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                ${{ number_format($currentMonth['net_profit'], 2) }}
            </p>
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Profit Margin</p>
            @if(!is_null($currentMonth['margin']))
                <p class="text-3xl font-bold text-gray-900">{{ number_format($currentMonth['margin'], 2) }}%</p>
            @else
                <p class="text-3xl font-bold text-gray-900">—</p>
            @endif
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
    </div>

    {{-- Trailing Totals --}}
    <div class="admin-card">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Trailing {{ $range }} Month Totals</h2>
                <p class="text-sm text-gray-500">Aggregated performance for the selected period.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                <p class="text-sm text-blue-600 mb-1">Revenue</p>
                <p class="text-2xl font-bold text-blue-900">${{ number_format($trailingTotals['revenue'], 2) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100">
                <p class="text-sm text-rose-600 mb-1">Expenses</p>
                <p class="text-2xl font-bold text-rose-900">${{ number_format($trailingTotals['expenses'], 2) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                <p class="text-sm text-emerald-600 mb-1">Net Profit</p>
                <p class="text-2xl font-bold text-emerald-900">${{ number_format($trailingTotals['net_profit'], 2) }}</p>
                <p class="text-xs text-emerald-600 mt-1">
                    Margin: {{ $trailingTotals['margin'] !== null ? number_format($trailingTotals['margin'], 2) . '%' : '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Monthly Breakdown --}}
    <div class="admin-card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Monthly Breakdown</h2>
                <p class="text-sm text-gray-500">Detailed view of revenue, expenses, and profit by month.</p>
            </div>
        </div>
        <div class="admin-table-wrapper">
            {!! $monthlyDataTable->html()->table(['class' => 'admin-table', 'id' => $monthlyDataTable->getTableIdPublic()]) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $monthlyDataTable->scripts() !!}
@endpush

