<?php

namespace Database\Seeders;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo seeder for Phase 1 content gating features.
 *
 * Creates:
 * - beginner_male (no bay'ah)
 * - expert_female (bay'ah)
 * - admin user
 * - Sample courses with various rules
 *
 * Usage:
 * php artisan db:seed --class=LmsDemoSeeder
 * OR
 * php artisan lms:seed-demo (if command created)
 */
class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Only run in local/dev environments
        if (!app()->environment('local') && !config('app.debug')) {
            $this->command->warn('LmsDemoSeeder should only run in local/dev environments.');
            return;
        }

        // Create demo users
        $beginnerMale = User::updateOrCreate(
            ['email' => 'beginner_male@demo.com'],
            [
                'name' => 'Ahmad Beginner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'gender' => 'male',
                'level' => 'beginner',
                'has_bayah' => false,
                'is_admin' => false,
            ]
        );

        $expertFemale = User::updateOrCreate(
            ['email' => 'expert_female@demo.com'],
            [
                'name' => 'Fatima Expert',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'gender' => 'female',
                'level' => 'expert',
                'has_bayah' => true,
                'is_admin' => false,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'gender' => 'male',
                'level' => 'expert',
                'has_bayah' => true,
                'is_admin' => true,
            ]
        );

        // Course A: Free (no rules)
        $courseA = Course::updateOrCreate(
            ['slug' => 'demo-course-free'],
            [
                'title' => 'Free Access Course',
                'description' => 'This course has no access restrictions. All users can access it.',
                'instructor' => 'Demo Instructor',
                'level' => 'Beginner',
                'sort_order' => 1,
            ]
        );

        $moduleA1 = Module::updateOrCreate(
            ['slug' => 'demo-free-module-1', 'course_id' => $courseA->id],
            [
                'title' => 'Introduction Module',
                'sort_order' => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['slug' => 'demo-free-lesson-1', 'module_id' => $moduleA1->id],
            [
                'title' => 'Welcome Lesson',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_seconds' => 300,
                'sort_order' => 1,
            ]
        );

        // Course B: Bay'ah required
        $courseB = Course::updateOrCreate(
            ['slug' => 'demo-course-bayah'],
            [
                'title' => 'Bay\'ah Required Course',
                'description' => 'This course requires bay\'ah. Only users with bay\'ah can access.',
                'instructor' => 'Demo Instructor',
                'level' => 'Intermediate',
                'sort_order' => 2,
            ]
        );

        ContentRule::updateOrCreate(
            [
                'ruleable_type' => Course::class,
                'ruleable_id' => $courseB->id,
            ],
            [
                'requires_bayah' => true,
                'min_level' => null,
                'gender' => null,
            ]
        );

        $moduleB1 = Module::updateOrCreate(
            ['slug' => 'demo-bayah-module-1', 'course_id' => $courseB->id],
            [
                'title' => 'Bay\'ah Module',
                'sort_order' => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['slug' => 'demo-bayah-lesson-1', 'module_id' => $moduleB1->id],
            [
                'title' => 'Bay\'ah Lesson',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_seconds' => 300,
                'sort_order' => 1,
            ]
        );

        // Course C: Expert only
        $courseC = Course::updateOrCreate(
            ['slug' => 'demo-course-expert'],
            [
                'title' => 'Expert Level Course',
                'description' => 'This course requires Expert level. Only expert users can access.',
                'instructor' => 'Demo Instructor',
                'level' => 'Expert',
                'sort_order' => 3,
            ]
        );

        ContentRule::updateOrCreate(
            [
                'ruleable_type' => Course::class,
                'ruleable_id' => $courseC->id,
            ],
            [
                'requires_bayah' => false,
                'min_level' => 'expert',
                'gender' => null,
            ]
        );

        $moduleC1 = Module::updateOrCreate(
            ['slug' => 'demo-expert-module-1', 'course_id' => $courseC->id],
            [
                'title' => 'Expert Module',
                'sort_order' => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['slug' => 'demo-expert-lesson-1', 'module_id' => $moduleC1->id],
            [
                'title' => 'Expert Lesson',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_seconds' => 300,
                'sort_order' => 1,
            ]
        );

        // Course D: Female only
        $courseD = Course::updateOrCreate(
            ['slug' => 'demo-course-female'],
            [
                'title' => 'Sisters Only Course',
                'description' => 'This course is available for sisters only.',
                'instructor' => 'Demo Instructor',
                'level' => 'Beginner',
                'sort_order' => 4,
            ]
        );

        ContentRule::updateOrCreate(
            [
                'ruleable_type' => Course::class,
                'ruleable_id' => $courseD->id,
            ],
            [
                'requires_bayah' => false,
                'min_level' => null,
                'gender' => 'female',
            ]
        );

        $moduleD1 = Module::updateOrCreate(
            ['slug' => 'demo-female-module-1', 'course_id' => $courseD->id],
            [
                'title' => 'Sisters Module',
                'sort_order' => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['slug' => 'demo-female-lesson-1', 'module_id' => $moduleD1->id],
            [
                'title' => 'Sisters Lesson',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_seconds' => 300,
                'sort_order' => 1,
            ]
        );

        // Course E: Conflicting gender rules (for testing)
        $courseE = Course::updateOrCreate(
            ['slug' => 'demo-course-conflicting'],
            [
                'title' => 'Conflicting Rules Course',
                'description' => 'This course has conflicting gender rules (male course, female module) for testing purposes.',
                'instructor' => 'Demo Instructor',
                'level' => 'Intermediate',
                'sort_order' => 5,
            ]
        );

        ContentRule::updateOrCreate(
            [
                'ruleable_type' => Course::class,
                'ruleable_id' => $courseE->id,
            ],
            [
                'requires_bayah' => false,
                'min_level' => null,
                'gender' => 'male',
            ]
        );

        $moduleE1 = Module::updateOrCreate(
            ['slug' => 'demo-conflicting-module-1', 'course_id' => $courseE->id],
            [
                'title' => 'Conflicting Module',
                'sort_order' => 1,
            ]
        );

        ContentRule::updateOrCreate(
            [
                'ruleable_type' => Module::class,
                'ruleable_id' => $moduleE1->id,
            ],
            [
                'requires_bayah' => false,
                'min_level' => null,
                'gender' => 'female',
            ]
        );

        Lesson::updateOrCreate(
            ['slug' => 'demo-conflicting-lesson-1', 'module_id' => $moduleE1->id],
            [
                'title' => 'Conflicting Lesson',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_seconds' => 300,
                'sort_order' => 1,
            ]
        );

        // Auto-enroll demo users into free course
        Enrollment::updateOrCreate(
            [
                'user_id' => $beginnerMale->id,
                'course_id' => $courseA->id,
            ],
            ['enrolled_at' => now()]
        );

        Enrollment::updateOrCreate(
            [
                'user_id' => $expertFemale->id,
                'course_id' => $courseA->id,
            ],
            ['enrolled_at' => now()]
        );

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Users created:');
        $this->command->info('  - beginner_male@demo.com (password: password)');
        $this->command->info('  - expert_female@demo.com (password: password)');
        $this->command->info('  - admin@demo.com (password: password)');
    }
}
