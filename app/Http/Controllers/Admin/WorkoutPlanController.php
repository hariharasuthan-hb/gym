<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\WorkoutPlanDataTable;
use App\Http\Requests\Admin\StoreWorkoutPlanRequest;
use App\Http\Requests\Admin\UpdateWorkoutPlanRequest;
use App\Models\WorkoutPlan;
use App\Models\User;
use App\Events\WorkoutPlanCreated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\EntityIntegrityService;

/**
 * Controller for managing workout plans in the admin panel.
 * 
 * Handles CRUD operations for workout plans including creation, updating,
 * deletion, and viewing. Workout plans are assigned to members by trainers
 * or admins and include exercise routines and demo videos. Accessible by
 * both admin and trainer roles with appropriate permissions.
 */
class WorkoutPlanController extends Controller
{
    public function __construct(
        private readonly EntityIntegrityService $entityIntegrityService
    ) {
    }

    /**
     * Display a listing of workout plans.
     * Accessible by both admin and trainer (filtered by permission).
     */
    public function index(WorkoutPlanDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new WorkoutPlan))->toJson();
        }
        
        return view('admin.workout-plans.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new workout plan.
     */
    public function create(): View
    {
        $user = auth()->user();
        
        // Get members based on role
        if ($user->hasRole('trainer')) {
            // Trainers see their assigned members or members without active plans
            $memberIds = WorkoutPlan::where('trainer_id', $user->id)
                ->pluck('member_id')
                ->unique();
            
            $members = User::role('member')
                ->subscribed()
                ->where(function ($query) use ($memberIds, $user) {
                    $query->whereIn('id', $memberIds)
                        ->orWhereDoesntHave('workoutPlans', function ($q) use ($user) {
                            $q->where('trainer_id', $user->id)->where('status', 'active');
                        });
                })
                ->get();
            
            $trainers = collect(); // Trainers don't need trainer list
        } else {
            // Admins see all members and all trainers
            $members = User::role('member')->subscribed()->get();
            $trainers = User::role('trainer')->get();
        }
        
        return view('admin.workout-plans.create', compact('members', 'trainers'));
    }

    /**
     * Store a newly created workout plan in storage.
     */
    public function store(StoreWorkoutPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Auto-set trainer_id for trainers, use from request for admins
        if (auth()->user()->hasRole('trainer')) {
            $validated['trainer_id'] = auth()->id();
        }
        // For admins, trainer_id should come from the form request
        
        // Handle exercises - convert from array or JSON string to JSON
        if ($request->has('exercises_json') && !empty($request->exercises_json)) {
            $validated['exercises'] = json_decode($request->exercises_json, true);
        } elseif ($request->has('exercises') && is_array($request->exercises)) {
            // Filter out empty values and convert to JSON array
            $exercises = array_filter(array_map('trim', $request->exercises), function($value) {
                return !empty($value);
            });
            $validated['exercises'] = !empty($exercises) ? array_values($exercises) : null;
        } else {
            $validated['exercises'] = null;
        }
        
        // Remove temporary fields
        unset($validated['exercises_json']);
        
        // Handle demo video upload (store final file directly; no queue conversion for admin)
        if ($request->hasFile('demo_video')) {
            $file = $request->file('demo_video');
            $originalName = $file->getClientOriginalName();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = \Illuminate\Support\Str::slug($fileName) . '-' . time();
            
            // Store final file (as uploaded) directly in final directory
            $extension = strtolower($file->getClientOriginalExtension());
            $finalPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.' . $extension;
            $file->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');

            $validated['demo_video_path'] = $finalPath;
        } elseif ($request->filled('demo_video_path')) {
            // Video was pre-uploaded via AJAX (chunked upload)
            $path = (string) $request->input('demo_video_path');
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                // Enforce the same 25MB limit server-side
                $size = \Illuminate\Support\Facades\Storage::disk('public')->size($path);
                if ($size <= 26214400) {
                    $validated['demo_video_path'] = $path;
                }
            }
        }
        
        $workoutPlan = WorkoutPlan::create($validated);
        
        // Fire event to send notification to member
        event(new WorkoutPlanCreated($workoutPlan));

        return redirect()->route('admin.workout-plans.index')
            ->with('success', 'Workout plan created successfully.');
    }

    /**
     * Display the specified workout plan.
     */
    public function show(WorkoutPlan $workoutPlan): View
    {
        // Check if trainer can view this plan
        if (auth()->user()->hasRole('trainer') && $workoutPlan->trainer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        $workoutPlan->load(['trainer', 'member']);
        
        return view('admin.workout-plans.show', compact('workoutPlan'));
    }

    /**
     * Show the form for editing the specified workout plan.
     */
    public function edit(WorkoutPlan $workoutPlan): View
    {
        // Check if trainer can edit this plan
        if (auth()->user()->hasRole('trainer') && $workoutPlan->trainer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        $user = auth()->user();
        
        // Get members based on role
        if ($user->hasRole('trainer')) {
            $memberIds = WorkoutPlan::where('trainer_id', $user->id)
                ->pluck('member_id')
                ->unique();

            $members = User::role('member')
                ->subscribed()
                ->whereIn('id', $memberIds)
                ->get();

            $currentMember = $workoutPlan->member()->first();
            if ($currentMember && !$members->contains('id', $currentMember->id)) {
                $members->push($currentMember);
            }

            $trainers = collect(); // Trainers don't need trainer list
        } else {
            $members = User::role('member')->subscribed()->get();
            $trainers = User::role('trainer')->get();
        }
        
        $workoutPlan->load(['trainer', 'member']);
        
        return view('admin.workout-plans.edit', compact('workoutPlan', 'members', 'trainers'));
    }

    /**
     * Update the specified workout plan in storage.
     */
    public function update(UpdateWorkoutPlanRequest $request, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $validated = $request->validated();
        
        // Handle exercises - convert from array or JSON string to JSON
        if ($request->has('exercises_json') && !empty($request->exercises_json)) {
            $validated['exercises'] = json_decode($request->exercises_json, true);
        } elseif ($request->has('exercises') && is_array($request->exercises)) {
            // Filter out empty values and convert to JSON array
            $exercises = array_filter(array_map('trim', $request->exercises), function($value) {
                return !empty($value);
            });
            $validated['exercises'] = !empty($exercises) ? array_values($exercises) : null;
        } else {
            $validated['exercises'] = null;
        }
        
        // Remove temporary fields
        unset($validated['exercises_json']);
        
        // Handle demo video upload (either from file input or pre-uploaded path)
        if ($request->hasFile('demo_video')) {
            // Delete old demo video if exists
            if ($workoutPlan->demo_video_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($workoutPlan->demo_video_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($workoutPlan->demo_video_path);
            }
            $file = $request->file('demo_video');
            $originalName = $file->getClientOriginalName();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = \Illuminate\Support\Str::slug($fileName) . '-' . time();
            
            // Store final file (as uploaded) directly in final directory (no queue conversion for admin)
            $extension = strtolower($file->getClientOriginalExtension());
            $finalPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.' . $extension;
            $file->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');

            $validated['demo_video_path'] = $finalPath;
        } elseif ($request->has('demo_video_path') && !empty($request->input('demo_video_path'))) {
            // Video was pre-uploaded via AJAX
            // Delete old demo video if exists and different from new one
            if ($workoutPlan->demo_video_path 
                && $workoutPlan->demo_video_path !== $request->input('demo_video_path')
                && \Illuminate\Support\Facades\Storage::disk('public')->exists($workoutPlan->demo_video_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($workoutPlan->demo_video_path);
            }
            $validated['demo_video_path'] = $request->input('demo_video_path');
        }
        
        $workoutPlan->update($validated);

        return redirect()->route('admin.workout-plans.index')
            ->with('success', 'Workout plan updated successfully.');
    }

    /**
     * Upload demo video (direct upload for smaller files).
     */
    public function uploadDemoVideo(Request $request, ?WorkoutPlan $workoutPlan = null): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check permissions
            if (!$user->can('create workout plans') && !$user->can('edit workout plans')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have permission to upload demo videos.',
                ], 403);
            }
            
            
            // Check if file exists
            if (!$request->hasFile('demo_video')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No video file was uploaded. Please select a video file.',
                ], 422);
            }
            
            $file = $request->file('demo_video');
            
            
            // Validate file extension first (more reliable)
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedExtensions = ['mp4', 'webm', 'mov'];
            
            if (!in_array($extension, $allowedExtensions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The demo video must be a file of type: mp4, webm, or mov. Your file has extension: ' . $extension,
                ], 422);
            }
            
            // Validate with flexible MIME type rules
            $validated = $request->validate([
                'demo_video' => [
                    'required',
                    'file',
                    function ($attribute, $value, $fail) use ($extension) {
                        // Get MIME type
                        $mimeType = $value->getMimeType();
                        $clientMimeType = $value->getClientMimeType();
                        
                        // Allowed MIME types
                        $allowedMimeTypes = [
                            'video/mp4',
                            'video/webm',
                            'video/quicktime',
                            'video/x-msvideo',
                            'video/x-ms-wmv',
                            'application/octet-stream', // Sometimes files are sent with this
                        ];
                        
                        // Check if MIME type is allowed OR if extension is correct (fallback)
                        if (!in_array($mimeType, $allowedMimeTypes) && 
                            !in_array($clientMimeType, $allowedMimeTypes) &&
                            !in_array($extension, ['mp4', 'webm', 'mov'])) {
                            $fail('The demo video must be a file of type: mp4, webm, or mov. Detected MIME type: ' . $mimeType);
                        }
                    },
                    'max:25600', // Max 25MB
                ],
            ], [
                'demo_video.max' => 'Demo video file size must not exceed 25MB.',
            ]);

            // Store final file directly (no queue conversion for admin)
            $originalName = $file->getClientOriginalName();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = \Illuminate\Support\Str::slug($fileName) . '-' . time();
            
            $extension = strtolower($file->getClientOriginalExtension());
            $finalPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.' . $extension;
            $file->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');

            // Verify stored size matches uploaded size
            $storedSize = \Illuminate\Support\Facades\Storage::disk('public')->size($finalPath);
            if ($storedSize !== $file->getSize()) {
                // Cleanup and error if something went wrong
                \Illuminate\Support\Facades\Storage::disk('public')->delete($finalPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed: stored file size mismatch.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully.',
                'video_path' => $finalPath,
                'video_url' => file_url($finalPath),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            
            \Illuminate\Support\Facades\Log::error('Demo video upload validation failed', [
                'errors' => $errors,
                'request_data' => $request->all(),
                'has_file' => $request->hasFile('demo_video'),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $firstError ?? 'Validation failed.',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Demo video upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle chunked demo video upload.
     */
    public function uploadDemoVideoChunk(Request $request, ?WorkoutPlan $workoutPlan = null): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check permissions
            if (!$user->can('create workout plans') && !$user->can('edit workout plans')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have permission to upload demo videos.',
                ], 403);
            }
            
            $validated = $request->validate([
                // Each chunk should be small enough to avoid POST limits (we send 5MB chunks from the browser)
                'video_chunk' => ['required', 'file', 'max:10240'], // 10MB per chunk (KB)
                'chunk_index' => ['required', 'integer', 'min:0'],
                'total_chunks' => ['required', 'integer', 'min:1'],
                'upload_id' => ['required', 'string'],
                'file_name' => ['required', 'string'],
                // Total size limit for admin demo videos: 25MB
                'file_size' => ['required', 'integer', 'min:1', 'max:26214400'],
                '_videoFieldName' => ['nullable', 'string'], // Allow additional data
            ]);

            $chunk = $request->file('video_chunk');
            $chunkIndex = (int) $request->input('chunk_index');
            $totalChunks = (int) $request->input('total_chunks');
            $uploadId = $request->input('upload_id');
            $fileName = $request->input('file_name');
            $fileSize = (int) $request->input('file_size');

            if ($chunkIndex >= $totalChunks) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chunk index.',
                ], 422);
            }

            // Store chunk in temporary directory
            $tempDir = storage_path('app/temp/demo-video-uploads/' . $uploadId);
            if (!\Illuminate\Support\Facades\File::exists($tempDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($tempDir, 0755, true);
            }

            // Validate extension from original filename (we store final file as uploaded; no conversion here)
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['mp4', 'webm', 'mov'];
            if (!in_array($extension, $allowedExtensions)) {
                \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Allowed: mp4, webm, mov.',
                ], 422);
            }

            // Move chunk into temp folder
            $chunk->move($tempDir, 'chunk_' . $chunkIndex);

            // If this is the last chunk, combine all chunks and save
            if ($chunkIndex === $totalChunks - 1) {
                // Prevent concurrent assembly (e.g. retries hitting last chunk)
                $lockFile = $tempDir . '/assemble.lock';
                $lockHandle = fopen($lockFile, 'c');
                if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed: unable to acquire assembly lock.',
                    ], 500);
                }

                // Check if all chunks exist before assembly
                $missingChunks = [];
                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkFile = $tempDir . '/chunk_' . $i;
                    if (!file_exists($chunkFile)) {
                        $missingChunks[] = $i;
                    }
                }

                if (!empty($missingChunks)) {
                    \Illuminate\Support\Facades\Log::error('Missing chunks before assembly', [
                        'upload_id' => $uploadId,
                        'missing_chunks' => $missingChunks,
                        'total_chunks' => $totalChunks,
                    ]);

                    // Unlock then clean up and return error
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed: Missing chunks ' . implode(', ', $missingChunks),
                    ], 422);
                }

                // Assemble chunks into a single temp file (streaming, low memory)
                $assembledLocalPath = $tempDir . '/final.' . $extension;
                $out = fopen($assembledLocalPath, 'wb');
                if ($out === false) {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed: unable to create final file.',
                    ], 500);
                }

                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkFile = $tempDir . '/chunk_' . $i;
                    $in = fopen($chunkFile, 'rb');
                    if ($in === false) {
                        fclose($out);
                        flock($lockHandle, LOCK_UN);
                        fclose($lockHandle);
                        \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                        return response()->json([
                            'success' => false,
                            'message' => 'Upload failed: unable to read chunk ' . $i,
                        ], 500);
                    }
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                }
                fclose($out);

                // Verify assembled file size matches the client-reported total
                clearstatcache();
                $assembledSize = @filesize($assembledLocalPath) ?: 0;
                if ($assembledSize !== $fileSize) {
                    \Illuminate\Support\Facades\Log::error('Chunk assembly size mismatch', [
                        'upload_id' => $uploadId,
                        'expected_size' => $fileSize,
                        'assembled_size' => $assembledSize,
                        'total_chunks' => $totalChunks,
                    ]);

                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed: file size mismatch after assembly.',
                    ], 422);
                }

                // Store assembled file directly in final directory (no queue conversion)
                $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                $uniqueFileName = \Illuminate\Support\Str::slug($baseName) . '-' . time();
                $finalRelPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.' . $extension;

                $stream = fopen($assembledLocalPath, 'rb');
                if ($stream === false) {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed: unable to open assembled file.',
                    ], 500);
                }
                \Illuminate\Support\Facades\Storage::disk('public')->put($finalRelPath, $stream);
                fclose($stream);

                // Verify stored file size matches expected
                $storedSize = \Illuminate\Support\Facades\Storage::disk('public')->size($finalRelPath);
                if ($storedSize !== $fileSize) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($finalRelPath);
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed: stored file size mismatch.',
                    ], 500);
                }

                // Unlock then clean up temporary chunk directory
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                \Illuminate\Support\Facades\File::deleteDirectory($tempDir);

                return response()->json([
                    'success' => true,
                    'message' => 'Video uploaded successfully.',
                    'video_path' => $finalRelPath,
                    'video_url' => file_url($finalRelPath),
                    'file_size' => $storedSize,
                ]);
            }

            // Return success for intermediate chunks
            return response()->json([
                'success' => true,
                'message' => 'Chunk uploaded successfully.',
                'chunk_index' => $chunkIndex,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            
            return response()->json([
                'success' => false,
                'message' => $firstError ?? 'Validation failed.',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Demo video chunk upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified workout plan from storage.
     */
    public function destroy(WorkoutPlan $workoutPlan): RedirectResponse
    {
        // Check if trainer can delete this plan
        if (auth()->user()->hasRole('trainer') && $workoutPlan->trainer_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if ($reason = $this->entityIntegrityService->firstWorkoutPlanDeletionBlocker($workoutPlan)) {
            return redirect()->route('admin.workout-plans.index')
                ->with('error', $reason);
        }

        $workoutPlan->delete();

        return redirect()->route('admin.workout-plans.index')
            ->with('success', 'Workout plan deleted successfully.');
    }

    // checkVideoConversion removed: admin demo videos are stored directly (no queue conversion)
}

