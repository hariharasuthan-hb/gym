<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\OrphanedVideoDataTable;
use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\OrphanedVideoRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing orphaned videos in the admin panel.
 *
 * Handles scanning for videos that exist in storage but are not referenced
 * in the database, and provides functionality to delete them.
 */
class OrphanedVideosController extends Controller
{
    public function __construct(
        private readonly OrphanedVideoRepositoryInterface $orphanedVideoRepository
    ) {
    }

    /**
     * Display a listing of orphaned videos.
     */
    public function index(OrphanedVideoDataTable $dataTable)
    {
        $orphanedVideos = $this->orphanedVideoRepository->scanOrphanedVideos();
        
        $dataTable->setData($orphanedVideos);

        return view('admin.orphaned-videos.index', [
            'dataTable' => $dataTable,
            'orphanedVideos' => $orphanedVideos,
            'totalSize' => $this->orphanedVideoRepository->calculateTotalSize($orphanedVideos),
            'totalSizeFormatted' => $this->orphanedVideoRepository->formatBytes(
                $this->orphanedVideoRepository->calculateTotalSize($orphanedVideos)
            ),
        ]);
    }

    /**
     * Delete a single orphaned video.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'video_path' => 'required|string',
        ]);

        $videoPath = $request->input('video_path');

        // Verify the video is actually orphaned before deleting
        if ($this->orphanedVideoRepository->isVideoReferenced($videoPath)) {
            return redirect()->route('admin.orphaned-videos.index')
                ->with('error', 'Video is referenced in database and cannot be deleted.');
        }

        // Delete the video file
        if ($this->orphanedVideoRepository->deleteVideo($videoPath)) {
            return redirect()->route('admin.orphaned-videos.index')
                ->with('success', 'Video deleted successfully.');
        }

        return redirect()->route('admin.orphaned-videos.index')
            ->with('error', 'Video file not found.');
    }

    /**
     * Delete multiple orphaned videos.
     */
    public function destroyMultiple(Request $request): RedirectResponse
    {
        $request->validate([
            'video_paths' => 'required|string',
        ]);

        // Handle JSON string input from form
        $videoPathsJson = $request->input('video_paths');
        $videoPaths = json_decode($videoPathsJson, true);

        if (!is_array($videoPaths) || empty($videoPaths)) {
            return redirect()->route('admin.orphaned-videos.index')
                ->with('error', 'No videos selected for deletion.');
        }

        $result = $this->orphanedVideoRepository->deleteVideos($videoPaths);

        $message = "Successfully deleted {$result['deleted']} video(s).";
        if (!empty($result['errors'])) {
            $message .= ' ' . implode(' ', $result['errors']);
        }

        return redirect()->route('admin.orphaned-videos.index')
            ->with(!empty($result['errors']) ? 'warning' : 'success', $message);
    }
}

