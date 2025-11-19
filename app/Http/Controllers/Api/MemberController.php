<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AutoCheckoutMemberJob;
use App\Models\ActivityLog;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WorkoutVideoRepositoryInterface;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * API Controller for member-related endpoints.
 * 
 * Reuses existing MemberController logic and repositories
 * to provide JSON API responses for member functionality.
 * 
 * All endpoints require authentication and member role.
 */
class MemberController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WorkoutVideoRepositoryInterface $workoutVideoRepository
    ) {
    }

    /**
     * Get authenticated member's profile.
     * 
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user()->load('roles');
        
        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'age' => $user->age,
            'gender' => $user->gender,
            'address' => $user->address,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], 'Profile retrieved successfully');
    }

    /**
     * Update member profile information.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->userRepository->updateWithRole($user, $validated);
        $user->refresh();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
        ], 'Profile updated successfully');
    }

    /**
     * Update member password.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse(null, 'Password updated successfully');
    }

    /**
     * Get member dashboard data.
     * 
     * Reuses logic from FrontendMemberController::dashboard()
     * 
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        \App\Models\DietPlan::autoCompleteExpired();
        
        // Get active subscription
        $activeSubscription = $user->subscriptions()
            ->with('subscriptionPlan')
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                      ->orWhere('next_billing_at', '>=', now());
            })
            ->first();
        
        // Get active subscription plans if no active subscription
        $subscriptionPlans = null;
        if (!$activeSubscription) {
            $subscriptionPlans = \App\Models\SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'plan_name' => $plan->plan_name,
                        'description' => $plan->description,
                        'price' => $plan->price,
                        'duration' => $plan->duration,
                        'duration_type' => $plan->duration_type,
                    ];
                });
        }
        
        // Get active workout plans
        $activeWorkoutPlans = $user->workoutPlans()
            ->with('trainer')
            ->where('status', 'active')
            ->latest('start_date')
            ->limit(3)
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'trainer' => $plan->trainer ? [
                        'id' => $plan->trainer->id,
                        'name' => $plan->trainer->name,
                    ] : null,
                    'start_date' => $plan->start_date,
                    'end_date' => $plan->end_date,
                ];
            });
        
        // Get active diet plans
        $activeDietPlans = $user->dietPlans()
            ->with('trainer')
            ->where('status', 'active')
            ->latest('start_date')
            ->limit(3)
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'trainer' => $plan->trainer ? [
                        'id' => $plan->trainer->id,
                        'name' => $plan->trainer->name,
                    ] : null,
                    'start_date' => $plan->start_date,
                    'end_date' => $plan->end_date,
                ];
            });
        
        // Count totals
        $stats = [
            'total_workout_plans' => $user->workoutPlans()->where('status', 'active')->count(),
            'total_diet_plans' => $user->dietPlans()->where('status', 'active')->count(),
            'total_activities' => \App\Models\ActivityLog::where('user_id', $user->id)->count(),
        ];
        
        // Today's activity
        $hasActiveSubscription = (bool) $activeSubscription;
        $hasActiveWorkoutPlan = $activeWorkoutPlans->isNotEmpty();
        $canTrackAttendance = $hasActiveWorkoutPlan && $hasActiveSubscription;
        
        $todayActivity = null;
        if ($canTrackAttendance) {
            $activity = \App\Models\ActivityLog::todayForUser($user->id);
            if ($activity) {
                $todayActivity = [
                    'checked_in' => (bool) $activity->check_in_time,
                    'checked_out' => (bool) $activity->check_out_time,
                    'check_in_time' => $activity->check_in_time?->format('H:i:s'),
                    'check_out_time' => $activity->check_out_time?->format('H:i:s'),
                ];
            }
        }
        
        return $this->successResponse([
            'active_subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'status' => $activeSubscription->status,
                'plan' => $activeSubscription->subscriptionPlan ? [
                    'id' => $activeSubscription->subscriptionPlan->id,
                    'plan_name' => $activeSubscription->subscriptionPlan->plan_name,
                    'price' => $activeSubscription->subscriptionPlan->price,
                ] : null,
                'next_billing_at' => $activeSubscription->next_billing_at,
            ] : null,
            'subscription_plans' => $subscriptionPlans,
            'active_workout_plans' => $activeWorkoutPlans,
            'active_diet_plans' => $activeDietPlans,
            'stats' => $stats,
            'today_activity' => $todayActivity,
            'can_track_attendance' => $canTrackAttendance,
        ], 'Dashboard data retrieved successfully');
    }

    /**
     * Get member subscriptions.
     * 
     * @return JsonResponse
     */
    public function subscriptions(): JsonResponse
    {
        $user = auth()->user();
        
        $subscriptions = $user->subscriptions()
            ->with('subscriptionPlan')
            ->latest()
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'plan' => $subscription->subscriptionPlan ? [
                        'id' => $subscription->subscriptionPlan->id,
                        'plan_name' => $subscription->subscriptionPlan->plan_name,
                        'price' => $subscription->subscriptionPlan->price,
                        'duration' => $subscription->subscriptionPlan->duration,
                        'duration_type' => $subscription->subscriptionPlan->duration_type,
                    ] : null,
                    'started_at' => $subscription->started_at,
                    'next_billing_at' => $subscription->next_billing_at,
                    'created_at' => $subscription->created_at,
                ];
            });
        
        return $this->successResponse($subscriptions, 'Subscriptions retrieved successfully');
    }

    /**
     * Get member activities with pagination.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function activities(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $perPage = $request->get('per_page', 15);
        
        $activities = \App\Models\ActivityLog::where('user_id', $user->id)
            ->latest('date')
            ->paginate($perPage);
        
        $activities->getCollection()->transform(function ($activity) {
            return [
                'id' => $activity->id,
                'date' => $activity->date,
                'check_in_time' => $activity->check_in_time?->format('H:i:s'),
                'check_out_time' => $activity->check_out_time?->format('H:i:s'),
                'duration_minutes' => $activity->duration_minutes,
                'workout_summary' => $activity->workout_summary,
                'exercises_done' => $activity->exercises_done,
            ];
        });
        
        return $this->paginatedResponse($activities, 'Activities retrieved successfully');
    }

    /**
     * Get member workout plans with pagination.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function workoutPlans(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status', 'all');
        
        $query = $user->workoutPlans()
            ->with('trainer')
            ->latest('start_date');
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $workoutPlans = $query->paginate($perPage);
        
        $workoutPlans->getCollection()->transform(function ($plan) {
            return [
                'id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'description' => $plan->description,
                'status' => $plan->status,
                'start_date' => $plan->start_date,
                'end_date' => $plan->end_date,
                'trainer' => $plan->trainer ? [
                    'id' => $plan->trainer->id,
                    'name' => $plan->trainer->name,
                ] : null,
                'exercises' => $plan->exercises,
            ];
        });
        
        return $this->paginatedResponse($workoutPlans, 'Workout plans retrieved successfully');
    }

    /**
     * Get single workout plan details.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function showWorkoutPlan(int $id): JsonResponse
    {
        $user = auth()->user();
        
        $workoutPlan = $user->workoutPlans()
            ->with('trainer')
            ->findOrFail($id);
        
        return $this->successResponse([
            'id' => $workoutPlan->id,
            'plan_name' => $workoutPlan->plan_name,
            'description' => $workoutPlan->description,
            'status' => $workoutPlan->status,
            'start_date' => $workoutPlan->start_date,
            'end_date' => $workoutPlan->end_date,
            'exercises' => $workoutPlan->exercises,
            'trainer' => $workoutPlan->trainer ? [
                'id' => $workoutPlan->trainer->id,
                'name' => $workoutPlan->trainer->name,
                'email' => $workoutPlan->trainer->email,
            ] : null,
        ], 'Workout plan retrieved successfully');
    }

    /**
     * Get member workout videos with pagination.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function workoutVideos(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'status' => ['nullable', 'in:all,pending,approved,rejected'],
            'plan_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        
        $perPage = $validated['per_page'] ?? 15;
        $status = $validated['status'] ?? 'all';
        $planId = $validated['plan_id'] ?? null;
        
        $query = $user->workoutVideos()
            ->with(['workoutPlan.trainer', 'reviewer'])
            ->latest();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($planId) {
            $query->where('workout_plan_id', $planId);
        }
        
        $videos = $query->paginate($perPage);
        
        $videos->getCollection()->transform(function ($video) {
            return [
                'id' => $video->id,
                'exercise_name' => $video->exercise_name,
                'status' => $video->status,
                'video_url' => $video->video_url,
                'thumbnail_url' => $video->thumbnail_url,
                'duration_seconds' => $video->duration_seconds,
                'trainer_feedback' => $video->trainer_feedback,
                'workout_plan' => $video->workoutPlan ? [
                    'id' => $video->workoutPlan->id,
                    'plan_name' => $video->workoutPlan->plan_name,
                ] : null,
                'reviewed_at' => $video->reviewed_at,
                'created_at' => $video->created_at,
            ];
        });
        
        return $this->paginatedResponse($videos, 'Workout videos retrieved successfully');
    }

    /**
     * Get member diet plans with pagination.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function dietPlans(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        \App\Models\DietPlan::autoCompleteExpired();
        
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status', 'all');
        
        $query = $user->dietPlans()
            ->with('trainer')
            ->latest('start_date');
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $dietPlans = $query->paginate($perPage);
        
        $dietPlans->getCollection()->transform(function ($plan) {
            return [
                'id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'description' => $plan->description,
                'status' => $plan->status,
                'start_date' => $plan->start_date,
                'end_date' => $plan->end_date,
                'meals' => $plan->meals,
                'trainer' => $plan->trainer ? [
                    'id' => $plan->trainer->id,
                    'name' => $plan->trainer->name,
                ] : null,
            ];
        });
        
        return $this->paginatedResponse($dietPlans, 'Diet plans retrieved successfully');
    }

    /**
     * Check in - Reuses logic from FrontendMemberController.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIn(Request $request): JsonResponse
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Ensure user has active subscription and workout plan
        $hasActiveSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                    ->orWhere('next_billing_at', '>=', now());
            })
            ->exists();

        if (!$hasActiveSubscription) {
            return $this->unauthorizedResponse('You need an active subscription to check in.');
        }

        $hasActiveWorkoutPlan = $user->workoutPlans()->where('status', 'active')->exists();
        if (!$hasActiveWorkoutPlan) {
            return $this->unauthorizedResponse('You need an active workout plan to check in.');
        }
        
        // Check if already checked in today
        $existingCheckIn = ActivityLog::todayForUser($user->id);
        if ($existingCheckIn && $existingCheckIn->check_in_time) {
            return $this->errorResponse('You have already checked in today.', 400, ['checked_in' => true]);
        }
        
        // Create check-in record
        $activityLog = ActivityLog::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in_time' => now(),
            'check_in_method' => 'manual',
            'workout_summary' => 'Manual check-in',
        ]);

        // Schedule automatic checkout at end of day
        $delayUntil = Carbon::parse($today)->endOfDay();
        AutoCheckoutMemberJob::dispatch($activityLog->id)->delay($delayUntil);

        return $this->successResponse(['checked_in' => true], 'Check-in successful!');
    }

    /**
     * Upload workout video - Reuses logic from FrontendMemberController.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function uploadWorkoutVideo(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $workoutPlan = \App\Models\WorkoutPlan::findOrFail($id);
        
        // Ensure the plan belongs to the authenticated member
        if ($workoutPlan->member_id !== $user->id) {
            return $this->unauthorizedResponse('Unauthorized access to this workout plan.');
        }
        
        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,webm,mov', 'max:102400'], // Max 100MB
            'exercise_name' => ['required', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);
        
        // Upload video using service
        $videoService = app(\App\Services\WorkoutVideoService::class);
        $workoutVideo = $videoService->uploadVideo(
            $workoutPlan,
            $user,
            $request->input('exercise_name'),
            $request->file('video'),
            $request->input('duration_seconds', 60)
        );
        
        return $this->successResponse([
            'id' => $workoutVideo->id,
            'exercise_name' => $workoutVideo->exercise_name,
            'status' => $workoutVideo->status,
            'video_url' => $workoutVideo->video_url,
        ], 'Video uploaded successfully. Waiting for trainer approval.');
    }

    /**
     * Mark attendance - Reuses logic from FrontendMemberController.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAttendance(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $workoutPlan = \App\Models\WorkoutPlan::findOrFail($id);
        
        // Ensure the plan belongs to the authenticated member
        if ($workoutPlan->member_id !== $user->id) {
            return $this->unauthorizedResponse('Unauthorized access to this workout plan.');
        }
        
        // Check if all videos are uploaded
        $allUploaded = $this->workoutVideoRepository->checkAllExercisesUploadedToday($workoutPlan, $user);
        
        if (!$allUploaded) {
            return $this->errorResponse('Not all videos uploaded yet.', 400);
        }
        
        // Mark attendance
        $today = now()->toDateString();
        $exercises = is_array($workoutPlan->exercises) ? $workoutPlan->exercises : [];
        
        $existingAttendance = \App\Models\ActivityLog::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_time')
            ->first();
        
        if (!$existingAttendance) {
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'date' => $today,
                'check_in_time' => now(),
                'check_in_method' => 'manual',
                'workout_summary' => 'Completed workout plan: ' . $workoutPlan->plan_name,
                'exercises_done' => $exercises,
                'duration_minutes' => count($exercises) * 5,
            ]);
        }
        
        return $this->successResponse(['attendance_marked' => true], 'Attendance marked successfully for today!');
    }

    /**
     * Check out - Reuses logic from FrontendMemberController.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkOut(Request $request): JsonResponse
    {
        $user = auth()->user();

        $hasActiveSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                    ->orWhere('next_billing_at', '>=', now());
            })
            ->exists();

        if (!$hasActiveSubscription) {
            return $this->unauthorizedResponse('You need an active subscription to check out.');
        }

        $hasActiveWorkoutPlan = $user->workoutPlans()->where('status', 'active')->exists();
        if (!$hasActiveWorkoutPlan) {
            return $this->unauthorizedResponse('You need an active workout plan to check out.');
        }

        $todayActivity = ActivityLog::todayForUser($user->id);

        if (!$todayActivity || !$todayActivity->check_in_time) {
            return $this->errorResponse('No active check-in found for today.', 400);
        }

        if ($todayActivity->check_out_time) {
            return $this->errorResponse('You have already checked out today.', 400);
        }

        $checkoutTime = now();
        $todayActivity->check_out_time = $checkoutTime;
        $todayActivity->duration_minutes = $todayActivity->check_in_time
            ? $todayActivity->check_in_time->diffInMinutes($checkoutTime)
            : 0;
        $todayActivity->save();

        return $this->successResponse(null, 'Checkout successful. Enjoy your rest!');
    }
}

