<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Convert admin demo videos in the background using FFmpeg.
 *
 * Pattern:
 * - Upload controller stores raw file under workout-plans/demo-videos/raw
 * - This job converts raw → MP4 into a temp file
 * - If conversion looks good, we atomically move into final location
 * - If conversion fails or is tiny, we keep the original file instead
 */
class ConvertDemoVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow long-running conversions.
     */
    public $timeout = 0; // No hard timeout, rely on supervisor / process manager

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

        $cmd = sprintf(
            '%s -y -loglevel error -i %s -c:v libx264 -preset veryfast -crf 23 -c:a aac -b:a 128k %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg($input),
            escapeshellarg($tempOutput)
        );

        $output = [];
        $status = 0;
        exec($cmd, $output, $status);

        // If FFmpeg failed or temp file missing → keep original
        if ($status !== 0 || !file_exists($tempOutput)) {
            Log::warning('ConvertDemoVideoJob: FFmpeg failed, keeping original', [
                'source_path' => $this->sourcePath,
                'status' => $status,
                'output' => implode("\n", $output),
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
            $stream = fopen($tempOutput, 'rb');
            $disk->put($finalRel, $stream);
            fclose($stream);
        } finally {
            @unlink($tempOutput);
            if ($disk->exists($this->sourcePath)) {
                $disk->delete($this->sourcePath);
            }
        }

        Log::info('ConvertDemoVideoJob: conversion completed', [
            'source_path' => $this->sourcePath,
            'final_path' => $finalRel,
            'input_bytes' => $inputSize,
            'output_bytes' => $outputSize,
        ]);
    }
}

