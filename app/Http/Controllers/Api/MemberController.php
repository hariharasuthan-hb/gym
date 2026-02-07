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
        
        return $this->successResponse('Profile retrieved successfully', [
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
        ]);
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
            'gender' => ['nullable', 'in:male,female,other'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        $this->userRepository->updateWithRole($user, $validated);
        $user->refresh();

        return $this->successResponse('Profile updated successfully', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'age' => $user->age,
            'gender' => $user->gender,
            'address' => $user->address,
        ]);
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

        return $this->successResponse('Password updated successfully');
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
        
        // Get active (non-expired) subscription
        $activeSubscription = $user->subscriptions()
            ->active()
            ->with('subscriptionPlan')
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
            $activity = ActivityLog::with('checkedInBy')->where('user_id', $user->id)
                ->where('date', now()->toDateString())
                ->whereNotNull('check_in_time')
                ->latest('check_in_time')
                ->first();
            if ($activity) {
                $todayActivity = $this->formatActivityWithRelations($activity, true);
            }
        }

        // Recent check-in/check-out records (last 10) with relations for dashboard (relative time like UI)
        $recentCheckInCheckOut = ActivityLog::where('user_id', $user->id)
            ->whereNotNull('check_in_time')
            ->with('checkedInBy')
            ->latest('date')
            ->latest('check_in_time')
            ->limit(10)
            ->get()
            ->map(fn ($a) => $this->formatActivityWithRelations($a, true))
            ->values()
            ->all();

        return $this->successResponse('Dashboard data retrieved successfully', [
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
            'recent_check_in_check_out' => $recentCheckInCheckOut,
            'can_track_attendance' => $canTrackAttendance,
        ]);
    }

    /**
     * Format a single activity log with check-in/check-out details and relations.
     *
     * @param ActivityLog $activity
     * @param bool $useRelativeTime When true (dashboard/recent), uses same format as UI e.g. "49 minutes ago". When false (workout plan history), uses clock time e.g. "07:11 PM".
     * @return array<string, mixed>
     */
    private function formatActivityWithRelations(ActivityLog $activity, bool $useRelativeTime = false): array
    {
        $formatTime = $useRelativeTime
            ? fn ($t) => $t ? format_time_smart($t) : null
            : fn ($t) => $t ? format_time($t) : null;

        return [
            'id' => $activity->id,
            'date' => $activity->date?->toDateString(),
            'checked_in' => (bool) $activity->check_in_time,
            'checked_out' => (bool) $activity->check_out_time,
            'check_in_time' => $activity->check_in_time?->format('H:i:s'),
            'check_out_time' => $activity->check_out_time?->format('H:i:s'),
            'check_in_time_formatted' => $formatTime($activity->check_in_time),
            'check_out_time_formatted' => $formatTime($activity->check_out_time),
            'duration_minutes' => $activity->duration_minutes ?? 0,
            'workout_summary' => $activity->workout_summary,
            'check_in_method' => $activity->check_in_method,
            'checked_in_by' => $activity->checkedInBy ? [
                'id' => $activity->checkedInBy->id,
                'name' => $activity->checkedInBy->name,
            ] : null,
        ];
    }

    /**
     * Get member user details with subscription details (using relations).
     *
     * @return JsonResponse
     */
    public function subscriptions(): JsonResponse
    {
        $user = auth()->user()->load(['subscriptions' => function ($query) {
            $query->with('subscriptionPlan')->latest();
        }]);

        $member = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'age' => $user->age,
            'gender' => $user->gender,
            'address' => $user->address,
            'status' => $user->status,
            'created_at' => $user->created_at,
        ];

        $subscriptions = $user->subscriptions->map(function ($subscription) {
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
                'expiration_at' => $subscription->expiration_at,
                'created_at' => $subscription->created_at,
            ];
        })->values()->all();

        return $this->successResponse('Subscriptions retrieved successfully', [
            'user' => $member,
            'subscriptions' => $subscriptions,
        ]);
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
        
        // When status=all (or empty), do not add any status condition to the query
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        $workoutPlans = $query->paginate($perPage);
        
        $workoutPlans->getCollection()->transform(function ($plan) {
            $weeks = $plan->duration_weeks ?? 0;
            $pendingDays = 0;
            $durationLabel = $weeks === 1 ? '1 week' : "{$weeks} weeks";
            if ($plan->start_date && $plan->end_date) {
                $start = $plan->start_date->copy()->startOfDay();
                $end = $plan->end_date->copy()->startOfDay();
                $totalDays = (int) $start->diffInDays($end) + 1;
                $fullWeeks = (int) floor($totalDays / 7);
                $pendingDays = $totalDays % 7;
                if ($fullWeeks > 0) {
                    $weeksPart = $fullWeeks === 1 ? '1 week' : "{$fullWeeks} weeks";
                    $daysPart = $pendingDays === 1 ? '1 day' : "{$pendingDays} days";
                    $durationLabel = $pendingDays > 0 ? "{$weeksPart} {$daysPart}" : $weeksPart;
                } elseif ($pendingDays > 0) {
                    $durationLabel = $pendingDays === 1 ? '1 day' : "{$pendingDays} days";
                }
            }
            $daysRemaining = null;
            if ($plan->end_date && $plan->end_date->startOfDay()->isFuture()) {
                $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($plan->end_date->startOfDay()) + 1);
            }
            $payload = [
                'id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'description' => $plan->description,
                'status' => $plan->status,
                'duration_weeks' => $plan->duration_weeks,
                'duration' => $durationLabel,
                'start_date' => $plan->start_date?->toDateString(),
                'end_date' => $plan->end_date?->toDateString(),
                'created_at' => $plan->created_at?->toIso8601String(),
                'trainer' => $plan->trainer ? [
                    'id' => $plan->trainer->id,
                    'name' => $plan->trainer->name,
                ] : null,
                'exercises' => $plan->exercises ?? [],
            ];
            if ($pendingDays > 0) {
                $payload['pending_days'] = $pendingDays;
            }
            if ($daysRemaining !== null) {
                $payload['days_remaining'] = $daysRemaining;
            }
            return $payload;
        });
        
        return $this->paginatedResponse($workoutPlans, 'Workout plans retrieved successfully');
    }

    /**
     * Get single workout plan view details (with trainer, videos, notes, demo).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showWorkoutPlan(int $id): JsonResponse
    {
        $user = auth()->user();

        $workoutPlan = $user->workoutPlans()
            ->with(['trainer', 'workoutVideos' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($id);

        // Check-in/check-out details for this plan's date range with relations
        $checkInCheckOutDetails = [];
        if ($workoutPlan->start_date && $workoutPlan->end_date) {
            $checkInCheckOutDetails = ActivityLog::where('user_id', $user->id)
                ->whereBetween('date', [$workoutPlan->start_date, $workoutPlan->end_date])
                ->whereNotNull('check_in_time')
                ->with('checkedInBy')
                ->orderByDesc('date')
                ->orderByDesc('check_in_time')
                ->get()
                ->map(fn ($a) => $this->formatActivityWithRelations($a, true))
                ->values()
                ->all();
        }

        $workoutVideos = $workoutPlan->workoutVideos->map(function ($video) {
            return [
                'id' => $video->id,
                'exercise_name' => $video->exercise_name,
                'status' => $video->status,
                'video_url' => $video->video_url,
                'thumbnail_url' => $video->thumbnail_url,
                'duration_seconds' => $video->duration_seconds,
                'trainer_feedback' => $video->trainer_feedback,
                'reviewed_at' => $video->reviewed_at?->toIso8601String(),
                'created_at' => $video->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->successResponse('Workout plan retrieved successfully', [
            'id' => $workoutPlan->id,
            'plan_name' => $workoutPlan->plan_name,
            'description' => $workoutPlan->description,
            'status' => $workoutPlan->status,
            'start_date' => $workoutPlan->start_date?->toIso8601String(),
            'end_date' => $workoutPlan->end_date?->toIso8601String(),
            'duration_weeks' => $workoutPlan->duration_weeks,
            'exercises' => $workoutPlan->exercises ?? [],
            'notes' => $workoutPlan->notes,
            'demo_video_path' => $workoutPlan->demo_video_path,
            'demo_video_url' => $workoutPlan->demo_video_path ? file_url($workoutPlan->demo_video_path) : null,
            'created_at' => $workoutPlan->created_at?->toIso8601String(),
            'updated_at' => $workoutPlan->updated_at?->toIso8601String(),
            'trainer' => $workoutPlan->trainer ? [
                'id' => $workoutPlan->trainer->id,
                'name' => $workoutPlan->trainer->name,
                'email' => $workoutPlan->trainer->email,
            ] : null,
            'workout_videos' => $workoutVideos,
            'check_in_check_out_details' => $checkInCheckOutDetails,
        ]);
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
        
        // When status=all (or empty), do not add any status condition to the query
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        $dietPlans = $query->paginate($perPage);
        
        $dietPlans->getCollection()->transform(function ($plan) {
            $durationLabel = null;
            $pendingDays = 0;
            if ($plan->start_date && $plan->end_date) {
                $start = $plan->start_date->copy()->startOfDay();
                $end = $plan->end_date->copy()->startOfDay();
                $totalDays = (int) $start->diffInDays($end) + 1;
                $fullWeeks = (int) floor($totalDays / 7);
                $pendingDays = $totalDays % 7;
                if ($fullWeeks > 0) {
                    $weeksPart = $fullWeeks === 1 ? '1 week' : "{$fullWeeks} weeks";
                    $daysPart = $pendingDays === 1 ? '1 day' : "{$pendingDays} days";
                    $durationLabel = $pendingDays > 0 ? "{$weeksPart} {$daysPart}" : $weeksPart;
                } elseif ($pendingDays > 0) {
                    $durationLabel = $pendingDays === 1 ? '1 day' : "{$pendingDays} days";
                }
            }
            $daysRemaining = null;
            if ($plan->end_date && $plan->end_date->startOfDay()->isFuture()) {
                $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($plan->end_date->startOfDay()) + 1);
            }
            $payload = [
                'id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'description' => $plan->description,
                'status' => $plan->status,
                'duration' => $durationLabel,
                'start_date' => $plan->start_date?->toDateString(),
                'end_date' => $plan->end_date?->toDateString(),
                'created_at' => $plan->created_at?->toIso8601String(),
                'target_calories' => $plan->target_calories,
                'nutritional_goals' => $plan->nutritional_goals,
                'meal_plan' => $plan->meal_plan ?? [],
                'trainer' => $plan->trainer ? [
                    'id' => $plan->trainer->id,
                    'name' => $plan->trainer->name,
                ] : null,
            ];
            if ($pendingDays > 0) {
                $payload['pending_days'] = $pendingDays;
            }
            if ($daysRemaining !== null) {
                $payload['days_remaining'] = $daysRemaining;
            }
            return $payload;
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

        // Ensure user has active (non-expired) subscription and workout plan
        $hasActiveSubscription = $user->subscriptions()
            ->active()
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

        return $this->successResponse('Check-in successful!', [
            'checked_in' => true,
        ]);
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
        
        return $this->successResponse('Video uploaded successfully. Waiting for trainer approval.', [
            'id' => $workoutVideo->id,
            'exercise_name' => $workoutVideo->exercise_name,
            'status' => $workoutVideo->status,
            'video_url' => $workoutVideo->video_url,
        ]);
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
        
        return $this->successResponse('Attendance marked successfully for today!', [
            'attendance_marked' => true,
        ]);
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
            ->active()
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

        return $this->successResponse('Checkout successful. Enjoy your rest!');
    }
}

