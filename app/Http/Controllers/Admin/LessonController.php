<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lesson;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lessons = Lesson::with('module.course')->orderBy('sort_order')->get()->map(function ($lesson) {
            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'image' => $lesson->image ? Storage::disk('public')->url($lesson->image) : null,
                'video_provider' => $lesson->video_provider,
                'is_free_preview' => $lesson->is_free_preview,
                'module' => $lesson->module ? [
                    'id' => $lesson->module->id,
                    'title' => $lesson->module->title,
                    'course' => $lesson->module->course ? [
                        'id' => $lesson->module->course->id,
                        'title' => $lesson->module->course->title,
                    ] : null,
                ] : null,
            ];
        });

        return Inertia::render('Admin/Lessons/Index', [
            'lessons' => $lessons
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
            'title_en' => 'nullable|string|max:255',
            'title_en_roman' => 'nullable|string|max:255',
            'title_ur' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'video_provider' => 'required|in:youtube,mp4,external,vimeo',
            'sort_order' => 'integer',
            'is_free_preview' => 'boolean',
            'youtube_video_id' => 'nullable|required_if:video_provider,youtube|string',
            'video_file' => 'nullable|required_if:video_provider,mp4|file|mimetypes:video/mp4|max:512000', // 500MB
            'external_video_url' => 'nullable|required_if:video_provider,external|url',
            'transcript_file' => 'nullable|file|mimes:vtt,srt|max:512', // 512KB
            'release_at' => 'nullable|date',
            'release_day_offset' => 'nullable|integer|min:0|max:365',
        ];

        $validated = $request->validate($rules);

        // Wrap lesson creation and transcript upload in transaction
        $lesson = DB::transaction(function () use ($request, $validated, $parser) {
            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('lessons', 'public');
            }

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

            return $lesson;
        });

        return redirect()->route('admin.lessons.edit', $lesson);
    }

    public function edit(Lesson $lesson)
    {
        $lesson->load('contentRule', 'task', 'resource', 'quizQuestions');

        return Inertia::render('Admin/Lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'module_id' => $lesson->module_id,
                'title' => $lesson->title,
                'title_en' => $lesson->title_en,
                'title_en_roman' => $lesson->title_en_roman,
                'title_ur' => $lesson->title_ur,
                'slug' => $lesson->slug,
                'image' => $lesson->image ? Storage::disk('public')->url($lesson->image) : null,
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
            'modules' => \App\Models\Module::with('course')->get(),
            'contentRule' => $lesson->contentRule ? [
                'min_level' => $lesson->contentRule->min_level,
                'gender' => $lesson->contentRule->gender,
                'requires_bayah' => $lesson->contentRule->requires_bayah,
            ] : null,
            'task' => $lesson->task ? [
                'id' => $lesson->task->id,
                'title' => $lesson->task->title,
                'instructions' => $lesson->task->instructions,
                'required_days' => $lesson->task->required_days,
                'unlock_next_lesson' => $lesson->task->unlock_next_lesson,
            ] : null,
            'resource' => $lesson->resource ? [
                'id' => $lesson->resource->id,
                'sunnah_pointers' => $lesson->resource->sunnah_pointers,
                'duas_text' => $lesson->resource->duas_text,
                'audio_path' => $lesson->resource->audio_path,
                'pdf_path' => $lesson->resource->pdf_path,
            ] : null,
            'release_at' => $lesson->release_at ? $lesson->release_at->toIso8601String() : null,
            'release_day_offset' => $lesson->release_day_offset,
            'quiz_questions' => $lesson->quizQuestions->map(fn($q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'options' => $q->options,
                'correct_index' => $q->correct_index,
                'correct_indices' => $q->getCorrectIndices(),
            ])->values()->toArray(),
        ]);
    }

    public function update(Request $request, Lesson $lesson, \App\Services\TranscriptParser $parser)
    {
        $rules = [
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'title_en_roman' => 'nullable|string|max:255',
            'title_ur' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'video_provider' => 'required|in:youtube,mp4,external,vimeo',
            'sort_order' => 'integer',
            'is_free_preview' => 'boolean',
            'youtube_video_id' => 'nullable|required_if:video_provider,youtube|string',
            'video_file' => 'nullable|file|mimetypes:video/mp4|max:512000', // 500MB
            'external_video_url' => 'nullable|required_if:video_provider,external|url',
            'transcript_file' => 'nullable|file|mimes:vtt,srt|max:512', // 512KB
            'release_at' => 'nullable|date',
            'release_day_offset' => 'nullable|integer|min:0|max:365',
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

        // Wrap in transaction to ensure data consistency
        DB::transaction(function () use ($lesson, $validated, $request, $parser) {
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($lesson->image) {
                    Storage::disk('public')->delete($lesson->image);
                }
                $validated['image'] = $request->file('image')->store('lessons', 'public');
            } else {
                // Keep existing image if no new file uploaded
                unset($validated['image']);
            }

            $lesson->update($validated);

            if ($request->hasFile('transcript_file')) {
                $content = file_get_contents($request->file('transcript_file')->getRealPath());
                $segments = $parser->parse($content);

                // Replace existing segments
                $lesson->transcriptSegments()->delete();
                $lesson->transcriptSegments()->createMany($segments);
            }
        });

        return redirect()->route('admin.lessons.edit', $lesson)->with('success', 'Lesson updated successfully.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return redirect()->back();
    }
}
