<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use App\Services\JourneyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DripUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_at_offsets_on_enrollment(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = collect([
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 1]),
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 2]),
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 3]),
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        JourneyService::ensureProgressRecords($user, $course);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[0]->id,
            'available_at' => '2026-01-01 00:00:00',
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[1]->id,
            'available_at' => '2026-01-02 00:00:00',
        ]);
    }

    public function test_drip_command_unlocks_when_available_at_passed(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = collect([
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 1]),
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 2]),
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        // initialize records and drip schedule
        JourneyService::ensureProgressRecords($user, $course);

        // mark first lesson completed so second can be unlocked once time allows
        LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessons[0]->id)
            ->update([
                'is_completed' => true,
                'completed_at' => now(),
                'status' => 'completed',
            ]);

        // move time forward past second lesson's available_at
        Carbon::setTestNow('2026-01-03 03:00:00');

        $this->artisan('lms:drip-unlock')->assertExitCode(0);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[1]->id,
            'status' => 'available',
        ]);
    }
}

