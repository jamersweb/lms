<?php

namespace App\Http\Controllers\Admin;

use App\Models\Module;
use App\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    /**
     * Display a listing of modules.
     */
    public function index(Request $request)
    {
        $query = Module::with('course')->withCount('lessons');

        // Filter by course
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        // Search
        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $modules = $query->orderBy('course_id')
            ->orderBy('sort_order')
            ->paginate(15)
            ->through(fn($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'sort_order' => $module->sort_order,
                'lessons_count' => $module->lessons_count,
                'course' => [
                    'id' => $module->course->id,
                    'title' => $module->course->title,
                ],
            ]);

        $courses = Course::orderBy('title')->get(['id', 'title']);

        return Inertia::render('Admin/Modules/Index', [
            'modules' => $modules,
            'courses' => $courses,
            'filters' => [
                'search' => $request->search,
                'course_id' => $request->course_id,
            ],
        ]);
    }

    /**
     * Show form for creating a new module.
     */
    public function create(Request $request)
    {
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $selectedCourseId = $request->course_id;

        return Inertia::render('Admin/Modules/Create', [
            'courses' => $courses,
            'selectedCourseId' => $selectedCourseId,
        ]);
    }

    /**
     * Store a newly created module.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        // Auto-assign sort order if not provided
        if (empty($validated['sort_order'])) {
            $maxOrder = Module::where('course_id', $validated['course_id'])->max('sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        Module::create($validated);

        return redirect()->route('admin.modules.index', ['course_id' => $validated['course_id']])
            ->with('success', 'Module created successfully.');
    }

    /**
     * Display the specified module.
     */
    public function show(Module $module)
    {
        $module->load(['course', 'lessons']);

        return Inertia::render('Admin/Modules/Show', [
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'slug' => $module->slug,
                'sort_order' => $module->sort_order,
                'course' => [
                    'id' => $module->course->id,
                    'title' => $module->course->title,
                ],
                'lessons' => $module->lessons->map(fn($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'sort_order' => $lesson->sort_order,
                    'duration_seconds' => $lesson->duration_seconds,
                ]),
            ],
        ]);
    }

    /**
     * Show form for editing the specified module.
     */
    public function edit(Module $module)
    {
        $module->load('contentRule');
        $courses = Course::orderBy('title')->get(['id', 'title']);

        return Inertia::render('Admin/Modules/Edit', [
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'sort_order' => $module->sort_order,
                'course_id' => $module->course_id,
            ],
            'courses' => $courses,
            'contentRule' => $module->contentRule ? [
                'min_level' => $module->contentRule->min_level,
                'gender' => $module->contentRule->gender,
                'requires_bayah' => $module->contentRule->requires_bayah,
            ] : null,
        ]);
    }

    /**
     * Update the specified module.
     */
    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        $module->update($validated);

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module updated successfully.');
    }

    /**
     * Remove the specified module.
     */
    public function destroy(Module $module)
    {
        // Check if module has lessons
        if ($module->lessons()->count() > 0) {
            return back()->with('error', 'Cannot delete module with existing lessons. Delete lessons first.');
        }

        $module->delete();

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module deleted successfully.');
    }
}
