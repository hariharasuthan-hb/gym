<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\DietPlanDataTable;
use App\Http\Requests\Admin\StoreDietPlanRequest;
use App\Http\Requests\Admin\UpdateDietPlanRequest;
use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Services\EntityIntegrityService;

/**
 * Controller for managing diet plans in the admin panel.
 * 
 * Handles CRUD operations for diet plans including creation, updating,
 * deletion, and viewing. Diet plans are assigned to members by trainers
 * or admins and include meal plans and nutritional information. Accessible
 * by both admin and trainer roles with appropriate permissions.
 */
class DietPlanController extends Controller
{
    public function __construct(
        private readonly EntityIntegrityService $entityIntegrityService
    ) {
    }

    /**
     * Display a listing of diet plans.
     * Accessible by both admin and trainer (filtered by permission).
     */
    public function index(DietPlanDataTable $dataTable)
    {
        DietPlan::autoCompleteExpired();

        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new DietPlan))->toJson();
        }
        
        return view('admin.diet-plans.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new diet plan.
     */
    public function create(): View
    {
        $user = auth()->user();
        
        // Get members based on role
        if ($user->hasRole('trainer')) {
            // Trainers see their assigned members or members without active plans
            $memberIds = DietPlan::where('trainer_id', $user->id)
                ->pluck('member_id')
                ->unique();
            
            $members = User::role('member')
                ->where(function ($query) use ($memberIds, $user) {
                    $query->whereIn('id', $memberIds)
                        ->orWhereDoesntHave('dietPlans', function ($q) use ($user) {
                            $q->where('trainer_id', $user->id)->where('status', 'active');
                        });
                })
                ->get();
            
            $trainers = collect(); // Trainers don't need trainer list
        } else {
            // Admins see all members and all trainers
            $members = User::role('member')->get();
            $trainers = User::role('trainer')->get();
        }
        
        return view('admin.diet-plans.create', compact('members', 'trainers'));
    }

    /**
     * Store a newly created diet plan in storage.
     */
    public function store(StoreDietPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Auto-set trainer_id for trainers, use from request for admins
        if (auth()->user()->hasRole('trainer')) {
            $validated['trainer_id'] = auth()->id();
        }
        // For admins, trainer_id should come from the form request
        
        // Handle meal plan - convert from array or JSON string to JSON
        if ($request->has('meal_plan_json') && !empty($request->meal_plan_json)) {
            $validated['meal_plan'] = json_decode($request->meal_plan_json, true);
        } elseif ($request->has('meals') && is_array($request->meals)) {
            // Filter out empty values and convert to JSON array
            $meals = array_filter(array_map('trim', $request->meals), function($value) {
                return !empty($value);
            });
            $validated['meal_plan'] = !empty($meals) ? array_values($meals) : null;
        } else {
            $validated['meal_plan'] = null;
        }
        
        // Remove temporary fields
        unset($validated['meal_plan_json']);
        
        DietPlan::create($validated);

        return redirect()->route('admin.diet-plans.index')
            ->with('success', 'Diet plan created successfully.');
    }

    /**
     * Display the specified diet plan.
     */
    public function show(DietPlan $dietPlan): View
    {
        DietPlan::autoCompleteExpired();

        // Check if trainer can view this plan
        if (auth()->user()->hasRole('trainer') && $dietPlan->trainer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        $dietPlan->load(['trainer', 'member']);
        
        return view('admin.diet-plans.show', compact('dietPlan'));
    }

    /**
     * Show the form for editing the specified diet plan.
     */
    public function edit(DietPlan $dietPlan): View
    {
        // Check if trainer can edit this plan
        if (auth()->user()->hasRole('trainer') && $dietPlan->trainer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        $user = auth()->user();
        
        // Get members based on role
        if ($user->hasRole('trainer')) {
            $memberIds = DietPlan::where('trainer_id', $user->id)
                ->pluck('member_id')
                ->unique();
            $members = User::whereIn('id', $memberIds)
                ->orWhere('id', $dietPlan->member_id) // Include current member
                ->role('member')
                ->get();
            $trainers = collect(); // Trainers don't need trainer list
        } else {
            $members = User::role('member')->get();
            $trainers = User::role('trainer')->get();
        }
        
        $dietPlan->load(['trainer', 'member']);
        
        return view('admin.diet-plans.edit', compact('dietPlan', 'members', 'trainers'));
    }

    /**
     * Update the specified diet plan in storage.
     */
    public function update(UpdateDietPlanRequest $request, DietPlan $dietPlan): RedirectResponse
    {
        $validated = $request->validated();
        
        // Handle meal plan - convert from array or JSON string to JSON
        if ($request->has('meal_plan_json') && !empty($request->meal_plan_json)) {
            $validated['meal_plan'] = json_decode($request->meal_plan_json, true);
        } elseif ($request->has('meals') && is_array($request->meals)) {
            // Filter out empty values and convert to JSON array
            $meals = array_filter(array_map('trim', $request->meals), function($value) {
                return !empty($value);
            });
            $validated['meal_plan'] = !empty($meals) ? array_values($meals) : null;
        } else {
            $validated['meal_plan'] = null;
        }
        
        // Remove temporary fields
        unset($validated['meal_plan_json']);
        
        $dietPlan->update($validated);

        return redirect()->route('admin.diet-plans.index')
            ->with('success', 'Diet plan updated successfully.');
    }

    /**
     * Remove the specified diet plan from storage.
     */
    public function destroy(DietPlan $dietPlan): RedirectResponse
    {
        // Check if trainer can delete this plan
        if (auth()->user()->hasRole('trainer') && $dietPlan->trainer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if ($reason = $this->entityIntegrityService->firstDietPlanDeletionBlocker($dietPlan)) {
            return redirect()->route('admin.diet-plans.index')
                ->with('error', $reason);
        }

        $dietPlan->delete();

        return redirect()->route('admin.diet-plans.index')
            ->with('success', 'Diet plan deleted successfully.');
    }
}

