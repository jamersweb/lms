<?php

namespace Tests\Feature;

use App\Models\AskThread;
use App\Models\User;
use App\Models\VoiceNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_voice_note_to_thread(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $thread = AskThread::create([
            'user_id' => $user->id,
            'subject' => 'Test Question',
            'status' => 'open',
        ]);

        $audioFile = UploadedFile::fake()->create('voice.mp3', 100);

        $response = $this->actingAs($admin)->post(route('ask.voice-note', $thread), [
            'audio' => $audioFile,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'audio_url', 'created_at']);

        $this->assertDatabaseHas('voice_notes', [
            'ask_thread_id' => $thread->id,
            'sender_id' => $admin->id,
        ]);

        Storage::disk('public')->assertExists('voice-notes/' . $audioFile->hashName());
    }

    public function test_student_cannot_upload_voice_note(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_admin' => false]);
        $thread = AskThread::create([
            'user_id' => $user->id,
            'subject' => 'Test Question',
            'status' => 'open',
        ]);

        $audioFile = UploadedFile::fake()->create('voice.mp3', 100);

        $response = $this->actingAs($user)->post(route('ask.voice-note', $thread), [
            'audio' => $audioFile,
        ]);

        $response->assertStatus(403);
    }

    public function test_voice_note_validation(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $thread = AskThread::create([
            'user_id' => $user->id,
            'subject' => 'Test Question',
            'status' => 'open',
        ]);

        // Test missing file - Laravel returns 302 redirect with validation errors
        $response = $this->actingAs($admin)->post(route('ask.voice-note', $thread), []);
        $response->assertSessionHasErrors('audio');

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->actingAs($admin)->post(route('ask.voice-note', $thread), [
            'audio' => $invalidFile,
        ]);
        $response->assertSessionHasErrors('audio');

        // Test file too large (over 10MB)
        $largeFile = UploadedFile::fake()->create('voice.mp3', 11000); // 11MB
        $response = $this->actingAs($admin)->post(route('ask.voice-note', $thread), [
            'audio' => $largeFile,
        ]);
        $response->assertSessionHasErrors('audio');
    }

    public function test_user_can_delete_own_voice_note(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $thread = AskThread::create([
            'user_id' => $user->id,
            'subject' => 'Test Question',
            'status' => 'open',
        ]);

        $voiceNote = VoiceNote::create([
            'ask_thread_id' => $thread->id,
            'sender_id' => $user->id,
            'audio_path' => 'voice-notes/test.mp3',
        ]);

        Storage::disk('public')->put('voice-notes/test.mp3', 'fake audio content');

        $response = $this->actingAs($user)->delete(route('voice-notes.destroy', $voiceNote));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('voice_notes', ['id' => $voiceNote->id]);
        Storage::disk('public')->assertMissing('voice-notes/test.mp3');
    }

    public function test_user_cannot_delete_other_users_voice_note(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $thread = AskThread::create([
            'user_id' => $user1->id,
            'subject' => 'Test Question',
            'status' => 'open',
        ]);

        $voiceNote = VoiceNote::create([
            'ask_thread_id' => $thread->id,
            'sender_id' => $user1->id,
            'audio_path' => 'voice-notes/test.mp3',
        ]);

        $response = $this->actingAs($user2)->delete(route('voice-notes.destroy', $voiceNote));

        $response->assertStatus(403);
    }
}
