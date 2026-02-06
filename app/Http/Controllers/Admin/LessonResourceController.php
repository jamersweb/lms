<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonResourceController extends Controller
{
    /**
     * Store or update lesson resources
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'sunnah_pointers' => 'nullable|string',
            'duas_text' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240', // 10MB
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120', // 5MB
        ]);

        // Ensure at least one field is provided
        if (empty($validated['sunnah_pointers']) && 
            empty($validated['duas_text']) && 
            !$request->hasFile('audio_file') && 
            !$request->hasFile('pdf_file') &&
            !$resource) {
            return back()->withErrors([
                'sunnah_pointers' => 'Please provide at least one resource (Sunnah pointers, Duas, audio, or PDF).'
            ]);
        }

        $resource = $lesson->resource ?? new LessonResource(['lesson_id' => $lesson->id]);

        // Handle file uploads
        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('lesson-resources/audio', 'public');
            $validated['audio_path'] = $audioPath;
        }

        if ($request->hasFile('pdf_file')) {
            $pdfPath = $request->file('pdf_file')->store('lesson-resources/pdf', 'public');
            $validated['pdf_path'] = $pdfPath;
        }

        // Update or create resource
        $resource->fill($validated);
        $resource->save();

        return redirect()->route('admin.lessons.edit', $lesson)
            ->with('success', 'Lesson resources updated successfully.');
    }

    /**
     * Delete lesson resources
     */
    public function destroy(Lesson $lesson)
    {
        $resource = $lesson->resource;

        if ($resource) {
            // Delete files if they exist
            if ($resource->audio_path && Storage::disk('public')->exists($resource->audio_path)) {
                Storage::disk('public')->delete($resource->audio_path);
            }
            if ($resource->pdf_path && Storage::disk('public')->exists($resource->pdf_path)) {
                Storage::disk('public')->delete($resource->pdf_path);
            }

            $resource->delete();
        }

        return redirect()->route('admin.lessons.edit', $lesson)
            ->with('success', 'Lesson resources deleted successfully.');
    }
}
