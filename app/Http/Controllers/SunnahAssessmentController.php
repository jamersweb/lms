<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SunnahAssessment;
use App\Models\AssessmentResponse;
use App\Models\CourseExemption;
use App\Services\ExemptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SunnahAssessmentController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $user = $request->user();

        // Check if user already has an exemption
        $exemption = CourseExemption::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($exemption) {
            return redirect()->route('courses.show', $course)
                ->with('info', 'You have already completed the assessment for this course.');
        }

        $assessment = SunnahAssessment::where('course_id', $course->id)
            ->where('is_active', true)
            ->first();

        if (!$assessment) {
            return redirect()->route('courses.show', $course)
                ->with('info', 'No assessment available for this course.');
        }

        // Get existing responses
        $responses = AssessmentResponse::where('user_id', $user->id)
            ->where('sunnah_assessment_id', $assessment->id)
            ->get()
            ->keyBy('question_key');

        return Inertia::render('Assessments/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'questions' => $assessment->questions,
            ],
            'responses' => $responses,
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $user = $request->user();

        $assessment = SunnahAssessment::where('course_id', $course->id)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'responses' => ['required', 'array'],
            'responses.*.question_key' => ['required', 'string'],
            'responses.*.already_practicing' => ['required', 'boolean'],
            'responses.*.notes' => ['nullable', 'string'],
        ]);

        // Save responses
        foreach ($validated['responses'] as $responseData) {
            AssessmentResponse::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'sunnah_assessment_id' => $assessment->id,
                    'question_key' => $responseData['question_key'],
                ],
                [
                    'already_practicing' => $responseData['already_practicing'],
                    'notes' => $responseData['notes'] ?? null,
                ]
            );
        }

        // Process exemptions
        $exemptionService = new ExemptionService();
        $exemption = $exemptionService->processExemptions($user, $course, $assessment);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Assessment completed! ' .
                ($exemption ? 'Some modules have been exempted based on your responses.' : ''));
    }
}
