<?php

namespace App\Services;

use App\Models\WorkoutPlan;
use App\Models\WorkoutVideo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkoutVideoService
{
    protected VideoConversionService $conversionService;

    public function __construct(VideoConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Upload and store a workout video.
     *
     * @param WorkoutPlan $workoutPlan
     * @param User $user
     * @param string $exerciseName
     * @param UploadedFile $videoFile
     * @param int|null $durationSeconds
     * @return WorkoutVideo
     */
    public function uploadVideo(
        WorkoutPlan $workoutPlan,
        User $user,
        string $exerciseName,
        UploadedFile $videoFile,
        ?int $durationSeconds = null
    ): WorkoutVideo {
        // Generate unique filename
        $originalName = $videoFile->getClientOriginalName();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueFileName = \Illuminate\Support\Str::slug($fileName) . '-' . time();
        
        // Convert video to web-compatible format (H.264 MP4)
        $outputPath = 'workout-videos/' . $uniqueFileName . '.mp4';
        $convertedPath = $this->conversionService->convertToWebFormat(
            $videoFile,
            $outputPath,
            config('video.conversion', [])
        );
        
        // Use converted path or fallback to original storage
        $videoPath = $convertedPath ?? $this->storeOriginalFile($videoFile, $uniqueFileName);

        // Final safeguard: ensure we always have a stored file path
        if (!$videoPath) {
            $videoPath = $videoFile->store('workout-videos', 'public');
        }

        if (!$videoPath) {
            throw new \RuntimeException('Unable to store workout video file.');
        }
        
        // Get actual duration if not provided
        if ($durationSeconds === null) {
            $durationSeconds = $this->conversionService->getVideoDuration($videoFile) ?? 60;
        }
        
        // Create workout video record
        return WorkoutVideo::create([
            'workout_plan_id' => $workoutPlan->id,
            'user_id' => $user->id,
            'exercise_name' => $exerciseName,
            'video_path' => $videoPath,
            'duration_seconds' => $durationSeconds,
            'status' => 'pending',
        ]);
    }

    /**
     * Store original file as fallback.
     */
    protected function storeOriginalFile(UploadedFile $videoFile, string $uniqueFileName): string
    {
        $extension = $videoFile->getClientOriginalExtension();
        $fileName = $uniqueFileName . '.' . $extension;
        return $videoFile->storeAs('workout-videos', $fileName, 'public');
    }

    /**
     * Delete a workout video and its file.
     *
     * @param WorkoutVideo $workoutVideo
     * @return bool
     */
    public function deleteVideo(WorkoutVideo $workoutVideo): bool
    {
        // Delete the video file
        if ($workoutVideo->video_path && Storage::disk('public')->exists($workoutVideo->video_path)) {
            Storage::disk('public')->delete($workoutVideo->video_path);
        }
        
        // Delete the record
        return $workoutVideo->delete();
    }

    /**
     * Get video URL.
     *
     * @param WorkoutVideo $workoutVideo
     * @return string|null
     */
    public function getVideoUrl(WorkoutVideo $workoutVideo): ?string
    {
        if (!$workoutVideo->video_path) {
            return null;
        }
        
        return Storage::disk('public')->url($workoutVideo->video_path);
    }
}

