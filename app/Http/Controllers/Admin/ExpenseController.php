<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ExpenseDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Repositories\Interfaces\ExpenseRepositoryInterface;
use App\Services\EntityIntegrityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller for managing expenses in the admin panel.
 * 
 * Handles CRUD operations for expense records including creation, updating,
 * deletion, and viewing. Expenses represent outgoing financial transactions
 * for the gym business. Requires appropriate permissions for each operation.
 */
class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepository,
        private readonly EntityIntegrityService $integrityService
    ) {
        $this->middleware('permission:view expenses')->only(['index', 'show']);
        $this->middleware('permission:create expenses')->only(['create', 'store']);
        $this->middleware('permission:edit expenses')->only(['edit', 'update']);
        $this->middleware('permission:delete expenses')->only('destroy');
    }

    /**
     * Display a listing of expenses with filters.
     */
    public function index(ExpenseDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Expense()))->toJson();
        }

        return view('admin.expenses.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only(['category', 'vendor', 'date_from', 'date_to', 'search']),
            'categoryOptions' => $this->expenseRepository->getDistinctCategories(),
            'methodOptions' => \App\Models\Expense::getPaymentMethodOptions(),
        ]);
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(): View
    {
        return view('admin.expenses.create');
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenseRepository->createExpense($request->validated());

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense): View
    {
        return view('admin.expenses.edit', compact('expense'));
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense): View
    {
        return view('admin.expenses.show', compact('expense'));
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenseRepository->updateExpense($expense, $request->validated());

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $blocker = $this->integrityService->firstExpenseDeletionBlocker($expense);

        if ($blocker) {
            return redirect()
                ->route('admin.expenses.index')
                ->with('error', $blocker);
        }

        $this->expenseRepository->deleteExpense($expense);

        return redirect()
            ->route('admin.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}



