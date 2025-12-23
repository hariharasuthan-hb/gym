<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use App\Services\VideoConversionService;

class TestVideoUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-video-upload {file?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test video upload functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking video upload status...");

        // Debug the video upload issue
        $this->info("=== VIDEO UPLOAD DEBUGGING ===");

        $videoFile = '/Users/bhuvanahari/Downloads/8K_Thetestdata.mp4';
        if (!file_exists($videoFile)) {
            $this->error("❌ Test video file not found: {$videoFile}");
            return 1;
        }

        $this->info("✅ Test video file exists: " . filesize($videoFile) . " bytes");

        // Test FFmpeg availability
        $this->info("\n--- Testing FFmpeg ---");
        exec('ffmpeg -version 2>&1', $output, $returnVar);
        if ($returnVar === 0) {
            $this->info("✅ FFmpeg is available");
        } else {
            $this->error("❌ FFmpeg is NOT available");
        }

        // Test direct conversion and upload
        $this->info("\n--- Testing Video Conversion and Upload ---");
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $videoFile,
            basename($videoFile),
            mime_content_type($videoFile),
            null,
            true
        );

        $outputPath = 'workout-plans/demo-videos/' . \Illuminate\Support\Str::slug(basename($videoFile, '.mp4')) . '-' . time() . '.mp4';

        try {
            $conversionService = app(\App\Services\VideoConversionService::class);
            $convertedPath = $conversionService->convertToWebFormat(
                $uploadedFile,
                $outputPath,
                config('video.conversion', [])
            );

            if ($convertedPath) {
                $fullPath = storage_path('app/public/' . $convertedPath);
                if (file_exists($fullPath)) {
                    $fileSize = filesize($fullPath);
                    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
                    $this->info("✅ Video uploaded and converted successfully!");
                    $this->info("   File path: {$convertedPath}");
                    $this->info("   File size: {$fileSizeMB} MB ({$fileSize} bytes)");
                    $this->info("   Full path: {$fullPath}");

                    if ($fileSize < 1000000) { // Less than 1MB
                        $this->warn("⚠️  File is very small - conversion may have failed");
                    } else {
                        $this->info("🎉 Video is ready to use!");

                        // Create a workout plan with this video
                        $this->info("\n--- Creating Workout Plan ---");

                        // Get an admin user and a member
                        $admin = \App\Models\User::role('admin')->first();
                        $member = \App\Models\User::role('member')->first();

                        if (!$admin || !$member) {
                            $this->error("❌ Cannot create workout plan - missing admin or member users");
                            return 1;
                        }

                        $workoutPlan = \App\Models\WorkoutPlan::create([
                            'plan_name' => 'Test Workout Plan with Video',
                            'description' => 'Automatically created workout plan with uploaded video',
                            'member_id' => $member->id,
                            'trainer_id' => $admin->id,
                            'duration_weeks' => 4,
                            'start_date' => now()->format('Y-m-d'),
                            'end_date' => now()->addWeeks(4)->format('Y-m-d'),
                            'status' => 'active',
                            'exercises' => ['Push-ups', 'Squats', 'Bench Press'],
                            'demo_video_path' => $convertedPath,
                            'notes' => 'Video uploaded via command line tool'
                        ]);

                        $this->info("✅ Workout plan created successfully!");
                        $this->info("   Plan ID: {$workoutPlan->id}");
                        $this->info("   Plan Name: {$workoutPlan->plan_name}");
                        $this->info("   Member: {$member->name}");
                        $this->info("   Video: {$convertedPath}");

                        $this->info("🎉 Video is now associated with workout plan #{$workoutPlan->id}!");
                    }
                } else {
                    $this->error("❌ Converted file does not exist");
                }
            } else {
                $this->error("❌ Conversion returned null");
            }
        } catch (\Exception $e) {
            $this->error("❌ Conversion failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }

        // Check recent files
        $this->info("\n--- Recent Video Files ---");
        $videoDir = storage_path('app/public/workout-plans/demo-videos');
        if (is_dir($videoDir)) {
            $files = scandir($videoDir);
            $recentFiles = [];

            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                    $filePath = $videoDir . '/' . $file;
                    $mtime = filemtime($filePath);
                    $recentFiles[] = [
                        'name' => $file,
                        'size' => filesize($filePath),
                        'mtime' => $mtime
                    ];
                }
            }

            // Sort by modification time (newest first)
            usort($recentFiles, function($a, $b) {
                return $b['mtime'] - $a['mtime'];
            });

            $this->info("Recent video files (newest first):");
            foreach (array_slice($recentFiles, 0, 5) as $file) {
                $sizeKB = round($file['size'] / 1024, 1);
                $timeAgo = time() - $file['mtime'];
                $timeStr = $timeAgo < 60 ? "{$timeAgo}s ago" :
                          ($timeAgo < 3600 ? round($timeAgo/60) . "m ago" :
                          round($timeAgo/3600) . "h ago");

                $this->line("- {$file['name']} ({$sizeKB} KB) - {$timeStr}");

                if ($file['size'] < 2000) {
                    $this->warn("  ⚠️  Very small file - possible upload failure");
                }
            }
        }

        // Check all video files in directory
        $videoDir = storage_path('app/public/workout-plans/demo-videos');
        if (is_dir($videoDir)) {
            $this->info("All video files in demo-videos directory:");
            $files = scandir($videoDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                    $filePath = $videoDir . '/' . $file;
                    $size = filesize($filePath);
                    $this->line("- {$file} ({$size} bytes)");
                }
            }
        }

        $this->info("✅ Video status check completed!");
        return 0;
    }
}
