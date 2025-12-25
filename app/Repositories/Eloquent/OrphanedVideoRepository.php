<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkoutVideo;
use App\Models\WorkoutPlan;
use App\Models\CmsContent;
use App\Repositories\Interfaces\OrphanedVideoRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class OrphanedVideoRepository implements OrphanedVideoRepositoryInterface
{
    /**
     * Scan for orphaned videos in storage.
     * 
     * @return array Array of orphaned video information
     */
    public function scanOrphanedVideos(): array
    {
        $orphanedVideos = [];
        
        // Get all video directories to scan
        $videoDirectories = [
            'workout-videos',
            'workout-plans/demo-videos',
            'cms/videos',
        ];

        // Get all referenced video paths from database
        $referencedPaths = $this->getReferencedVideoPaths();

        foreach ($videoDirectories as $directory) {
            if (!Storage::disk('public')->exists($directory)) {
                continue;
            }

            $files = Storage::disk('public')->allFiles($directory);
            
            foreach ($files as $file) {
                // Check if file is a video
                if (!$this->isVideoFile($file)) {
                    continue;
                }

                // Check if video is referenced in database
                if (!$this->isVideoReferenced($file, $referencedPaths)) {
                    $fullPath = Storage::disk('public')->path($file);
                    $size = file_exists($fullPath) ? filesize($fullPath) : 0;
                    
                    $orphanedVideos[] = [
                        'path' => $file,
                        'size' => $size,
                        'size_formatted' => $this->formatBytes($size),
                        'modified_at' => file_exists($fullPath) ? filemtime($fullPath) : null,
                        'directory' => $directory,
                    ];
                }
            }
        }

        // Sort by size (largest first)
        usort($orphanedVideos, function ($a, $b) {
            return $b['size'] <=> $a['size'];
        });

        return $orphanedVideos;
    }

    /**
     * Get all video paths referenced in the database.
     * 
     * @return array Array of referenced video paths
     */
    public function getReferencedVideoPaths(): array
    {
        $paths = [];

        // Get workout videos
        $workoutVideos = WorkoutVideo::whereNotNull('video_path')
            ->pluck('video_path')
            ->toArray();
        $paths = array_merge($paths, $workoutVideos);

        // Get workout plan demo videos
        $demoVideos = WorkoutPlan::whereNotNull('demo_video_path')
            ->pluck('demo_video_path')
            ->toArray();
        $paths = array_merge($paths, $demoVideos);

        // Get CMS content videos
        $cmsVideos = CmsContent::whereNotNull('video_path')
            ->pluck('video_path')
            ->toArray();
        $paths = array_merge($paths, $cmsVideos);

        // Get CMS background videos
        $cmsBackgroundVideos = CmsContent::whereNotNull('background_video')
            ->pluck('background_video')
            ->toArray();
        $paths = array_merge($paths, $cmsBackgroundVideos);

        // Remove null/empty values and normalize paths
        $paths = array_filter($paths);
        $paths = array_map(function ($path) {
            return ltrim($path, '/');
        }, $paths);

        return array_unique($paths);
    }

    /**
     * Check if a video file is referenced in the database.
     * 
     * @param string $videoPath The video path to check
     * @param array|null $referencedPaths Optional pre-fetched referenced paths
     * @return bool
     */
    public function isVideoReferenced(string $videoPath, ?array $referencedPaths = null): bool
    {
        if ($referencedPaths === null) {
            $referencedPaths = $this->getReferencedVideoPaths();
        }

        // Normalize the path for comparison
        $normalizedPath = ltrim($videoPath, '/');

        // Direct match
        if (in_array($normalizedPath, $referencedPaths)) {
            return true;
        }

        // Check if any referenced path contains this path (for subdirectories)
        foreach ($referencedPaths as $referencedPath) {
            if (strpos($normalizedPath, $referencedPath) === 0 || 
                strpos($referencedPath, $normalizedPath) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a file is a video file based on extension.
     * 
     * @param string $filePath
     * @return bool
     */
    public function isVideoFile(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $videoExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'ogv', 'm4v', '3gp'];
        
        return in_array($extension, $videoExtensions);
    }

    /**
     * Calculate total size of orphaned videos.
     * 
     * @param array $orphanedVideos
     * @return int Total size in bytes
     */
    public function calculateTotalSize(array $orphanedVideos): int
    {
        return array_sum(array_column($orphanedVideos, 'size'));
    }

    /**
     * Format bytes to human-readable format.
     * 
     * @param int $bytes
     * @return string
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete a video file from storage.
     * 
     * @param string $videoPath
     * @return bool
     */
    public function deleteVideo(string $videoPath): bool
    {
        if (!Storage::disk('public')->exists($videoPath)) {
            return false;
        }

        return Storage::disk('public')->delete($videoPath);
    }

    /**
     * Delete multiple video files from storage.
     * 
     * @param array $videoPaths
     * @return array ['deleted' => int, 'errors' => array]
     */
    public function deleteVideos(array $videoPaths): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($videoPaths as $videoPath) {
            // Verify the video is actually orphaned before deleting
            if ($this->isVideoReferenced($videoPath)) {
                $errors[] = "Video '{$videoPath}' is referenced in database.";
                continue;
            }

            // Delete the video file
            if ($this->deleteVideo($videoPath)) {
                $deletedCount++;
            } else {
                $errors[] = "Failed to delete '{$videoPath}'.";
            }
        }

        return [
            'deleted' => $deletedCount,
            'errors' => $errors,
        ];
    }
}

