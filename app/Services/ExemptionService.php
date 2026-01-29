<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseExemption;
use App\Models\SunnahAssessment;
use App\Models\User;
use App\Models\AssessmentResponse;

class ExemptionService
{
    public function processExemptions(User $user, Course $course, SunnahAssessment $assessment): ?CourseExemption
    {
        // Get all responses where user already practices the Sunnah
        $exemptedModules = [];

        $responses = AssessmentResponse::where('user_id', $user->id)
            ->where('sunnah_assessment_id', $assessment->id)
            ->where('already_practicing', true)
            ->get();

        // Map responses to modules (this would need to be configured in assessment questions)
        // For now, we'll use a simple mapping based on question keys
        $course->load('modules');

        foreach ($responses as $response) {
            // Check if question has module mapping
            $question = collect($assessment->questions)->firstWhere('key', $response->question_key);

            if ($question && isset($question['module_id'])) {
                $exemptedModules[] = $question['module_id'];
            }
        }

        if (empty($exemptedModules)) {
            return null;
        }

        // Create or update exemption
        $exemption = CourseExemption::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'sunnah_assessment_id' => $assessment->id,
                'exempted_modules' => array_unique($exemptedModules),
                'reason' => 'User already practices these Sunnah based on assessment',
            ]
        );

        return $exemption;
    }

    public function isModuleExempted(User $user, Course $course, int $moduleId): bool
    {
        $exemption = CourseExemption::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$exemption || !$exemption->exempted_modules) {
            return false;
        }

        return in_array($moduleId, $exemption->exempted_modules);
    }
}
