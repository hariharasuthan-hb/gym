<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\LeadDataTable;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\LeadAccessService;
use App\Services\LeadAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller for managing leads in the admin panel.
 * 
 * Handles CRUD operations for leads including creation, updating, deletion,
 * and assignment. Admins have full access, trainers can view/edit their assigned leads.
 */
class LeadController extends Controller
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly LeadAssignmentService $leadAssignmentService
    ) {
    }

    /**
     * Display a listing of the resource.
     * For trainers: shows only their assigned leads
     * For admins: shows all leads
     */
    public function index(LeadDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Lead))->toJson();
        }
        
        return view('admin.leads.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'trainer']);
        })->get();
        
        return view('admin.leads.create', [
            'users' => $users,
            'statusOptions' => Lead::getStatusOptions(),
            'sourceOptions' => Lead::getSourceOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        // Automatically assign lead to a trainer if not manually assigned
        if (empty($validated['assigned_to'])) {
            $assignedTrainerId = $this->leadAssignmentService->assignLeadAutomatically();
            if ($assignedTrainerId) {
                $validated['assigned_to'] = $assignedTrainerId;
            }
        }

        $this->leadRepository->create($validated);

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead created successfully.');
    }

    /**
     * Display the specified resource.
     * For trainers: can only view their assigned leads
     * For admins: can view any lead
     */
    public function show(Lead $lead): View
    {
        LeadAccessService::ensureCanAccessLead($lead, null, 'view');
        
        $lead->load(['assignedTo', 'createdBy']);
        return view('admin.leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the specified resource.
     * For trainers: can only edit their assigned leads
     * For admins: can edit any lead
     */
    public function edit(Lead $lead): View
    {
        LeadAccessService::ensureCanAccessLead($lead, null, 'edit');
        
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'trainer']);
        })->get();
        
        return view('admin.leads.edit', [
            'lead' => $lead,
            'users' => $users,
            'statusOptions' => Lead::getStatusOptions(),
            'sourceOptions' => Lead::getSourceOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * For trainers: can only update their assigned leads
     * For admins: can update any lead
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        LeadAccessService::ensureCanAccessLead($lead, null, 'update');
        
        $validated = $request->validated();
        
        // If status is changed to 'converted', set converted_at
        if ($validated['status'] === Lead::STATUS_CONVERTED && $lead->status !== Lead::STATUS_CONVERTED) {
            $validated['converted_at'] = now();
        } elseif ($validated['status'] !== Lead::STATUS_CONVERTED) {
            $validated['converted_at'] = null;
        }

        $this->leadRepository->update($lead->id, $validated);

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $this->leadRepository->delete($lead->id);

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead deleted successfully.');
    }
}
