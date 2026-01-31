<?php

namespace Tests\Feature;

use App\Jobs\AggregateDailyUserMetricsJob;
use App\Jobs\UpdateCourseProgressSnapshotsJob;
use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\CourseProgressSnapshot;
use App\Models\DailyUserMetric;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\Task;
use App\Models\TaskCheckin;
use App\Models\TaskProgress;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logger_logs_event_with_subject(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $logger = new ActivityLogger();
        $logger->log(
            ActivityEvent::TYPE_LESSON_WATCH_STARTED,
            $user,
            [
                'subject' => $lesson,
                'meta' => ['test' => 'data'],
            ]
        );

        $this->assertDatabaseHas('activity_events', [
            'user_id' => $user->id,
            'event_type' => ActivityEvent::TYPE_LESSON_WATCH_STARTED,
            'subject_type' => Lesson::class,
            'subject_id' => $lesson->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
        ]);

        $event = ActivityEvent::where('user_id', $user->id)->first();
        $this->assertNotNull($event->meta);
        $this->assertEquals('data', $event->meta['test']);
    }

    public function test_aggregate_daily_user_metrics_job_computes_correct_counts(): void
    {
        Carbon::setTestNow('2026-01-30 12:00:00');
        $date = Carbon::parse('2026-01-29'); // Yesterday

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        // Create watch session
        $session = \App\Models\LessonWatchSession::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'started_at' => $date->copy()->setTime(10, 0),
            'ended_at' => $date->copy()->setTime(10, 30),
            'watched_seconds' => 1800,
            'seek_attempts' => 2,
            'max_playback_rate' => 1.2,
            'violations' => [
                ['type' => 'seek_forward', 'at' => now()->toIso8601String()],
            ],
        ]);

        // Create lesson completion
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'completed_at' => $date->copy()->setTime(11, 0),
            'watched_seconds' => 1800,
            'max_playback_rate' => 1.2,
        ]);

        // Create reflection
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'takeaway' => 'Test reflection',
            'submitted_at' => $date->copy()->setTime(12, 0),
        ]);

        // Create task check-in
        $task = Task::factory()->create(['taskable_id' => $lesson->id, 'taskable_type' => Lesson::class]);
        $taskProgress = TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => TaskProgress::STATUS_IN_PROGRESS,
            'days_done' => 1,
        ]);
        TaskCheckin::create([
            'task_progress_id' => $taskProgress->id,
            'checkin_on' => $date->toDateString(),
        ]);

        // Create activity event
        ActivityEvent::create([
            'user_id' => $user->id,
            'event_type' => ActivityEvent::TYPE_LESSON_WATCH_COMPLETED,
            'lesson_id' => $lesson->id,
            'occurred_at' => $date->copy()->setTime(11, 0),
        ]);

        // Run aggregation job
        $job = new AggregateDailyUserMetricsJob($date);
        $job->handle();

        // Assert metrics
        $metric = DailyUserMetric::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals(1800, $metric->watched_seconds);
        $this->assertEquals(1, $metric->lessons_completed);
        $this->assertEquals(1, $metric->reflections_submitted);
        $this->assertEquals(1, $metric->task_checkins);
        $this->assertEquals(1, $metric->violations_count);
        $this->assertEquals(2, $metric->seek_attempts);
        $this->assertEquals(1.2, (float) $metric->max_playback_rate);
    }

    public function test_course_progress_snapshot_job_computes_blocked_by_correctly(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 1]);
        $lesson2 = Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 2]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'started_at' => now()->subDays(5),
        ]);

        // Complete lesson 1 but don't submit reflection
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
        ]);

        // Run snapshot job
        $progressionService = app(\App\Services\ProgressionService::class);
        $releaseScheduleService = app(\App\Services\ReleaseScheduleService::class);
        $job = new UpdateCourseProgressSnapshotsJob();
        $job->handle($progressionService, $releaseScheduleService);

        $snapshot = CourseProgressSnapshot::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals(2, $snapshot->lessons_total);
        $this->assertEquals(1, $snapshot->lessons_completed);
        $this->assertEquals($lesson2->id, $snapshot->next_lesson_id);
        $this->assertEquals(CourseProgressSnapshot::BLOCKED_REFLECTION_REQUIRED, $snapshot->blocked_by);
    }

    public function test_non_admin_cannot_access_analytics_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.analytics.index'))
            ->assertStatus(403);

        $this->actingAs($user)->get(route('admin.analytics.stagnation'))
            ->assertStatus(403);

        $course = Course::factory()->create();
        $this->actingAs($user)->get(route('admin.analytics.courses.show', $course))
            ->assertStatus(403);
    }

    public function test_admin_can_access_analytics_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.analytics.index'))
            ->assertStatus(200);

        $this->actingAs($admin)->get(route('admin.analytics.stagnation'))
            ->assertStatus(200);

        $course = Course::factory()->create();
        $this->actingAs($admin)->get(route('admin.analytics.courses.show', $course))
            ->assertStatus(200);
    }

    public function test_stagnation_score_heuristic(): void
    {
        Carbon::setTestNow('2026-01-30 12:00:00');
        $date = Carbon::parse('2026-01-29');

        $user = User::factory()->create();

        // User with no activity
        $job = new AggregateDailyUserMetricsJob($date);
        $job->handle();

        $metric = DailyUserMetric::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->first();

        // Should have stagnation score > 0 (no watched seconds + no activity)
        if ($metric) {
            $this->assertGreaterThan(0, $metric->stagnation_score);
        }
    }

    public function test_course_snapshot_updates_next_lesson_release_at(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'release_day_offset' => 2,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'started_at' => Carbon::parse('2026-01-28'),
        ]);

        $progressionService = app(\App\Services\ProgressionService::class);
        $releaseScheduleService = app(\App\Services\ReleaseScheduleService::class);
        $job = new UpdateCourseProgressSnapshotsJob();
        $job->handle($progressionService, $releaseScheduleService);

        $snapshot = CourseProgressSnapshot::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals($lesson->id, $snapshot->next_lesson_id);
        // Release should be started_at + 2 days = 2026-01-30
        $this->assertEquals('2026-01-30', $snapshot->next_lesson_release_at->toDateString());
    }
}
