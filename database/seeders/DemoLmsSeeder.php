<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranscriptSegment;
use App\Models\Module;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;

class DemoLmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        $umar = User::updateOrCreate(
            ['email' => 'umar@example.com'],
            [
                'name' => 'Umar Student',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );

        $fatima = User::updateOrCreate(
            ['email' => 'fatima@example.com'],
            [
                'name' => 'Fatima Student',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );

        // Course
        $course = Course::updateOrCreate(
            ['slug' => 'demo-course'],
            [
                'title' => 'Demo Course',
                'description' => 'A deterministic demo course used for automated tests.',
                'instructor' => 'Demo Instructor',
                'level' => 'Beginner',
                'sort_order' => 1,
            ]
        );

        // Modules
        $moduleIntro = Module::updateOrCreate(
            ['slug' => 'demo-intro', 'course_id' => $course->id],
            [
                'title' => 'Introduction',
                'sort_order' => 1,
            ]
        );

        $moduleDeepDive = Module::updateOrCreate(
            ['slug' => 'demo-deep-dive', 'course_id' => $course->id],
            [
                'title' => 'Deep Dive',
                'sort_order' => 2,
            ]
        );

        // Lessons
        $lesson1 = Lesson::updateOrCreate(
            ['slug' => 'demo-lesson-1', 'module_id' => $moduleIntro->id],
            [
                'title' => 'Demo Lesson 1',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'DEMO_VIDEO_ID_1',
                'duration_seconds' => 600,
                'sort_order' => 1,
                'is_free_preview' => true,
            ]
        );

        $lesson2 = Lesson::updateOrCreate(
            ['slug' => 'demo-lesson-2', 'module_id' => $moduleIntro->id],
            [
                'title' => 'Demo Lesson 2',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'DEMO_VIDEO_ID_2',
                'duration_seconds' => 900,
                'sort_order' => 2,
                'is_free_preview' => false,
            ]
        );

        $lesson3 = Lesson::updateOrCreate(
            ['slug' => 'demo-lesson-3', 'module_id' => $moduleDeepDive->id],
            [
                'title' => 'Demo Lesson 3',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'DEMO_VIDEO_ID_3',
                'duration_seconds' => 1200,
                'sort_order' => 1,
                'is_free_preview' => false,
            ]
        );

        // Transcript segments for lesson1
        foreach ([0, 30, 60] as $index => $start) {
            $end = $start + 30;

            LessonTranscriptSegment::updateOrCreate(
                [
                    'lesson_id' => $lesson1->id,
                    'start_seconds' => $start,
                ],
                [
                    'end_seconds' => $end,
                    'text' => "Demo transcript segment " . ($index + 1) . " from {$start}s to {$end}s.",
                ]
            );
        }

        // Enroll Umar in the demo course so he can complete lessons
        if (isset($umar, $course)) {
            Enrollment::updateOrCreate(
                [
                    'user_id' => $umar->id,
                    'course_id' => $course->id,
                ],
                [
                    'enrolled_at' => now(),
                ]
            );
        }
    }
}

