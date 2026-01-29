<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LessonReflection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonReflectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $reflections = LessonReflection::with(['lesson.module.course', 'user'])
            ->when($status, fn ($q) => $q->where('review_status', $status))
            ->orderByDesc('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/LessonReflections/Index', [
            'reflections' => $reflections,
            'filter_status' => $status,
        ]);
    }

    public function update(Request $request, LessonReflection $reflection)
    {
        $data = $request->validate([
            'review_status' => ['required', 'in:pending,approved,needs_clarification'],
            'mentor_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $reflection->review_status = $data['review_status'];
        $reflection->mentor_note = $data['mentor_note'] ?? null;
        $reflection->save();

        return back()->with('success', 'Reflection updated.');
    }
}

