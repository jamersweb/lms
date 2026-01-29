<?php

namespace Tests\Feature;

use App\Models\Broadcast;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\StagnationAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnalyticsStagnationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stagnation_scan_flags_user_with_old_activity(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create([
            'module_id' => \App\Models\Module::factory()->create([
                'course_id' => $course->id,
            ])->id,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subDays(10),
        ]);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'last_heartbeat_at' => now()->subDays(7),
        ]);

        Notification::fake();

        $this->artisan('lms:stagnation-scan', ['--days' => 3])
            ->assertExitCode(0);

        $this->assertDatabaseHas('stagnation_alerts', [
            'user_id' => $user->id,
            'days' => 3,
        ]);
    }

    public function test_broadcast_reaches_only_matching_segment(): void
    {
        Notification::fake();

        $maleBeginner = User::factory()->create([
            'gender' => 'male',
            'has_bayah' => true,
            'level' => 'beginner',
        ]);

        $femaleBeginner = User::factory()->create([
            'gender' => 'female',
            'has_bayah' => true,
            'level' => 'beginner',
        ]);

        $maleExpertNoBayah = User::factory()->create([
            'gender' => 'male',
            'has_bayah' => false,
            'level' => 'expert',
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.broadcasts.store'), [
            'subject' => 'Beginner brothers with bayah',
            'body' => 'This is a targeted message.',
            'gender' => 'male',
            'has_bayah' => true,
            'level' => 'beginner',
        ])->assertRedirect(route('admin.broadcasts.index'));

        // Only the matching user should receive the notification
        Notification::assertSentTo(
            [$maleBeginner],
            \App\Notifications\BroadcastNotification::class
        );

        Notification::assertNotSentTo(
            [$femaleBeginner, $maleExpertNoBayah],
            \App\Notifications\BroadcastNotification::class
        );

        $this->assertDatabaseHas('broadcasts', [
            'subject' => 'Beginner brothers with bayah',
        ]);
    }
}
