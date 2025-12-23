<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\VideoConversionService;

class ConvertDemoVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes for large videos

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $sourcePath,  // Path to the original uploaded file (e.g., 'workout-plans/demo-videos/raw/...')
        public string $outputPath   // Final path where converted MP4 should be saved (e.g., 'workout-plans/demo-videos/...mp4')
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(VideoConversionService $conversionService): void
    {
        try {
            // Check if source file exists
            if (!Storage::disk('public')->exists($this->sourcePath)) {
                Log::error('ConvertDemoVideoJob: Source file not found', [
                    'source_path' => $this->sourcePath,
                ]);
                return;
            }

            // Get full path to source file
            $sourceFullPath = Storage::disk('public')->path($this->sourcePath);

            // Convert video using VideoConversionService
            $convertedPath = $conversionService->convertToWebFormat(
                $sourceFullPath,
                $this->outputPath,
                config('video.conversion', [])
            );

            // If conversion succeeded, delete the raw source file
            if ($convertedPath && Storage::disk('public')->exists($convertedPath)) {
                // Verify converted file is valid (not tiny/corrupted)
                $convertedSize = Storage::disk('public')->size($convertedPath);
                
                if ($convertedSize > 1024 * 1024) { // At least 1MB
                    // Delete the raw source file
                    if (Storage::disk('public')->exists($this->sourcePath)) {
                        Storage::disk('public')->delete($this->sourcePath);
                    }
                    
                    Log::info('ConvertDemoVideoJob: Video converted successfully', [
                        'source_path' => $this->sourcePath,
                        'output_path' => $convertedPath,
                        'size_bytes' => $convertedSize,
                    ]);
                } else {
                    Log::warning('ConvertDemoVideoJob: Converted file is too small, keeping original', [
                        'source_path' => $this->sourcePath,
                        'output_path' => $convertedPath,
                        'size_bytes' => $convertedSize,
                    ]);
                    
                    // If conversion failed (tiny file), rename source to output path
                    if ($convertedPath !== $this->sourcePath) {
                        Storage::disk('public')->move($this->sourcePath, $this->outputPath);
                    }
                }
            } else {
                // Conversion failed, move source file to output path as fallback
                Log::warning('ConvertDemoVideoJob: Conversion failed, using original file', [
                    'source_path' => $this->sourcePath,
                    'output_path' => $this->outputPath,
                ]);
                
                if (Storage::disk('public')->exists($this->sourcePath)) {
                    Storage::disk('public')->move($this->sourcePath, $this->outputPath);
                }
            }
        } catch (\Exception $e) {
            Log::error('ConvertDemoVideoJob: Exception during conversion', [
                'source_path' => $this->sourcePath,
                'output_path' => $this->outputPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Fallback: move source to output path
            try {
                if (Storage::disk('public')->exists($this->sourcePath)) {
                    Storage::disk('public')->move($this->sourcePath, $this->outputPath);
                }
            } catch (\Exception $moveException) {
                Log::error('ConvertDemoVideoJob: Failed to move source file as fallback', [
                    'error' => $moveException->getMessage(),
                ]);
            }
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }
}
