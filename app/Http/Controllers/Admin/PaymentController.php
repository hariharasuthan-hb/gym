<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PaymentDataTable;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Contracts\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
        $this->middleware('permission:view payments');
    }

    /**
     * Display a listing of the payments with optional filters.
     */
    public function index(PaymentDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Payment()))->toJson();
        }

        return view('admin.payments.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only(['status', 'method', 'search', 'date_from', 'date_to']),
            'statusOptions' => \App\Models\Payment::getStatusOptions(),
            'methodOptions' => $this->paymentRepository->getDistinctMethods(),
        ]);
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): View
    {
        $payment->load(['user', 'subscription.subscriptionPlan']);

        return view('admin.payments.show', compact('payment'));
    }
}


