<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use App\Models\Lead;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for lead-related endpoints.
 * 
 * Provides JSON API responses for lead functionality.
 * 
 * All endpoints require authentication and admin role.
 */
class LeadController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository
    ) {
    }

    /**
     * Get a paginated list of leads.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'source', 'assigned_to', 'date_from', 'date_to', 'follow_up_date_from', 'follow_up_date_to']);
        $sort = $request->get('sort', ['created_at' => 'desc']);
        $perPage = $request->get('per_page', 15);

        $leads = $this->leadRepository->all($filters, $sort, $perPage);

        return $this->paginatedResponse($leads, 'Leads retrieved successfully');
    }

    /**
     * Get a specific lead by ID.
     * 
     * @param Lead $lead
     * @return JsonResponse
     */
    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['assignedTo', 'createdBy']);

        return $this->successResponse('Lead retrieved successfully', [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'message' => $lead->message,
            'status' => $lead->status,
            'source' => $lead->source,
            'assigned_to' => $lead->assignedTo ? [
                'id' => $lead->assignedTo->id,
                'name' => $lead->assignedTo->name,
            ] : null,
            'notes' => $lead->notes,
            'follow_up_date' => $lead->follow_up_date,
            'converted_at' => $lead->converted_at,
            'created_by' => $lead->createdBy ? [
                'id' => $lead->createdBy->id,
                'name' => $lead->createdBy->name,
            ] : null,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
        ]);
    }

    /**
     * Create a new lead.
     * 
     * @param StoreLeadRequest $request
     * @return JsonResponse
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        $lead = $this->leadRepository->create($validated);
        $lead->load(['assignedTo', 'createdBy']);

        return $this->successResponse('Lead created successfully', [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'status' => $lead->status,
            'source' => $lead->source,
        ], 201);
    }

    /**
     * Update an existing lead.
     * 
     * @param UpdateLeadRequest $request
     * @param Lead $lead
     * @return JsonResponse
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $validated = $request->validated();
        
        // If status is changed to 'converted', set converted_at
        if ($validated['status'] === Lead::STATUS_CONVERTED && $lead->status !== Lead::STATUS_CONVERTED) {
            $validated['converted_at'] = now();
        } elseif ($validated['status'] !== Lead::STATUS_CONVERTED) {
            $validated['converted_at'] = null;
        }

        $this->leadRepository->update($lead->id, $validated);
        $lead->refresh()->load(['assignedTo', 'createdBy']);

        return $this->successResponse('Lead updated successfully', [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'status' => $lead->status,
            'source' => $lead->source,
        ]);
    }

    /**
     * Delete a lead.
     * 
     * @param Lead $lead
     * @return JsonResponse
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $this->leadRepository->delete($lead->id);

        return $this->successResponse('Lead deleted successfully');
    }
}
