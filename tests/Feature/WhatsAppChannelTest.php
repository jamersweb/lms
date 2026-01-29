<?php

namespace Tests\Feature;

use App\Console\Commands\StagnationCheckCommand;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\WhatsApp\FakeWhatsAppChannel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WhatsAppChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setupStalledUser(bool $whatsAppEnabled = true): User
    {
        Carbon::setTestNow('2026-01-10 00:00:00');

        $user = User::factory()->create();

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

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

        NotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => true,
            'whatsapp_enabled' => $whatsAppEnabled,
        ]);

        return $user;
    }

    public function test_when_whatsapp_enabled_fake_channel_receives_message(): void
    {
        Config::set('whatsapp.driver', 'fake');
        FakeWhatsAppChannel::flush();
        Notification::fake();

        $user = $this->setupStalledUser(true);

        Carbon::setTestNow('2026-01-20 00:00:00');

        $this->artisan('lms:stagnation-check --days=3')->assertExitCode(0);

        $messages = FakeWhatsAppChannel::messages();
        $this->assertNotEmpty($messages);
        $this->assertSame('+10000000000', $messages[0]['to']);
    }

    public function test_when_whatsapp_disabled_no_message_sent(): void
    {
        Config::set('whatsapp.driver', 'fake');
        FakeWhatsAppChannel::flush();
        Notification::fake();

        $this->setupStalledUser(false);

        Carbon::setTestNow('2026-01-20 00:00:00');

        $this->artisan('lms:stagnation-check --days=3')->assertExitCode(0);

        $messages = FakeWhatsAppChannel::messages();
        $this->assertEmpty($messages);
    }
}

