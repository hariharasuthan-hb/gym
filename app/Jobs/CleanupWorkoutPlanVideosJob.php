<?php

namespace App\Jobs;

use App\Models\WorkoutPlan;
use App\Models\WorkoutVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupWorkoutPlanVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting cleanup of workout plan videos for ended plans');

        // Find workout plans that have ended (end_date is in the past)
        $endedPlans = WorkoutPlan::where('end_date', '<', now())
            ->where('status', '!=', 'completed') // Don't process already completed plans
            ->get();

        $totalVideosDeleted = 0;
        $totalPlansProcessed = 0;

        foreach ($endedPlans as $plan) {
            Log::info("Processing ended workout plan ID: {$plan->id}");

            // Get all videos for this plan
            $videos = $plan->workoutVideos;

            foreach ($videos as $video) {
                // Delete video file from storage
                if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
                    Storage::disk('public')->delete($video->video_path);
                    Log::info("Deleted video file: {$video->video_path}");
                }

                // Delete video record
                $video->delete();
                $totalVideosDeleted++;
            }

            // Mark the plan as completed
            $plan->update(['status' => 'completed']);
            $totalPlansProcessed++;

            Log::info("Completed processing workout plan ID: {$plan->id}, deleted {$videos->count()} videos");
        }

        Log::info("Cleanup completed. Processed {$totalPlansProcessed} plans, deleted {$totalVideosDeleted} videos");
    }
}
