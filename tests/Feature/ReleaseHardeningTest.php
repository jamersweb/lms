<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\ProgressionService;
use App\Services\WatchTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReleaseHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledLesson(): array
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create(['duration_seconds' => 100]);
        $user->enrollments()->create(['course_id' => $lesson->module->course_id, 'enrolled_at' => now()]);
        return [$user, $lesson];
    }

    public function test_resume_position_cannot_forge_verified_watch_time(): void
    {
        [$user, $lesson] = $this->enrolledLesson();
        $this->actingAs($user)->postJson(route('lesson-progress.update', $lesson), [
            'duration_seconds' => 100, 'last_position_seconds' => 100, 'percent_complete' => 100,
        ])->assertOk();
        $this->assertDatabaseHas('lesson_progress', ['user_id' => $user->id, 'lesson_id' => $lesson->id, 'watched_seconds' => 0]);
        $this->postJson(route('lessons.complete', $lesson))->assertUnprocessable();
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_student_cannot_set_shared_video_duration(): void
    {
        [$user, $lesson] = $this->enrolledLesson();
        $lesson->update(['duration_seconds' => null, 'video_duration_seconds' => null]);
        $this->actingAs($user)->postJson(route('lessons.duration', $lesson), ['duration_seconds' => 1])->assertOk();
        $this->assertNull($lesson->fresh()->video_duration_seconds);
    }

    public function test_heartbeat_credits_elapsed_time_and_rejects_replay(): void
    {
        [$user, $lesson] = $this->enrolledLesson();
        $this->freezeTime();
        $service = app(WatchTrackingService::class);
        $session = $service->startSession($user, $lesson);
        $this->travel(15)->seconds();
        $payload = ['session_id' => $session->id, 'position_seconds' => 15, 'playback_rate' => 1, 'played_delta_seconds' => 15];
        $service->recordHeartbeat($user, $lesson, $payload);
        $service->recordHeartbeat($user, $lesson, $payload);
        $progress = LessonProgress::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(15, $progress->watched_seconds);
        $this->assertEquals(0, $progress->seek_attempts);
    }

    public function test_complete_course_awards_once_after_required_reflection(): void
    {
        [$user, $lesson] = $this->enrolledLesson();
        $lesson->update(['requires_reflection' => true, 'reflection_requires_approval' => true]);
        LessonProgress::factory()->create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'completed_at' => now(), 'is_completed' => true]);
        $service = app(CertificateService::class);
        $course = $lesson->module->course;
        $this->assertNull($service->awardCompletedCourse($user, $course));
        $reflection = $lesson->reflections()->create(['user_id' => $user->id, 'takeaway' => 'A meaningful reflection on this course.', 'submitted_at' => now(), 'review_status' => 'pending']);
        $this->assertNull($service->awardCompletedCourse($user, $course));
        $reflection->update(['review_status' => 'reviewed']);
        $this->assertNotNull($service->awardCompletedCourse($user, $course));
        $service->awardCompletedCourse($user, $course);
        $this->assertDatabaseCount('certificates', 1);
        $this->assertEquals(1, $user->pointsEvents()->where('event_type', 'course_completed')->count());
    }

    public function test_disabling_sequence_does_not_bypass_drip_release(): void
    {
        [$user, $lesson] = $this->enrolledLesson();
        $lesson->update(['release_at' => now()->addDay()]);
        config(['progression.sequential_lessons' => false]);
        $this->assertFalse(app(ProgressionService::class)->canAccessLesson($user, $lesson)->allowed);
    }

    public function test_scheduler_requires_header_token_and_hides_exceptions(): void
    {
        config(['app.scheduler_token' => 'test-secret']);
        $this->postJson('/scheduler/run?token=test-secret')->assertUnauthorized();
        $this->get('/scheduler/run?token=test-secret')->assertStatus(405);
        $this->travelTo(now()->setTime(20, 0));
        Artisan::shouldReceive('call')->once()->andThrow(new \RuntimeException('private-provider-secret'));
        $this->withToken('test-secret')->postJson('/scheduler/run')->assertStatus(500)->assertContent('Scheduler failed');
        $this->assertTrue(Cache::lock('scheduled-triggers', 60)->get());
    }

    public function test_admin_edit_preserves_translations_and_has_checklist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create(['title_en_roman' => 'Roman course title']);
        $this->actingAs($admin)->get(route('admin.courses.edit', $course))->assertInertia(fn ($page) => $page
            ->where('course.title_en_roman', 'Roman course title')->where('readiness.ready', false)->has('readiness.issues', 1));
    }
}
