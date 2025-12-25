<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkoutVideo;
use Illuminate\Http\Request;

/**
 * Controller for reviewing workout videos submitted by members.
 * 
 * Handles viewing and reviewing workout videos uploaded by members as part
 * of their workout plans. Trainers can approve or reject videos with
 * feedback. Accessible by both admin and trainer roles, with trainers
 * only seeing videos from their assigned members.
 */
class WorkoutVideoReviewController extends Controller
{
    /**
     * Display workout videos for review (trainers/admins).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->get('status', 'pending');

        $baseQuery = WorkoutVideo::with(['user', 'workoutPlan.trainer'])
            ->latest();

        if ($user->hasRole('trainer')) {
            $baseQuery->whereHas('workoutPlan', function ($query) use ($user) {
                $query->where('trainer_id', $user->id);
            });
        }

        $statusCounts = [
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'all' => (clone $baseQuery)->count(),
        ];

        $videosQuery = clone $baseQuery;

        if ($status !== 'all') {
            $videosQuery->where('status', $status);
        }

        $videos = $videosQuery->paginate(12)->withQueryString();

        return view('admin.trainer.workout-videos.index', compact('videos', 'statusCounts', 'status'));
    }

    /**
     * Approve or reject a workout video.
     */
    public function review(Request $request, WorkoutVideo $workoutVideo)
    {
        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'trainer_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if ($user->hasRole('trainer') && $workoutVideo->workoutPlan->trainer_id !== $user->id) {
            abort(403, 'You are not authorized to review this video.');
        }

        $action = $request->input('action');
        $feedback = $request->input('trainer_feedback');

        $workoutVideo->update([
            'status' => $action === 'approve' ? 'approved' : 'rejected',
            'trainer_feedback' => $feedback,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        $member = $workoutVideo->user;
        if ($action === 'approve') {
            \App\Events\EntityApproved::dispatch($member, 'workout_video', $workoutVideo, "Your workout video '{$workoutVideo->exercise_name}' has been approved.");
        } else {
            \App\Events\EntityRejected::dispatch($member, 'workout_video', $workoutVideo, $feedback);
        }

        return back()->with('success', "Video {$action}ed successfully.");
    }
}

