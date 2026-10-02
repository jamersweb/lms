<?php

namespace App\Services;

use App\Models\Course;

class CourseReadinessService
{
    public function inspect(Course $course): array
    {
        $course->loadMissing('modules.lessons.quizQuestions', 'modules.lessons.contentRule');
        $issues = [];
        if ($course->modules->isEmpty()) { $issues[] = ['title' => 'Add a module', 'url' => route('admin.modules.create')]; }
        foreach ($course->modules as $module) {
            if ($module->lessons->isEmpty()) {
                $issues[] = ['title' => $module->title.': add a lesson', 'url' => route('admin.lessons.create')];
            }
            foreach ($module->lessons as $lesson) {
                $problems = [];
                if (!$lesson->video_url) { $problems[] = 'video missing'; }
                if (!($lesson->duration_seconds > 0)) { $problems[] = 'set verified video duration'; }
                foreach ($lesson->quizQuestions as $question) {
                    $indices = $question->getCorrectIndices();
                    if (count($question->options ?? []) < 2 || !$indices || max($indices) >= count($question->options ?? [])) {
                        $problems[] = 'quiz options or correct answers incomplete';
                        break;
                    }
                }
                if ($problems) { $issues[] = ['title' => $lesson->title.': '.implode('; ', $problems), 'url' => route('admin.lessons.edit', $lesson)]; }
            }
        }
        return ['ready' => !$issues, 'issues' => $issues, 'lessons_count' => $course->modules->sum(fn ($module) => $module->lessons->count())];
    }
}
