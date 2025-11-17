<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WorkoutVideoRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WorkoutVideoRepositoryInterface $workoutVideoRepository
    ) {
    }

    /**
     * Show member registration form.
     */
    public function register(): View
    {
        return view('frontend.member.register');
    }

    /**
     * Store new member registration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Get member role ID
        $memberRole = Role::where('name', 'member')->first();
        
        if (!$memberRole) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Member role not found. Please contact administrator.']);
        }

        // Add role ID to validated data
        $validated['role'] = $memberRole->id;

        // Create user with member role using repository
        $this->userRepository->createWithRole($validated);

        return redirect()->route('login')
->with('success', 'Registration successful! Please login.');
    }

    /**
     * Show member dashboard.
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        \App\Models\DietPlan::autoCompleteExpired();
        
        // Check if user has an active subscription
        $activeSubscription = $user->subscriptions()
            ->with('subscriptionPlan')
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                      ->orWhere('next_billing_at', '>=', now());
            })
            ->first();
        
        // Get active subscription plans if user has no active subscription
        $subscriptionPlans = null;
        if (!$activeSubscription) {
            $subscriptionPlans = SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get();
        }
        
        // Get active workout plans for the member
        $activeWorkoutPlans = $user->workoutPlans()
            ->with('trainer')
            ->where('status', 'active')
            ->latest('start_date')
            ->limit(3)
            ->get();
        $todayRecordingProgress = null;
        
        // Get active diet plans for the member
        $activeDietPlans = $user->dietPlans()
            ->with('trainer')
            ->where('status', 'active')
            ->latest('start_date')
            ->limit(3)
            ->get();
        
        // Count totals for stats
        $totalWorkoutPlans = $user->workoutPlans()->where('status', 'active')->count();
        $totalDietPlans = $user->dietPlans()->where('status', 'active')->count();
        $totalActivities = \App\Models\ActivityLog::where('user_id', $user->id)->count();

        // Build today's recording progress summary using first active workout plan
        if ($activeWorkoutPlans->count() > 0) {
            $primaryPlan = $activeWorkoutPlans->first();
            $planExercises = is_array($primaryPlan->exercises) ? $primaryPlan->exercises : [];
            $totalExercises = count($planExercises);
            $today = now()->toDateString();
            $todayVideos = $this->workoutVideoRepository->getVideosUploadedOnDate($primaryPlan, $user, $today);
            $todayRecordedExercises = $todayVideos->pluck('exercise_name')->unique()->toArray();
            $recordedCount = count($todayRecordedExercises);
            $percent = $totalExercises > 0 ? round(($recordedCount / $totalExercises) * 100, 1) : 0;
            $attendanceMarked = \App\Models\ActivityLog::where('user_id', $user->id)
                ->where('date', $today)
                ->whereNotNull('check_in_time')
                ->exists();
            $todayRecordingProgress = [
                'plan' => $primaryPlan,
                'recorded_count' => $recordedCount,
                'total_exercises' => $totalExercises,
                'percent' => $percent,
                'attendance_marked' => $attendanceMarked,
            ];
        }
        
        return view('frontend.member.dashboard', compact(
            'activeSubscription', 
            'subscriptionPlans',
            'activeWorkoutPlans',
            'activeDietPlans',
            'totalWorkoutPlans',
            'totalDietPlans',
            'totalActivities',
            'todayRecordingProgress'
        ));
    }

    /**
     * Show member profile.
     */
    public function profile(): View
    {
        $user = auth()->user();
        return view('frontend.member.profile', compact('user'));
    }

    /**
     * Update member profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);

        return redirect()->route('member.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update member password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('member.profile')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Show member subscriptions.
     */
    public function subscriptions(): View
    {
        return view('frontend.member.subscriptions');
    }

    /**
     * Show member activities.
     */
    public function activities(): View
    {
        return view('frontend.member.activities');
    }

    /**
     * Show member workout plans.
     */
    public function workoutPlans(Request $request): View
    {
        $user = auth()->user();
        
        // Get all workout plans for the member with trainer relationship
        $query = $user->workoutPlans()
            ->with('trainer')
            ->latest('start_date');
        
        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Paginate results
        $workoutPlans = $query->paginate(12)->withQueryString();
        
        // Get counts by status for filter tabs
        $statusCounts = [
            'all' => $user->workoutPlans()->count(),
            'active' => $user->workoutPlans()->where('status', 'active')->count(),
            'completed' => $user->workoutPlans()->where('status', 'completed')->count(),
            'paused' => $user->workoutPlans()->where('status', 'paused')->count(),
            'cancelled' => $user->workoutPlans()->where('status', 'cancelled')->count(),
        ];
        
        return view('frontend.member.workout-plans', compact('workoutPlans', 'statusCounts'));
    }

    /**
     * Show member workout plan details.
     */
    public function showWorkoutPlan(\App\Models\WorkoutPlan $workoutPlan): View
    {
        $user = auth()->user();
        
        // Ensure the plan belongs to the authenticated member
        if ($workoutPlan->member_id !== $user->id) {
            abort(403, 'Unauthorized access to this workout plan.');
        }
        
        // Load relationships
        $workoutPlan->load(['trainer', 'member']);
        
        // Calculate difficulty
        $exerciseCount = is_array($workoutPlan->exercises) ? count($workoutPlan->exercises) : 0;
        $difficulty = 'Intermediate';
        $difficultyColor = 'bg-orange-100 text-orange-800';
        if ($exerciseCount <= 5) {
            $difficulty = 'Beginner';
            $difficultyColor = 'bg-green-100 text-green-800';
        } elseif ($exerciseCount >= 10) {
            $difficulty = 'Advanced';
            $difficultyColor = 'bg-red-100 text-red-800';
        }
        
        // Calculate duration
        $durationMinutes = $exerciseCount * 5;
        
        // Get attendance records for this plan period
        $attendanceRecords = [];
        $attendedDates = [];
        if ($workoutPlan->start_date && $workoutPlan->end_date) {
            $attendanceLogs = \App\Models\ActivityLog::where('user_id', $user->id)
                ->whereBetween('date', [$workoutPlan->start_date, $workoutPlan->end_date])
                ->whereNotNull('check_in_time')
                ->get();
            
            $attendedDates = $attendanceLogs->pluck('date')->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })->toArray();
        }

        // Today's recording progress
        $today = now()->toDateString();
        $todayVideos = $this->workoutVideoRepository->getVideosUploadedOnDate($workoutPlan, $user, $today);
        $todayRecordedExercises = $todayVideos->pluck('exercise_name')->unique()->toArray();
        $recordedTodayCount = count($todayRecordedExercises);
        $todayRecordingPercent = $exerciseCount > 0 ? round(($recordedTodayCount / $exerciseCount) * 100, 1) : 0;
        $attendanceMarkedToday = \App\Models\ActivityLog::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_time')
            ->exists();
        
        return view('frontend.member.workout-plans.show', compact(
            'workoutPlan', 
            'difficulty', 
            'difficultyColor', 
            'durationMinutes', 
            'exerciseCount',
            'attendedDates',
            'todayRecordedExercises',
            'recordedTodayCount',
            'todayRecordingPercent',
            'attendanceMarkedToday'
        ));
    }

    /**
     * Upload workout video for trainer approval (direct upload).
     */
    public function uploadWorkoutVideo(
        Request $request,
        \App\Models\WorkoutPlan $workoutPlan,
        \App\Services\WorkoutVideoService $videoService
    ): \Illuminate\Http\JsonResponse {
        $user = auth()->user();
        
        // Ensure the plan belongs to the authenticated member
        if ($workoutPlan->member_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this workout plan.',
            ], 403);
        }
        
        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,webm,mov', 'max:102400'], // Max 100MB
            'exercise_name' => ['required', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);
        
        // Upload video using service
        $workoutVideo = $videoService->uploadVideo(
            $workoutPlan,
            $user,
            $request->input('exercise_name'),
            $request->file('video'),
            $request->input('duration_seconds', 60)
        );
        
        // Check if all videos are uploaded and mark attendance
        $this->checkAndMarkAttendance($workoutPlan, $user);
        
        return response()->json([
            'success' => true,
            'message' => 'Video uploaded successfully. Waiting for trainer approval.',
            'video' => $workoutVideo,
        ]);
    }

    /**
     * Handle chunked video upload.
     */
    public function uploadWorkoutVideoChunk(
        Request $request,
        \App\Models\WorkoutPlan $workoutPlan,
        \App\Services\WorkoutVideoService $videoService
    ): \Illuminate\Http\JsonResponse {
        $user = auth()->user();
        
        // Ensure the plan belongs to the authenticated member
        if ($workoutPlan->member_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this workout plan.',
            ], 403);
        }
        
        $request->validate([
            'video_chunk' => ['required', 'file'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'upload_id' => ['required', 'string'],
            'exercise_name' => ['required', 'string', 'max:255'],
            'file_name' => ['required', 'string'],
            'file_size' => ['required', 'integer'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);
        
        $chunk = $request->file('video_chunk');
        $chunkIndex = $request->input('chunk_index');
        $totalChunks = $request->input('total_chunks');
        $uploadId = $request->input('upload_id');
        $exerciseName = $request->input('exercise_name');
        $fileName = $request->input('file_name');
        $fileSize = $request->input('file_size');
        $durationSeconds = $request->input('duration_seconds', 60);
        
        // Store chunk in temporary directory
        $tempDir = storage_path('app/temp/video-uploads/' . $uploadId);
        if (!\Illuminate\Support\Facades\File::exists($tempDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($tempDir, 0755, true);
        }
        
        $chunkPath = $tempDir . '/chunk_' . $chunkIndex;
        $chunk->move($tempDir, 'chunk_' . $chunkIndex);
        
        // If this is the last chunk, combine all chunks and save
        if ($chunkIndex === $totalChunks - 1) {
            $finalPath = storage_path('app/temp/video-uploads/' . $uploadId . '/final.webm');
            $finalFile = fopen($finalPath, 'wb');
            
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = $tempDir . '/chunk_' . $i;
                if (file_exists($chunkFile)) {
                    $chunkContent = file_get_contents($chunkFile);
                    fwrite($finalFile, $chunkContent);
                }
            }
            
            fclose($finalFile);
            
            // Create UploadedFile from final file
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $finalPath,
                $fileName,
                mime_content_type($finalPath) ?: 'video/webm',
                null,
                true
            );
            
            // Upload using service (will convert to web format)
            $workoutVideo = $videoService->uploadVideo(
                $workoutPlan,
                $user,
                $exerciseName,
                $uploadedFile,
                $durationSeconds
            );
            
            // Clean up temporary files
            \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
            
            // Check if all videos are uploaded and mark attendance
            $this->checkAndMarkAttendance($workoutPlan, $user);
            
            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully. Waiting for trainer approval.',
                'video' => $workoutVideo,
            ]);
        }
        
        // Return success for intermediate chunks
        return response()->json([
            'success' => true,
            'message' => 'Chunk uploaded successfully.',
            'chunk_index' => $chunkIndex,
        ]);
    }

    /**
     * Mark attendance if all videos are uploaded for today.
     */
    public function markAttendance(
        Request $request,
        \App\Models\WorkoutPlan $workoutPlan
    ): \Illuminate\Http\JsonResponse {
        $user = auth()->user();
        
        // Ensure the plan belongs to the authenticated member
        if ($workoutPlan->member_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this workout plan.',
            ], 403);
        }
        
        $attendanceMarked = $this->checkAndMarkAttendance($workoutPlan, $user);
        
        return response()->json([
            'success' => true,
            'attendance_marked' => $attendanceMarked,
            'message' => $attendanceMarked 
                ? 'Attendance marked successfully for today!' 
                : 'Not all videos uploaded yet.',
        ]);
    }

    /**
     * Check if all videos are uploaded and mark attendance.
     */
    protected function checkAndMarkAttendance(\App\Models\WorkoutPlan $workoutPlan, \App\Models\User $user): bool
    {
        // Use repository to check if all exercises have videos uploaded for today
        $allUploaded = $this->workoutVideoRepository->checkAllExercisesUploadedToday($workoutPlan, $user);
        
        if (!$allUploaded) {
            return false;
        }
        
        // If all videos are uploaded, mark attendance
        $today = now()->toDateString();
        $exercises = is_array($workoutPlan->exercises) ? $workoutPlan->exercises : [];
        
        // Check if attendance already marked for today
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
                'duration_minutes' => count($exercises) * 5, // Estimate 5 min per exercise
            ]);
            
            return true;
        }
        
        return true; // Already marked
    }

    /**
     * Show member diet plans.
     */
    public function dietPlans(Request $request): View
    {
        $user = auth()->user();

        \App\Models\DietPlan::autoCompleteExpired();

        $query = $user->dietPlans()
            ->with('trainer')
            ->latest('start_date');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $dietPlans = $query->paginate(12);

        $statusCounts = [
            'all' => $user->dietPlans()->count(),
            'active' => $user->dietPlans()->where('status', 'active')->count(),
            'completed' => $user->dietPlans()->where('status', 'completed')->count(),
            'paused' => $user->dietPlans()->where('status', 'paused')->count(),
            'cancelled' => $user->dietPlans()->where('status', 'cancelled')->count(),
        ];

        return view('frontend.member.diet-plans', compact('dietPlans', 'statusCounts'));
    }
}
