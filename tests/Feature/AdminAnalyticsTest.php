<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonWatchSession;
use App\Models\Module;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-01-28 12:00:00');
    }

    public function test_non_admin_forbidden(): void
    {
        $this->seed();
        $umar = $this->loginAsStudentUmar();

        $this->get(route('admin.analytics.index'))
            ->assertStatus(403);
    }

    public function test_admin_sees_correct_counts(): void
    {
        $this->seed();

        $admin = $this->loginAsAdmin();
        $umar = User::where('email', 'umar@example.com')->firstOrFail();
        $fatima = User::where('email', 'fatima@example.com')->firstOrFail();

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_duration_seconds' => 600,
        ]);

        // Enroll users
        Enrollment::create([
            'user_id' => $umar->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subDays(10),
        ]);

        Enrollment::create([
            'user_id' => $fatima->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subDays(10),
        ]);

        // Umar: active (recent heartbeat) + speeding (seek + low watch)
        LessonProgress::create([
            'user_id' => $umar->id,
            'lesson_id' => $lesson->id,
            'last_heartbeat_at' => now()->subMinutes(2),
            'time_watched_seconds' => 100,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => true,
            'verified_completion' => true,
        ]);

        LessonWatchSession::create([
            'user_id' => $umar->id,
            'lesson_id' => $lesson->id,
            'started_at' => now()->subMinutes(5),
            'ended_at' => now()->subMinutes(1),
            'watch_time_seconds' => 100,
            'last_time_seconds' => 100,
            'seek_events_count' => 3,
            'max_playback_rate' => 1.0,
            'is_valid' => true,
        ]);

        // Fatima: stalled (no activity for >3 days)
        LessonProgress::create([
            'user_id' => $fatima->id,
            'lesson_id' => $lesson->id,
            'last_heartbeat_at' => now()->subDays(5),
            'time_watched_seconds' => 50,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['days' => 3]));

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Index')
            ->where('stalledDays', 3)
            ->has('activeUsers', 1)
            ->has('stalledUsers', 1)
            ->has('speeding', 1)
        );
    }
}

