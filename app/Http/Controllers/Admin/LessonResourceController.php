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
            'sunnah_pointers_en' => 'nullable|string',
            'sunnah_pointers_en_roman' => 'nullable|string',
            'sunnah_pointers_ur' => 'nullable|string',
            'duas_text' => 'nullable|string',
            'duas_text_en' => 'nullable|string',
            'duas_text_en_roman' => 'nullable|string',
            'duas_text_ur' => 'nullable|string',
            'audio_file' => [
                'nullable',
                'file',
                'max:10240', // 10MB
                'mimes:mp3,wav,ogg,m4a',
                'mimetypes:audio/mpeg,audio/mp3,audio/x-wav,audio/wav,audio/ogg,audio/x-m4a,audio/mp4',
            ],
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120', // 5MB
        ]);

        $resource = $lesson->resource;
        $hasNewContent = ! empty(trim($validated['sunnah_pointers'] ?? ''))
            || ! empty(trim($validated['sunnah_pointers_en'] ?? ''))
            || ! empty(trim($validated['sunnah_pointers_en_roman'] ?? ''))
            || ! empty(trim($validated['sunnah_pointers_ur'] ?? ''))
            || ! empty(trim($validated['duas_text'] ?? ''))
            || ! empty(trim($validated['duas_text_en'] ?? ''))
            || ! empty(trim($validated['duas_text_en_roman'] ?? ''))
            || ! empty(trim($validated['duas_text_ur'] ?? ''))
            || $request->hasFile('audio_file')
            || $request->hasFile('pdf_file');
        $hasExistingContent = $resource
            && (
                trim($resource->sunnah_pointers ?? '') !== ''
                || trim($resource->sunnah_pointers_en ?? '') !== ''
                || trim($resource->sunnah_pointers_en_roman ?? '') !== ''
                || trim($resource->sunnah_pointers_ur ?? '') !== ''
                || trim($resource->duas_text ?? '') !== ''
                || trim($resource->duas_text_en ?? '') !== ''
                || trim($resource->duas_text_en_roman ?? '') !== ''
                || trim($resource->duas_text_ur ?? '') !== ''
                || $resource->audio_path
                || $resource->pdf_path
            );
        if (! $hasNewContent && ! $hasExistingContent) {
            return back()->withErrors([
                'sunnah_pointers' => 'Please provide at least one resource (Sunnah pointers, Duas, audio, or PDF).',
            ]);
        }

        $resource = $resource ?? new LessonResource(['lesson_id' => $lesson->id]);

        // Handle file uploads
        if ($request->hasFile('audio_file')) {
            if ($resource->audio_path && Storage::disk('public')->exists($resource->audio_path)) {
                Storage::disk('public')->delete($resource->audio_path);
            }
            $audioPath = $request->file('audio_file')->store('lesson-resources/audio', 'public');
            $validated['audio_path'] = $audioPath;
        }

        if ($request->hasFile('pdf_file')) {
            if ($resource->pdf_path && Storage::disk('public')->exists($resource->pdf_path)) {
                Storage::disk('public')->delete($resource->pdf_path);
            }
            $pdfPath = $request->file('pdf_file')->store('lesson-resources/pdf', 'public');
            $validated['pdf_path'] = $pdfPath;
        }

        // Only fill fields that belong on the model (exclude uploaded file objects)
        $resource->sunnah_pointers = $validated['sunnah_pointers'] ?? $resource->sunnah_pointers;
        $resource->sunnah_pointers_en = $validated['sunnah_pointers_en'] ?? $resource->sunnah_pointers_en;
        $resource->sunnah_pointers_en_roman = $validated['sunnah_pointers_en_roman'] ?? $resource->sunnah_pointers_en_roman;
        $resource->sunnah_pointers_ur = $validated['sunnah_pointers_ur'] ?? $resource->sunnah_pointers_ur;
        $resource->duas_text = $validated['duas_text'] ?? $resource->duas_text;
        $resource->duas_text_en = $validated['duas_text_en'] ?? $resource->duas_text_en;
        $resource->duas_text_en_roman = $validated['duas_text_en_roman'] ?? $resource->duas_text_en_roman;
        $resource->duas_text_ur = $validated['duas_text_ur'] ?? $resource->duas_text_ur;
        if (isset($validated['audio_path'])) {
            $resource->audio_path = $validated['audio_path'];
        }
        if (isset($validated['pdf_path'])) {
            $resource->pdf_path = $validated['pdf_path'];
        }
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
