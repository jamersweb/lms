<?php

namespace Tests\Feature;

use App\Jobs\SendDripRemindersJob;
use App\Jobs\SendStagnationRemindersJob;
use App\Jobs\SendTaskRemindersJob;
use App\Models\AppSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\NotificationLog;
use App\Models\Task;
use App\Models\TaskProgress;
use App\Models\User;
use App\Notifications\NextLessonAvailableNotification;
use App\Notifications\StagnationReminderNotification;
use App\Notifications\TaskCheckInReminderNotification;
use App\Services\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;

class Phase4NotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function drip_reminder_job_sends_notification_when_next_lesson_released()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_reminders_opt_in' => true,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now()->subDays(2), // Enrolled 2 days ago
        ]);

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_day_offset' => 1, // Released 1 day after enrollment
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        // Enable notifications
        app(AppSettings::class)->setNotificationSettings([
            'enabled' => true,
            'drip' => ['enabled' => true, 'send_hour' => now()->hour],
        ]);

        // Run job
        $job = new SendDripRemindersJob();
        $job->handle();

        // Assert notification sent
        Notification::assertSentTo($user, NextLessonAvailableNotification::class);
    }

    /** @test */
    public function drip_reminder_respects_opt_out()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_reminders_opt_in' => false,
            'whatsapp_opt_in' => false,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now()->subDays(2),
        ]);

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_day_offset' => 1,
        ]);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        app(AppSettings::class)->setNotificationSettings([
            'enabled' => true,
            'drip' => ['enabled' => true, 'send_hour' => now()->hour],
        ]);

        $job = new SendDripRemindersJob();
        $job->handle();

        // Should not send notification (user opted out)
        Notification::assertNothingSent();
    }

    /** @test */
    public function task_reminder_job_sends_notification_when_checkin_missing()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_reminders_opt_in' => true,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $task = Task::create([
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson->id,
            'title' => 'Test Task',
            'required_days' => 7,
            'unlock_next_lesson' => true,
        ]);

        $taskProgress = TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => TaskProgress::STATUS_IN_PROGRESS,
            'days_done' => 2,
            'last_checkin_on' => Carbon::yesterday(), // Last checked in yesterday
        ]);

        app(AppSettings::class)->setNotificationSettings([
            'enabled' => true,
            'task' => ['enabled' => true, 'send_hour' => now()->hour],
        ]);

        $job = new SendTaskRemindersJob();
        $job->handle();

        Notification::assertSentTo($user, TaskCheckInReminderNotification::class);
    }

    /** @test */
    public function stagnation_reminder_job_sends_notification_when_inactive()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_reminders_opt_in' => true,
            'last_active_at' => now()->subDays(5), // Inactive for 5 days
        ]);

        $course = Course::factory()->create();
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subDays(10),
            'started_at' => now()->subDays(10),
        ]);

        app(AppSettings::class)->setNotificationSettings([
            'enabled' => true,
            'stagnation' => [
                'enabled' => true,
                'inactive_days' => 3,
                'send_hour' => now()->hour,
            ],
        ]);

        $job = new SendStagnationRemindersJob();
        $job->handle();

        Notification::assertSentTo($user, StagnationReminderNotification::class);
    }

    /** @test */
    public function notification_logs_prevent_duplicate_notifications()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_reminders_opt_in' => true,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now()->subDays(2),
        ]);

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_day_offset' => 1,
        ]);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        app(AppSettings::class)->setNotificationSettings([
            'enabled' => true,
            'drip' => ['enabled' => true, 'send_hour' => now()->hour],
        ]);

        // Run job first time - should send notification
        Notification::fake();
        $job = new SendDripRemindersJob();
        $job->handle();

        Notification::assertSentTo($user, NextLessonAvailableNotification::class);

        // Verify log entry was created
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'type' => 'drip',
        ]);

        $log = NotificationLog::where('user_id', $user->id)
            ->where('type', 'drip')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals(now()->toDateString(), $log->sent_on->toDateString());

        // Clear notifications and run again - should not send duplicate
        Notification::fake();

        $job2 = new SendDripRemindersJob();
        $job2->handle();

        // Should not send duplicate notification (log entry prevents it)
        Notification::assertNothingSent();

        // Verify only one log entry exists (updateOrCreate should update, not create duplicate)
        $this->assertEquals(1, NotificationLog::where('user_id', $user->id)
            ->where('type', 'drip')
            ->whereDate('sent_on', now()->toDateString())
            ->count());
    }

    /** @test */
    public function whatsapp_channel_calls_provider_with_correct_data()
    {
        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['module_id' => Module::factory()->create(['course_id' => $course->id])->id]);

        // Mock the provider
        $mockProvider = \Mockery::mock(\App\Services\WhatsApp\WhatsAppProviderInterface::class);
        $mockProvider->shouldReceive('sendMessage')
            ->once()
            ->with('+1234567890', \Mockery::type('string'));

        $this->app->instance(\App\Services\WhatsApp\LogWhatsAppProvider::class, $mockProvider);

        $notification = new NextLessonAvailableNotification($course, $lesson);
        $user->notify($notification);
    }

    /** @test */
    public function app_settings_service_stores_and_retrieves_settings()
    {
        $settings = app(AppSettings::class);

        $settings->set('test.key', 'test_value');
        $this->assertEquals('test_value', $settings->get('test.key'));

        $settings->set('test.array', ['a' => 1, 'b' => 2]);
        $this->assertEquals(['a' => 1, 'b' => 2], $settings->get('test.array'));
    }

    /** @test */
    public function admin_can_update_notification_settings()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin);

        $response = $this->patch(route('admin.notifications.settings.update'), [
            'enabled' => true,
            'channels' => [
                'email' => true,
                'whatsapp' => true,
            ],
            'drip' => [
                'enabled' => true,
                'send_hour' => 9,
            ],
            'task' => [
                'enabled' => true,
                'send_hour' => 19,
            ],
            'stagnation' => [
                'enabled' => true,
                'inactive_days' => 3,
                'send_hour' => 10,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = app(AppSettings::class)->getNotificationSettings();
        $this->assertTrue($settings['enabled']);
        $this->assertEquals(9, $settings['drip']['send_hour']);
    }

    /** @test */
    public function non_admin_cannot_access_notification_settings()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user);

        $response = $this->get(route('admin.notifications.settings'));
        $response->assertForbidden();
    }
}
