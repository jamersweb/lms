<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lesson;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Lessons/Index', [
            'lessons' => Lesson::with('module.course')->orderBy('sort_order')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Lessons/Create', [
            'modules' => \App\Models\Module::with('course')->get()
        ]);
    }

    public function store(Request $request, \App\Services\TranscriptParser $parser)
    {
        $rules = [
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:lessons',
            'video_provider' => 'required|in:youtube,mp4,external',
            'sort_order' => 'integer',
            'is_free_preview' => 'boolean',
            'youtube_video_id' => 'nullable|required_if:video_provider,youtube|string',
            'video_file' => 'nullable|required_if:video_provider,mp4|file|mimetypes:video/mp4|max:512000', // 500MB
            'external_video_url' => 'nullable|required_if:video_provider,external|url',
            'transcript_file' => 'nullable|file|mimes:vtt,srt|max:512', // 512KB
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('videos', 'public');
            $validated['video_path'] = $path;
        }

        $lesson = Lesson::create($validated);

        if ($request->hasFile('transcript_file')) {
            $content = file_get_contents($request->file('transcript_file')->getRealPath());
            $segments = $parser->parse($content);
            $lesson->transcriptSegments()->createMany($segments);
        }

        return redirect()->route('admin.lessons.index');
    }

    public function edit(Lesson $lesson)
    {
        return Inertia::render('Admin/Lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'module_id' => $lesson->module_id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'video_provider' => $lesson->video_provider,
                'youtube_video_id' => $lesson->youtube_video_id,
                'external_video_url' => $lesson->external_video_url,
                'video_path' => $lesson->video_path,
                'sort_order' => $lesson->sort_order,
                'is_free_preview' => $lesson->is_free_preview,
                'transcript_segments_count' => $lesson->transcriptSegments()->count(),
                'transcript_preview' => $lesson->transcriptSegments()
                    ->orderBy('start_seconds')
                    ->limit(5)
                    ->get(['id', 'start_seconds', 'end_seconds', 'text']),
            ],
            'modules' => \App\Models\Module::with('course')->get()
        ]);
    }

    public function update(Request $request, Lesson $lesson, \App\Services\TranscriptParser $parser)
    {
        $rules = [
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:lessons,slug,' . $lesson->id,
            'video_provider' => 'required|in:youtube,mp4,external',
            'sort_order' => 'integer',
            'is_free_preview' => 'boolean',
            'youtube_video_id' => 'nullable|required_if:video_provider,youtube|string',
            'video_file' => 'nullable|file|mimetypes:video/mp4|max:512000', // 500MB
            'external_video_url' => 'nullable|required_if:video_provider,external|url',
            'transcript_file' => 'nullable|file|mimes:vtt,srt|max:512', // 512KB
        ];

        $validated = $request->validate($rules);

        if ($request->video_provider === 'mp4') {
             if ($request->hasFile('video_file')) {
                 $path = $request->file('video_file')->store('videos', 'public');
                 $validated['video_path'] = $path;
             } elseif (!$lesson->video_path) {
                 return back()->withErrors(['video_file' => 'MP4 file is required.']);
             }
        }

        $lesson->update($validated);

        if ($request->hasFile('transcript_file')) {
            $content = file_get_contents($request->file('transcript_file')->getRealPath());
            $segments = $parser->parse($content);
            
            // Replace existing segments
            $lesson->transcriptSegments()->delete();
            $lesson->transcriptSegments()->createMany($segments);
        }

        return redirect()->route('admin.lessons.index');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return redirect()->back();
    }
}
