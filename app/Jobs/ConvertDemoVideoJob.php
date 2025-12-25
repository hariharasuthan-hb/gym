<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Convert admin demo videos in the background using FFmpeg.
 * 
 * Optimized for large video uploads (25MB+) following production-ready patterns.
 * Supports chunked uploads, long-running conversions, and robust error handling.
 *
 * Architecture:
 * 1. Frontend uploads video in chunks (5-10MB per chunk) via JavaScript
 * 2. Controller assembles chunks and stores raw file in workout-plans/demo-videos/raw
 * 3. This job converts raw → MP4 using FFmpeg with proper timeout handling
 * 4. Atomic move to final location only after successful conversion
 * 5. Falls back to original file if conversion fails or times out
 *
 * Timeout: 30 minutes (1800 seconds) for large video files
 * Memory: Requires 1GB+ for FFmpeg processing large files
 */
class ConvertDemoVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow long-running conversions for large video files.
     * Set to 1800 seconds (30 minutes) for large video conversions (25MB+).
     * This matches production-ready patterns like YouTube for handling large uploads.
     */
    public $timeout = 1800;

    /**
     * Number of attempts.
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param string $sourcePath Raw uploaded file path on public disk (e.g. 'workout-plans/demo-videos/raw/foo-123.webm')
     * @param string|null $outputPath Final MP4 path on public disk (e.g. 'workout-plans/demo-videos/foo-123.mp4')
     */
    public function __construct(
        public string $sourcePath,
        public ?string $outputPath = null,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($this->sourcePath)) {
            Log::warning('ConvertDemoVideoJob: source file missing', [
                'source_path' => $this->sourcePath,
            ]);
            return;
        }

        $input = $disk->path($this->sourcePath);

        clearstatcache();
        $inputSize = @filesize($input) ?: 0;

        // Basic safety: don't try to convert obviously broken uploads
        if ($inputSize < 500 * 1024) { // < 500KB
            Log::warning('ConvertDemoVideoJob: source file too small, keeping original', [
                'source_path' => $this->sourcePath,
                'size_bytes' => $inputSize,
            ]);

            $finalRel = $this->outputPath ?: str_replace('raw/', '', $this->sourcePath);
            if ($this->sourcePath !== $finalRel && $disk->exists($this->sourcePath)) {
                $disk->move($this->sourcePath, $finalRel);
            }
            return;
        }

        // Temp output (never the final path)
        $tempOutput = storage_path('app/tmp/' . uniqid('video_', true) . '.mp4');
        if (!is_dir(dirname($tempOutput))) {
            mkdir(dirname($tempOutput), 0755, true);
        }

        // Build FFmpeg command (no ffprobe)
        $ffmpeg = config('video.ffmpeg_path') ?: 'ffmpeg';

        // Use Symfony Process instead of exec() for better timeout control
        // This ensures the process respects the job timeout and doesn't spawn nested queue workers
        $process = new Process([
            $ffmpeg,
            '-y',
            '-loglevel', 'error',
            '-i', $input,
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            $tempOutput
        ]);

        // Set process timeout to match job timeout (1800 seconds = 30 minutes)
        // Add 60 seconds buffer to ensure FFmpeg has enough time to complete
        $process->setTimeout($this->timeout + 60); // Job timeout + buffer
        $process->setIdleTimeout(null); // No idle timeout for video conversion

        Log::info('ConvertDemoVideoJob: Starting FFmpeg conversion', [
            'source_path' => $this->sourcePath,
            'input_size' => $inputSize,
            'input_size_mb' => round($inputSize / 1024 / 1024, 2),
            'timeout' => $this->timeout + 60,
        ]);

        try {
            $process->run();
            $status = $process->getExitCode();
            $output = $process->getOutput() . $process->getErrorOutput();
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            Log::error('ConvertDemoVideoJob: FFmpeg conversion timed out', [
                'source_path' => $this->sourcePath,
                'input_size_mb' => round($inputSize / 1024 / 1024, 2),
                'timeout' => $process->getTimeout(),
                'error' => $e->getMessage(),
            ]);
            $status = -1;
            $output = 'FFmpeg conversion timed out after ' . $process->getTimeout() . ' seconds';
            
            // Clean up temp file if it exists
            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
            
            // Keep original file on timeout
            $finalRel = $this->outputPath ?: str_replace('raw/', '', $this->sourcePath);
            if ($this->sourcePath !== $finalRel && $disk->exists($this->sourcePath)) {
                $disk->move($this->sourcePath, $finalRel);
            }
            return;
        }

        // If FFmpeg failed or temp file missing → keep original
        if ($status !== 0 || !file_exists($tempOutput)) {
            Log::warning('ConvertDemoVideoJob: FFmpeg failed, keeping original', [
                'source_path' => $this->sourcePath,
                'status' => $status,
                'output' => $output,
            ]);

            $finalRel = $this->outputPath ?: str_replace('raw/', '', $this->sourcePath);
            if ($this->sourcePath !== $finalRel && $disk->exists($this->sourcePath)) {
                $disk->move($this->sourcePath, $finalRel);
            }
            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
            return;
        }

        clearstatcache();
        $outputSize = @filesize($tempOutput) ?: 0;

        // If converted file is suspiciously small (<10% of input), treat as failure
        if ($outputSize < $inputSize * 0.10) {
            Log::warning('ConvertDemoVideoJob: converted file too small, keeping original', [
                'source_bytes' => $inputSize,
                'output_bytes' => $outputSize,
                'source_path' => $this->sourcePath,
            ]);

            $finalRel = $this->outputPath ?: str_replace('raw/', '', $this->sourcePath);
            if ($this->sourcePath !== $finalRel && $disk->exists($this->sourcePath)) {
                $disk->move($this->sourcePath, $finalRel);
            }
            @unlink($tempOutput);
            return;
        }

        // At this point tempOutput looks good – atomically move into final location
        $finalRel = $this->outputPath ?: str_replace('raw/', '', $this->sourcePath);

        try {
            // Store final video atomically
            $stream = fopen($tempOutput, 'rb');
            $disk->put($finalRel, $stream);
            fclose($stream);
        } catch (\Exception $e) {
            Log::error('ConvertDemoVideoJob: Failed to store final video', [
                'source_path' => $this->sourcePath,
                'final_path' => $finalRel,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Always clean up temp file
            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
            // Delete raw source file after successful conversion
            if ($disk->exists($this->sourcePath)) {
                $disk->delete($this->sourcePath);
            }
        }

        Log::info('ConvertDemoVideoJob: conversion completed', [
            'source_path' => $this->sourcePath,
            'final_path' => $finalRel,
            'input_bytes' => $inputSize,
            'input_size_mb' => round($inputSize / 1024 / 1024, 2),
            'output_bytes' => $outputSize,
            'output_size_mb' => round($outputSize / 1024 / 1024, 2),
            'compression_ratio' => $inputSize > 0 ? round(($outputSize / $inputSize) * 100, 2) . '%' : 'N/A',
        ]);
    }
}

