<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@sunnah-lms.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $student1 = User::create([
            'name' => 'Umar Abdullah',
            'email' => 'umar@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $student2 = User::create([
            'name' => 'Fatima Hassan',
            'email' => 'fatima@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // Course 1: Purification of the Heart
        $course1 = Course::create([
            'title' => 'Purification of the Heart',
            'slug' => 'purification-of-the-heart',
            'description' => 'A comprehensive guide to cleansing the heart from spiritual diseases.',
            'instructor' => 'Shaykh Ahmad',
            'level' => 'Beginner',
            'sort_order' => 1,
        ]);

        $module1_1 = Module::create([
            'course_id' => $course1->id,
            'title' => 'Introduction to Tazkiyah',
            'slug' => 'introduction-to-tazkiyah',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'module_id' => $module1_1->id,
            'title' => 'What is a Sound Heart?',
            'slug' => 'what-is-a-sound-heart',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 900, // 15 minutes
            'sort_order' => 1,
            'is_free_preview' => true,
        ]);

        Lesson::create([
            'module_id' => $module1_1->id,
            'title' => 'The Importance of Intention',
            'slug' => 'importance-of-intention',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 1200, // 20 minutes
            'sort_order' => 2,
            'is_free_preview' => false,
        ]);

        $module1_2 = Module::create([
            'course_id' => $course1->id,
            'title' => 'Diseases of the Heart',
            'slug' => 'diseases-of-the-heart',
            'sort_order' => 2,
        ]);

        Lesson::create([
            'module_id' => $module1_2->id,
            'title' => 'Understanding Envy',
            'slug' => 'understanding-envy',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 1500, // 25 minutes
            'sort_order' => 1,
        ]);

        Lesson::create([
            'module_id' => $module1_2->id,
            'title' => 'The Cure for Arrogance',
            'slug' => 'cure-for-arrogance',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 1800, // 30 minutes
            'sort_order' => 2,
        ]);

        // Course 2: Fiqh of Prayer
        $course2 = Course::create([
            'title' => 'Fiqh of Prayer',
            'slug' => 'fiqh-of-prayer',
            'description' => 'Learn the rulings of the five daily prayers.',
            'instructor' => 'Ustadha Fatima',
            'level' => 'Intermediate',
            'sort_order' => 2,
        ]);

        $module2_1 = Module::create([
            'course_id' => $course2->id,
            'title' => 'Foundations of Salah',
            'slug' => 'foundations-of-salah',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'module_id' => $module2_1->id,
            'title' => 'The Conditions of Prayer',
            'slug' => 'conditions-of-prayer',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 1080, // 18 minutes
            'sort_order' => 1,
            'is_free_preview' => true,
        ]);

        Lesson::create([
            'module_id' => $module2_1->id,
            'title' => 'The Pillars of Prayer',
            'slug' => 'pillars-of-prayer',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 1320, // 22 minutes
            'sort_order' => 2,
        ]);

        // Course 3: Understanding the Quran
        $course3 = Course::create([
            'title' => 'Understanding the Quran',
            'slug' => 'understanding-the-quran',
            'description' => 'Deep dive into selected Surahs with classical tafsir.',
            'instructor' => 'Dr. Kareem',
            'level' => 'Advanced',
            'sort_order' => 3,
        ]);

        $module3_1 = Module::create([
            'course_id' => $course3->id,
            'title' => 'Surah Al-Fatiha',
            'slug' => 'surah-al-fatiha',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'module_id' => $module3_1->id,
            'title' => 'Introduction to Al-Fatiha',
            'slug' => 'intro-al-fatiha',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 1800, // 30 minutes
            'sort_order' => 1,
            'is_free_preview' => true,
        ]);

        Lesson::create([
            'module_id' => $module3_1->id,
            'title' => 'Verse by Verse Analysis',
            'slug' => 'fatiha-verse-analysis',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 2700, // 45 minutes
            'sort_order' => 2,
        ]);

        // Create Enrollments
        Enrollment::create([
            'user_id' => $student1->id,
            'course_id' => $course1->id,
            'enrolled_at' => now()->subDays(10),
        ]);

        Enrollment::create([
            'user_id' => $student1->id,
            'course_id' => $course2->id,
            'enrolled_at' => now()->subDays(5),
        ]);

        Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $course1->id,
            'enrolled_at' => now()->subDays(7),
        ]);

        Enrollment::create([
            'user_id' => $student2->id,
            'course_id' => $course3->id,
            'enrolled_at' => now()->subDays(3),
        ]);

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('  Admin: admin@sunnah-lms.com / password');
        $this->command->info('  Student 1: umar@example.com / password');
        $this->command->info('  Student 2: fatima@example.com / password');
        $this->command->info('');
        $this->command->info('Database contains:');
        $this->command->info('  - 3 Courses with multiple modules and lessons');
        $this->command->info('  - Students enrolled in various courses');
        $this->command->info('  - All lessons use YouTube video ID: dQw4w9WgXcQ');
    }
}
