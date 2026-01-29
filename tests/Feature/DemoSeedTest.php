<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranscriptSegment;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_runs_db_seed_without_errors(): void
    {
        $this->artisan('db:seed')
            ->assertExitCode(0);
    }

    /** @test */
    public function demo_users_are_seeded(): void
    {
        $this->artisan('db:seed');

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'umar@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'fatima@example.com']);
    }

    /** @test */
    public function demo_course_modules_and_lessons_exist(): void
    {
        $this->artisan('db:seed');

        $course = Course::where('slug', 'demo-course')->first();
        $this->assertNotNull($course);

        $modules = Module::where('course_id', $course->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $modules);

        $lessons = Lesson::whereIn('module_id', $modules->pluck('id'))->get();
        $this->assertCount(3, $lessons);
    }

    /** @test */
    public function transcript_segments_exist_for_first_demo_lesson(): void
    {
        $this->artisan('db:seed');

        $lesson = Lesson::where('slug', 'demo-lesson-1')->first();
        $this->assertNotNull($lesson);

        foreach ([0, 30, 60] as $start) {
            $this->assertDatabaseHas('lesson_transcript_segments', [
                'lesson_id' => $lesson->id,
                'start_seconds' => $start,
            ]);
        }
    }
}

