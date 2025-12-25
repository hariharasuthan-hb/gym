<?php

namespace App\Repositories\Interfaces;

interface OrphanedVideoRepositoryInterface
{
    /**
     * Scan for orphaned videos in storage.
     * 
     * @return array Array of orphaned video information
     */
    public function scanOrphanedVideos(): array;

    /**
     * Get all video paths referenced in the database.
     * 
     * @return array Array of referenced video paths
     */
    public function getReferencedVideoPaths(): array;

    /**
     * Check if a video file is referenced in the database.
     * 
     * @param string $videoPath The video path to check
     * @param array|null $referencedPaths Optional pre-fetched referenced paths
     * @return bool
     */
    public function isVideoReferenced(string $videoPath, ?array $referencedPaths = null): bool;

    /**
     * Check if a file is a video file based on extension.
     * 
     * @param string $filePath
     * @return bool
     */
    public function isVideoFile(string $filePath): bool;

    /**
     * Calculate total size of orphaned videos.
     * 
     * @param array $orphanedVideos
     * @return int Total size in bytes
     */
    public function calculateTotalSize(array $orphanedVideos): int;

    /**
     * Format bytes to human-readable format.
     * 
     * @param int $bytes
     * @return string
     */
    public function formatBytes(int $bytes): string;

    /**
     * Delete a video file from storage.
     * 
     * @param string $videoPath
     * @return bool
     */
    public function deleteVideo(string $videoPath): bool;

    /**
     * Delete multiple video files from storage.
     * 
     * @param array $videoPaths
     * @return array ['deleted' => int, 'errors' => array]
     */
    public function deleteVideos(array $videoPaths): array;
}

