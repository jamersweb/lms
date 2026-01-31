<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Upsert a task for a lesson.
     */
    public function upsert(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'required_days' => ['required', 'integer', 'min:1', 'max:365'],
            'instructions' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:practice_streak'],
            'unlock_next_lesson' => ['boolean'],
        ]);

        // Set defaults
        $validated['type'] = $validated['type'] ?? 'practice_streak';
        $validated['unlock_next_lesson'] = $validated['unlock_next_lesson'] ?? true;

        // Upsert task
        $task = $lesson->task()->updateOrCreate(
            [],
            $validated
        );

        return back()->with('success', 'Task saved successfully.');
    }

    /**
     * Delete a task for a lesson.
     */
    public function destroy(Lesson $lesson)
    {
        $task = $lesson->task;

        if ($task) {
            $task->delete();
            return back()->with('success', 'Task deleted successfully.');
        }

        return back()->with('info', 'No task found to delete.');
    }
}
