<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;


class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::withCount('modules')->orderBy('sort_order')->get()->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'sort_order' => $course->sort_order,
                'thumbnail' => $course->thumbnail ? Storage::disk('public')->url($course->thumbnail) : null,
                'modules_count' => $course->modules_count,
            ];
        });

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Courses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses',
            'description' => 'nullable|string',
            'title_en' => 'nullable|string|max:255',
            'title_en_roman' => 'nullable|string|max:255',
            'title_ur' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_en_roman' => 'nullable|string',
            'description_ur' => 'nullable|string',
            'sort_order' => 'integer',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        Course::create($validated);

        return redirect()->route('admin.courses.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        $course->load('contentRule');

        return Inertia::render('Admin/Courses/Edit', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'sort_order' => $course->sort_order,
                'thumbnail' => $course->thumbnail ? Storage::url($course->thumbnail) : null,
            ],
            'contentRule' => $course->contentRule ? [
                'min_level' => $course->contentRule->min_level,
                'gender' => $course->contentRule->gender,
                'requires_bayah' => $course->contentRule->requires_bayah,
            ] : null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug,' . $course->id,
            'description' => 'nullable|string',
            'title_en' => 'nullable|string|max:255',
            'title_en_roman' => 'nullable|string|max:255',
            'title_ur' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_en_roman' => 'nullable|string',
            'description_ur' => 'nullable|string',
            'sort_order' => 'integer',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
        ]);

        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        } else {
            // Keep existing thumbnail if no new file uploaded
            unset($validated['thumbnail']);
        }

        $course->update($validated);

        return redirect()->route('admin.courses.edit', $course)->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->back();
    }
}
