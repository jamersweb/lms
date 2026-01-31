<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCheckin;
use App\Models\TaskProgress;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskProgressController extends Controller
{
    /**
     * Get task details with user's progress.
     */
    public function show(Task $task)
    {
        $user = auth()->user();

        // Ensure user is eligible for the lesson's course
        $taskable = $task->taskable;
        if ($taskable instanceof \App\Models\Lesson) {
            $course = $taskable->module->course;
            if (!$user->isEnrolledIn($course->id) && !$taskable->is_free_preview) {
                abort(403, 'You must be enrolled in this course to view tasks.');
            }
        }

        // Get or create progress
        $progress = TaskProgress::firstOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $user->id,
            ],
            [
                'status' => TaskProgress::STATUS_PENDING,
                'days_done' => 0,
            ]
        );

        // Load relationships
        $progress->load('checkins');

        return response()->json([
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'instructions' => $task->instructions,
                'required_days' => $task->required_days,
                'unlock_next_lesson' => $task->unlock_next_lesson,
            ],
            'progress' => [
                'id' => $progress->id,
                'status' => $progress->status,
                'days_done' => $progress->days_done,
                'required_days' => $task->required_days,
                'last_checkin_on' => $progress->last_checkin_on?->toDateString(),
                'has_checked_in_today' => $progress->hasCheckedInToday(),
                'completed_at' => $progress->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Record a daily check-in for a task.
     */
    public function checkin(Request $request, Task $task)
    {
        $user = auth()->user();

        // Ensure user is eligible for the lesson's course
        $taskable = $task->taskable;
        if ($taskable instanceof \App\Models\Lesson) {
            $course = $taskable->module->course;
            if (!$user->isEnrolledIn($course->id) && !$taskable->is_free_preview) {
                abort(403, 'You must be enrolled in this course to check in.');
            }
        }

        // Get or create progress
        $progress = TaskProgress::firstOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $user->id,
            ],
            [
                'status' => TaskProgress::STATUS_PENDING,
                'days_done' => 0,
            ]
        );

        // Check if already completed
        if ($progress->completed_at) {
            return response()->json([
                'ok' => false,
                'message' => 'Task already completed.',
            ], 409);
        }

        // Check if already checked in today
        $today = now()->toDateString();
        if ($progress->hasCheckedInToday()) {
            return response()->json([
                'ok' => false,
                'message' => 'You have already checked in today. Come back tomorrow!',
            ], 422);
        }

        // Use database transaction for atomicity
        DB::transaction(function () use ($progress, $task, $today) {
            // Create check-in record
            TaskCheckin::create([
                'task_progress_id' => $progress->id,
                'checkin_on' => $today,
            ]);

            // Update progress
            $progress->days_done += 1;
            $progress->last_checkin_on = $today;

            // Set started_at if this is the first check-in
            if (!$progress->started_at) {
                $progress->started_at = now();
            }

            // Check if completed
            if ($progress->days_done >= $task->required_days) {
                $progress->status = TaskProgress::STATUS_COMPLETED;
                $progress->completed_at = now();
            } else {
                $progress->status = TaskProgress::STATUS_IN_PROGRESS;
            }

            $progress->save();
        });

        // Refresh progress
        $progress->refresh();

        // Log task check-in
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_TASK_CHECKIN_COMPLETED,
            $user,
            [
                'subject' => $task,
                'meta' => [
                    'days_done' => $progress->days_done,
                    'required_days' => $task->required_days,
                    'completed' => $progress->status === TaskProgress::STATUS_COMPLETED,
                ],
            ]
        );

        return response()->json([
            'ok' => true,
            'status' => $progress->status,
            'days_done' => $progress->days_done,
            'required_days' => $task->required_days,
            'completed' => $progress->status === TaskProgress::STATUS_COMPLETED,
        ]);
    }
}
