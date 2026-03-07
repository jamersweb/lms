<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HabitController extends Controller
{
    /**
     * Display all habits across all users.
     */
    public function index(Request $request)
    {
        $query = Habit::with(['user', 'lesson.module.course']);

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhereHas('lesson', fn($lq) => $lq->where('title', 'like', "%{$request->search}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$request->search}%"));
            });
        }

        // Filter by user (legacy habits only)
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by lesson (lesson-based habits)
        if ($request->lesson_id) {
            $query->where('lesson_id', $request->lesson_id);
        }

        $habits = $query->with(['lesson.module.course', 'user'])
            ->withCount('logs')
            ->latest()
            ->paginate(15)
            ->through(fn($habit) => [
                'id' => $habit->id,
                'title' => $habit->title,
                'frequency_type' => $habit->frequency_type,
                'description' => $habit->description,
                'logs_count' => $habit->logs_count,
                'lesson' => $habit->lesson ? [
                    'id' => $habit->lesson->id,
                    'title' => $habit->lesson->title,
                    'course' => $habit->lesson->module->course->title ?? '',
                ] : null,
                'user' => $habit->user ? ['id' => $habit->user->id, 'name' => $habit->user->name] : null,
                'created_at' => $habit->created_at->format('M d, Y'),
            ]);

        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Habits/Index', [
            'habits' => $habits,
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'user_id' => $request->user_id,
            ],
        ]);
    }

    /**
     * Show form for creating a lesson-based habit.
     * Habit appears for users who have completed the linked lesson.
     */
    public function create(Request $request)
    {
        $lessons = \App\Models\Lesson::with('module.course')
            ->orderBy('title')
            ->get()
            ->map(function($lesson) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'course_title' => $lesson->module->course->title ?? '',
                    'module_title' => $lesson->module->title ?? '',
                ];
            });

        return Inertia::render('Admin/Habits/Create', [
            'lessons' => $lessons,
        ]);
    }

    /**
     * Store a new lesson-based habit.
     * Users see this habit only after completing the linked lesson video.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency_type' => 'required|in:daily,weekly,custom',
            'target_per_day' => 'nullable|integer|min:1|max:10',
        ]);

        $data = [
            'user_id' => null, // Lesson-based: shared for all users who completed the lesson
            'lesson_id' => $validated['lesson_id'],
            'title' => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'frequency_type' => $validated['frequency_type'] ?? 'daily',
            'target_per_day' => isset($validated['target_per_day']) ? (int)$validated['target_per_day'] : 1,
            'is_active' => true,
        ];

        Habit::create($data);

        return redirect()->route('admin.habits.index')
            ->with('success', 'Habit created. It will appear for users after they complete the lesson.');
    }

    /**
     * Show form for editing a habit.
     */
    public function edit(Habit $habit)
    {
        $lessons = \App\Models\Lesson::with('module.course')
            ->orderBy('title')
            ->get()
            ->map(function($lesson) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'course_title' => $lesson->module->course->title ?? '',
                    'module_title' => $lesson->module->title ?? '',
                ];
            });

        return Inertia::render('Admin/Habits/Edit', [
            'habit' => [
                'id' => $habit->id,
                'title' => $habit->title,
                'description' => $habit->description,
                'frequency_type' => $habit->frequency_type,
                'target_per_day' => $habit->target_per_day,
                'lesson_id' => $habit->lesson_id,
                'lesson' => $habit->lesson ? [
                    'id' => $habit->lesson->id,
                    'title' => $habit->lesson->title,
                ] : null,
                'user' => $habit->user ? [
                    'id' => $habit->user->id,
                    'name' => $habit->user->name,
                ] : null,
            ],
            'lessons' => $lessons,
        ]);
    }

    /**
     * Update the specified habit.
     */
    public function update(Request $request, Habit $habit)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency_type' => 'required|in:daily,weekly,custom',
            'target_per_day' => 'nullable|integer|min:1|max:10',
            'lesson_id' => 'nullable|exists:lessons,id',
        ]);

        // Map validated data
        $data = [
            'title' => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'frequency_type' => $validated['frequency_type'],
            'target_per_day' => isset($validated['target_per_day']) ? (int)$validated['target_per_day'] : 1,
            'lesson_id' => $validated['lesson_id'] ?? null,
        ];

        $habit->update($data);

        return redirect()->route('admin.habits.index')
            ->with('success', 'Habit updated successfully.');
    }

    /**
     * Delete the specified habit.
     */
    public function destroy(Habit $habit)
    {
        $habit->delete();

        return redirect()->route('admin.habits.index')
            ->with('success', 'Habit deleted successfully.');
    }
}
