<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\MonthlyBreakdownDataTable;
use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view finances');
    }

    /**
     * Display the finances overview page.
     */
    public function index(Request $request, FinancialReportService $financialReportService, MonthlyBreakdownDataTable $dataTable)
    {
        $rangeOptions = [3, 6, 12];
        $range = (int) $request->input('range', 6);

        if (!in_array($range, $rangeOptions, true)) {
            $range = 6;
        }

        $monthlyOverview = $financialReportService->getMonthlyOverview($range);
        $currentMonth = $financialReportService->latestMonth($monthlyOverview);
        $trailingTotals = $financialReportService->summarize($monthlyOverview);

        $dataTable->setMonthlyOverview($monthlyOverview);

        if ($request->ajax() || $request->wantsJson()) {
            return $dataTable->dataTable($dataTable->query())->toJson();
        }

        return view('admin.finances.index', [
            'range' => $range,
            'rangeOptions' => $rangeOptions,
            'monthlyOverview' => $monthlyOverview,
            'currentMonth' => $currentMonth,
            'trailingTotals' => $trailingTotals,
            'monthlyDataTable' => $dataTable,
        ]);
    }
}

