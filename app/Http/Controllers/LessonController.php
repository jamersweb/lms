<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    public function show($courseId, $lessonId)
    {
        $course = Course::with(['modules.lessons'])->findOrFail($courseId);
        $lesson = Lesson::with(['module'])->findOrFail($lessonId);

        // Build flat playlist from all modules
        $playlist = [];
        $prevLessonId = null;
        $nextLessonId = null;
        $foundCurrent = false;

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $l) {
                $isCurrent = $l->id == $lessonId;
                
                if (!$foundCurrent && !$isCurrent) {
                    $prevLessonId = $l->id;
                }
                
                if ($foundCurrent && !$nextLessonId) {
                    $nextLessonId = $l->id;
                }

                if ($isCurrent) {
                    $foundCurrent = true;
                }

                $playlist[] = [
                    'id' => $l->id,
                    'title' => $l->title,
                    'duration' => $this->formatDuration($l->duration_seconds),
                    'is_completed' => false, // TODO: Check from lesson_progress
                    'is_current' => $isCurrent,
                ];
            }
        }

        // Determine video URL based on provider
        $videoUrl = match($lesson->video_provider) {
            'youtube' => $lesson->youtube_video_id 
                ? "https://www.youtube.com/embed/{$lesson->youtube_video_id}" 
                : null,
            'external' => $lesson->external_video_url,
            'mp4' => $lesson->video_path,
            default => null,
        };

        return Inertia::render('Lessons/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description ?? 'Watch this lesson to learn more.',
                'video_url' => $videoUrl,
                'video_provider' => $lesson->video_provider,
                'duration' => $this->formatDuration($lesson->duration_seconds),
                'transcript' => $lesson->transcript ?? null,
                'next_lesson_id' => $nextLessonId,
                'prev_lesson_id' => $prevLessonId,
            ],
            'playlist' => $playlist,
        ]);
    }

    private function formatDuration(?int $seconds): string
    {
        if (!$seconds) return '0m';
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }
}
