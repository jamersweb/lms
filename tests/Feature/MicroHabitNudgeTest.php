<?php

namespace Tests\Feature;

use App\Models\MicroHabitNudge;
use App\Models\NudgeDelivery;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MicroHabitNudgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_micro_habit_nudge(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $audioFile = UploadedFile::fake()->create('nudge.mp3', 100);

        $response = $this->actingAs($admin)->post(route('admin.micro-habit-nudges.store'), [
            'title' => 'Morning Adhkar Reminder',
            'description' => '30-second reminder for morning Adhkar',
            'audio' => $audioFile,
            'sunnah_topic' => 'Morning Adhkar',
            'send_at' => '08:00',
            'target_days' => [1, 2, 3, 4, 5], // Weekdays
        ]);

        $response->assertRedirect(route('admin.micro-habit-nudges.index'));

        $this->assertDatabaseHas('micro_habit_nudges', [
            'title' => 'Morning Adhkar Reminder',
            'sunnah_topic' => 'Morning Adhkar',
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists('micro-habit-nudges/' . $audioFile->hashName());
    }

    public function test_nudge_command_sends_to_users_with_whatsapp_enabled(): void
    {
        Storage::fake('public');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        NotificationPreference::create([
            'user_id' => $user1->id,
            'whatsapp_enabled' => true,
            'email_enabled' => true,
        ]);

        NotificationPreference::create([
            'user_id' => $user2->id,
            'whatsapp_enabled' => false,
            'email_enabled' => true,
        ]);

        $audioFile = UploadedFile::fake()->create('nudge.mp3', 100);
        Storage::disk('public')->put('micro-habit-nudges/test.mp3', 'fake audio');

        $nudge = MicroHabitNudge::create([
            'title' => 'Test Nudge',
            'audio_path' => 'micro-habit-nudges/test.mp3',
            'sunnah_topic' => 'Test',
            'send_at' => now()->format('H:i'),
            'target_days' => [now()->dayOfWeek],
            'is_active' => true,
        ]);

        $this->artisan('lms:send-nudges')
            ->assertSuccessful();

        // Only user1 should receive the nudge
        $this->assertDatabaseHas('nudge_deliveries', [
            'user_id' => $user1->id,
            'micro_habit_nudge_id' => $nudge->id,
            'delivery_status' => 'sent',
        ]);

        $this->assertDatabaseMissing('nudge_deliveries', [
            'user_id' => $user2->id,
            'micro_habit_nudge_id' => $nudge->id,
        ]);
    }

    public function test_nudge_not_sent_twice_same_day(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        NotificationPreference::create([
            'user_id' => $user->id,
            'whatsapp_enabled' => true,
        ]);

        $nudge = MicroHabitNudge::create([
            'title' => 'Test Nudge',
            'audio_path' => 'micro-habit-nudges/test.mp3',
            'sunnah_topic' => 'Test',
            'send_at' => now()->format('H:i'),
            'target_days' => [now()->dayOfWeek],
            'is_active' => true,
        ]);

        // First send
        $this->artisan('lms:send-nudges')->assertSuccessful();

        // Second send (should not create duplicate)
        $this->artisan('lms:send-nudges')->assertSuccessful();

        $deliveryCount = NudgeDelivery::where('user_id', $user->id)
            ->where('micro_habit_nudge_id', $nudge->id)
            ->whereDate('sent_at', now()->toDateString())
            ->count();

        $this->assertEquals(1, $deliveryCount);
    }

    public function test_nudge_respects_target_days(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        NotificationPreference::create([
            'user_id' => $user->id,
            'whatsapp_enabled' => true,
        ]);

        // Create nudge for weekdays only (1-5)
        $nudge = MicroHabitNudge::create([
            'title' => 'Weekday Nudge',
            'audio_path' => 'micro-habit-nudges/test.mp3',
            'sunnah_topic' => 'Test',
            'send_at' => now()->format('H:i'),
            'target_days' => [1, 2, 3, 4, 5], // Monday-Friday
            'is_active' => true,
        ]);

        // If today is Saturday (6) or Sunday (0), nudge should not be sent
        $currentDay = now()->dayOfWeek;
        if ($currentDay === 0 || $currentDay === 6) {
            $this->artisan('lms:send-nudges')->assertSuccessful();
            $this->assertDatabaseMissing('nudge_deliveries', [
                'user_id' => $user->id,
                'micro_habit_nudge_id' => $nudge->id,
            ]);
        } else {
            // If it's a weekday, nudge should be sent
            $this->artisan('lms:send-nudges')->assertSuccessful();
            $this->assertDatabaseHas('nudge_deliveries', [
                'user_id' => $user->id,
                'micro_habit_nudge_id' => $nudge->id,
            ]);
        }
    }

    public function test_admin_can_update_nudge(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $nudge = MicroHabitNudge::create([
            'title' => 'Old Title',
            'audio_path' => 'micro-habit-nudges/old.mp3',
            'sunnah_topic' => 'Old Topic',
            'send_at' => '08:00',
            'is_active' => true,
        ]);

        $newAudioFile = UploadedFile::fake()->create('new.mp3', 100);

        $response = $this->actingAs($admin)->put(route('admin.micro-habit-nudges.update', $nudge), [
            'title' => 'New Title',
            'description' => 'Updated description',
            'audio' => $newAudioFile,
            'sunnah_topic' => 'New Topic',
            'send_at' => '09:00',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.micro-habit-nudges.index'));

        $nudge->refresh();
        $this->assertEquals('New Title', $nudge->title);
        $this->assertEquals('New Topic', $nudge->sunnah_topic);
        $this->assertFalse($nudge->is_active);
    }
}
