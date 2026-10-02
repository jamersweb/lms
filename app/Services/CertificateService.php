<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

class CertificateService
{
    public function awardCompletedCourse(User $user, Course $course): ?Certificate
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $course) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Certificate::where('user_id', $user->id)->where('course_id', $course->id)
                ->where('type', 'course_completion')->first();
            if ($existing) { return $existing; }
            $course->load('modules.lessons.quizQuestions', 'modules.task');
            $lessons = $course->modules->flatMap->lessons;
            if ($lessons->isEmpty()) { return null; }
            $progression = app(ProgressionService::class);
            foreach ($course->modules as $module) {
                if (!$progression->isModuleFullyCompleted($user, $module)) { return null; }
                if ($module->task && $module->task->unlock_next_lesson &&
                    !$module->task->progress()->where('user_id', $user->id)->where('status', 'completed')->exists()) {
                    return null;
                }
            }
            foreach ($lessons as $lesson) {
                if ($lesson->quizQuestions->isNotEmpty() && !\App\Models\LessonQuizAttempt::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)->where('passed', true)->exists()) { return null; }
            }
            $certificate = $this->awardCertificate($user, 'course_completion', $course);
            PointsService::award($user, 'course_completed', 50);
            app(\App\Services\WhatsApp\TriggerService::class)->fireAsync('certificate_delivery', $user);
            app(\App\Services\WhatsApp\TriggerService::class)->fireAsync('survey_link', $user);
            return $certificate;
        });
    }

    public function awardCertificate(User $user, string $type, ?Course $course = null, ?string $level = null): Certificate
    {
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course?->id,
            'type' => $type,
            'level' => $level,
            'certificate_number' => Certificate::generateCertificateNumber(),
            'issued_at' => now(),
            'metadata' => [
                'user_name' => $user->name,
                'course_title' => $course?->title,
                'issued_date' => now()->toDateString(),
            ],
        ]);

        // Generate PDF asynchronously or on-demand
        // For now, we'll generate on first download request

        return $certificate;
    }

    public function generatePdf(Certificate $certificate): Certificate
    {
        $user = $certificate->user;
        $course = $certificate->course;

        $data = [
            'certificate_number' => $certificate->certificate_number,
            'user_name' => $user->name,
            'course_title' => $course?->title ?? 'Learning Journey',
            'type' => $certificate->type,
            'level' => $certificate->level,
            'issued_date' => $certificate->issued_at->format('F j, Y'),
        ];

        $pdf = DomPDF::loadView('certificates.pdf', $data);
        
        $filename = "certificates/{$certificate->id}-{$certificate->certificate_number}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());
        
        // Ensure directory exists
        $directory = storage_path('app/certificates');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $certificate->update(['pdf_path' => $filename]);

        return $certificate;
    }
}
