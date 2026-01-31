<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReflection;
use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonReflectionController extends Controller
{
    /**
     * Display a listing of reflections with filters.
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->input('status', LessonReflection::STATUS_PENDING),
            'course_id' => $request->input('course_id'),
            'module_id' => $request->input('module_id'),
            'lesson_id' => $request->input('lesson_id'),
            'q' => $request->input('q'), // Search student name/email
        ];

        // Build query with eager loading
        $query = LessonReflection::with([
            'user:id,name,email',
            'lesson:id,title,module_id',
            'lesson.module:id,title,course_id',
            'lesson.module.course:id,title',
            'reviewer:id,name',
        ]);

        // Apply filters
        if ($filters['status']) {
            $query->where('review_status', $filters['status']);
        }

        if ($filters['course_id']) {
            $query->whereHas('lesson.module', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if ($filters['module_id']) {
            $query->whereHas('lesson', function ($q) use ($filters) {
                $q->where('module_id', $filters['module_id']);
            });
        }

        if ($filters['lesson_id']) {
            $query->where('lesson_id', $filters['lesson_id']);
        }

        // Search by student name or email
        if ($filters['q']) {
            $searchTerm = $filters['q'];
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('email', 'like', '%' . $searchTerm . '%');
                });
            });
        }

        // Sort: pending first, then newest
        $query->orderByRaw("CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END")
              ->orderByDesc('created_at');

        $reflections = $query->paginate(20)->withQueryString();

        // Get courses for filter dropdown
        $courses = Course::select('id', 'title')->orderBy('title')->get();

        // Get modules for selected course
        $modules = collect();
        if ($filters['course_id']) {
            $modules = Module::where('course_id', $filters['course_id'])
                ->select('id', 'title')
                ->orderBy('sort_order')
                ->get();
        }

        // Get lessons for selected module
        $lessons = collect();
        if ($filters['module_id']) {
            $lessons = Lesson::where('module_id', $filters['module_id'])
                ->select('id', 'title')
                ->orderBy('sort_order')
                ->get();
        }

        return Inertia::render('Admin/LessonReflections/Index', [
            'reflections' => $reflections,
            'filters' => $filters,
            'courses' => $courses,
            'modules' => $modules,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Display the specified reflection.
     */
    public function show(LessonReflection $reflection)
    {
        $reflection->load([
            'user:id,name,email',
            'lesson:id,title,module_id',
            'lesson.module:id,title,course_id',
            'lesson.module.course:id,title',
            'reviewer:id,name',
        ]);

        return Inertia::render('Admin/LessonReflections/Show', [
            'reflection' => $reflection,
        ]);
    }

    /**
     * Update the reflection review status and teacher note.
     */
    public function update(Request $request, LessonReflection $reflection)
    {
        $validated = $request->validate([
            'review_status' => ['required', 'in:' . implode(',', LessonReflection::getValidStatuses())],
            'teacher_note' => ['nullable', 'string', 'max:5000'],
        ]);

        // Store original takeaway to ensure data integrity
        $originalTakeaway = $reflection->takeaway;

        // Update review status and teacher note
        $reflection->review_status = $validated['review_status'];
        $reflection->teacher_note = $validated['teacher_note'] ?? null;

        // Handle reviewed_by and reviewed_at
        if ($validated['review_status'] === LessonReflection::STATUS_PENDING) {
            // Reset to pending: clear reviewed fields
            $reflection->reviewed_by = null;
            $reflection->reviewed_at = null;
        } else {
            // Mark as reviewed: set reviewed fields
            $reflection->reviewed_by = auth()->id();
            $reflection->reviewed_at = now();
        }

        $reflection->save();

        // Ensure takeaway was not modified (data integrity check)
        if ($reflection->takeaway !== $originalTakeaway) {
            // This should never happen, but log if it does
            \Log::warning('Reflection takeaway was modified during review update', [
                'reflection_id' => $reflection->id,
                'original' => $originalTakeaway,
                'current' => $reflection->takeaway,
            ]);
            // Restore original
            $reflection->takeaway = $originalTakeaway;
            $reflection->save();
        }

        $statusMessages = [
            LessonReflection::STATUS_PENDING => 'Reflection reset to pending.',
            LessonReflection::STATUS_REVIEWED => 'Reflection marked as reviewed.',
            LessonReflection::STATUS_NEEDS_FOLLOWUP => 'Reflection marked as needs follow-up.',
        ];

        return back()->with('success', $statusMessages[$validated['review_status']] ?? 'Reflection updated.');
    }
}
