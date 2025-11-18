<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\WorkoutPlanDataTable;
use App\Http\Requests\Admin\StoreWorkoutPlanRequest;
use App\Http\Requests\Admin\UpdateWorkoutPlanRequest;
use App\Models\WorkoutPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            $members = User::role('member')->get();
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
        
        // Handle demo video upload
        if ($request->hasFile('demo_video')) {
            $file = $request->file('demo_video');
            $originalName = $file->getClientOriginalName();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = \Illuminate\Support\Str::slug($fileName) . '-' . time();
            $outputPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.mp4';
            
            // Convert video to web-compatible format (H.264 MP4)
            $conversionService = app(\App\Services\VideoConversionService::class);
            $convertedPath = $conversionService->convertToWebFormat(
                $file,
                $outputPath,
                config('video.conversion', [])
            );
            
            // Use converted path or fallback to original
            if ($convertedPath) {
                $validated['demo_video_path'] = $convertedPath;
            } else {
                $extension = $file->getClientOriginalExtension();
                $validated['demo_video_path'] = $file->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');
            }
        }
        
        WorkoutPlan::create($validated);

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
            $members = User::whereIn('id', $memberIds)
                ->orWhere('id', $workoutPlan->member_id) // Include current member
                ->role('member')
                ->get();
            $trainers = collect(); // Trainers don't need trainer list
        } else {
            $members = User::role('member')->get();
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
            $outputPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.mp4';
            
            // Convert video to web-compatible format (H.264 MP4)
            $conversionService = app(\App\Services\VideoConversionService::class);
            $convertedPath = $conversionService->convertToWebFormat(
                $file,
                $outputPath,
                config('video.conversion', [])
            );
            
            // Use converted path or fallback to original
            if ($convertedPath) {
                $validated['demo_video_path'] = $convertedPath;
            } else {
                $extension = $file->getClientOriginalExtension();
                $validated['demo_video_path'] = $file->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');
            }
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
            
            // Log request data for debugging
            \Illuminate\Support\Facades\Log::info('Demo video upload request', [
                'has_file' => $request->hasFile('demo_video'),
                'all_files' => array_keys($request->allFiles()),
                'content_type' => $request->header('Content-Type'),
            ]);
            
            // Check if file exists
            if (!$request->hasFile('demo_video')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No video file was uploaded. Please select a video file.',
                ], 422);
            }
            
            $file = $request->file('demo_video');
            
            // Log file information for debugging
            \Illuminate\Support\Facades\Log::info('Demo video file details', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'client_mime_type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
            ]);
            
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
                    'max:102400', // Max 100MB
                ],
            ], [
                'demo_video.max' => 'Demo video file size must not exceed 100MB.',
            ]);

            // Convert video to web-compatible format (H.264 MP4)
            $originalName = $file->getClientOriginalName();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueFileName = \Illuminate\Support\Str::slug($fileName) . '-' . time();
            $outputPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.mp4';
            
            $conversionService = app(\App\Services\VideoConversionService::class);
            $convertedPath = $conversionService->convertToWebFormat(
                $file,
                $outputPath,
                config('video.conversion', [])
            );
            
            // Use converted path or fallback to original
            if ($convertedPath) {
                $videoPath = $convertedPath;
            } else {
                $extension = $file->getClientOriginalExtension();
                $videoPath = $file->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');
            }

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded and converted successfully.',
                'video_path' => $videoPath,
                'video_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($videoPath),
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
                'video_chunk' => ['required', 'file'],
                'chunk_index' => ['required', 'integer', 'min:0'],
                'total_chunks' => ['required', 'integer', 'min:1'],
                'upload_id' => ['required', 'string'],
                'file_name' => ['required', 'string'],
                'file_size' => ['required', 'integer'],
            ]);

            $chunk = $request->file('video_chunk');
            $chunkIndex = $request->input('chunk_index');
            $totalChunks = $request->input('total_chunks');
            $uploadId = $request->input('upload_id');
            $fileName = $request->input('file_name');
            $fileSize = $request->input('file_size');

            // Store chunk in temporary directory
            $tempDir = storage_path('app/temp/demo-video-uploads/' . $uploadId);
            if (!\Illuminate\Support\Facades\File::exists($tempDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($tempDir, 0755, true);
            }

            $chunkPath = $tempDir . '/chunk_' . $chunkIndex;
            $chunk->move($tempDir, 'chunk_' . $chunkIndex);

            // If this is the last chunk, combine all chunks and save
            if ($chunkIndex === $totalChunks - 1) {
                $finalPath = storage_path('app/temp/demo-video-uploads/' . $uploadId . '/final.' . pathinfo($fileName, PATHINFO_EXTENSION));
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
                    mime_content_type($finalPath),
                    null,
                    true
                );

                // Convert video to web-compatible format (H.264 MP4)
                $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                $uniqueFileName = \Illuminate\Support\Str::slug($baseName) . '-' . time();
                $outputPath = 'workout-plans/demo-videos/' . $uniqueFileName . '.mp4';
                
                $conversionService = app(\App\Services\VideoConversionService::class);
                $convertedPath = $conversionService->convertToWebFormat(
                    $uploadedFile,
                    $outputPath,
                    config('video.conversion', [])
                );
                
                // Use converted path or fallback to original
                if ($convertedPath) {
                    $videoPath = $convertedPath;
                } else {
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $videoPath = $uploadedFile->storeAs('workout-plans/demo-videos', $uniqueFileName . '.' . $extension, 'public');
                }

                // Clean up temporary files
                \Illuminate\Support\Facades\File::deleteDirectory($tempDir);

                return response()->json([
                    'success' => true,
                    'message' => 'Video uploaded successfully.',
                    'video_path' => $videoPath,
                    'video_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($videoPath),
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
        
        $workoutPlan->delete();

        return redirect()->route('admin.workout-plans.index')
            ->with('success', 'Workout plan deleted successfully.');
    }
}

