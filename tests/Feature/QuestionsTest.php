<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionMessage;
use App\Models\User;
use App\Models\UserVoiceNote;
use App\Notifications\NewQuestionNotification;
use App\Notifications\QuestionReplyNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_question(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('questions.store'), [
            'title' => 'How do I complete this lesson?',
            'body' => 'I am having trouble understanding the concept.',
            'priority' => 'normal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'user_id' => $user->id,
            'title' => 'How do I complete this lesson?',
            'status' => Question::STATUS_OPEN,
        ]);

        // Check initial message was created
        $question = Question::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('question_messages', [
            'question_id' => $question->id,
            'sender_id' => $user->id,
            'sender_role' => QuestionMessage::SENDER_ROLE_STUDENT,
        ]);
    }

    public function test_student_can_only_view_own_questions(): void
    {
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();

        $question = Question::create([
            'user_id' => $studentA->id,
            'title' => 'Student A Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        // Student B tries to view Student A's question
        $response = $this->actingAs($studentB)->get(route('questions.show', $question));
        $response->assertStatus(403);
    }

    public function test_teacher_can_view_any_question_and_reply(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        // Admin can view
        $response = $this->actingAs($admin)->get(route('admin.questions.show', $question));
        $response->assertStatus(200);

        // Admin can reply
        $response = $this->actingAs($admin)->post(route('admin.questions.message', $question), [
            'message' => 'Here is my reply to your question.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_messages', [
            'question_id' => $question->id,
            'sender_id' => $admin->id,
            'sender_role' => QuestionMessage::SENDER_ROLE_ADMIN,
            'message' => 'Here is my reply to your question.',
        ]);

        // Question status should be updated to answered
        $question->refresh();
        $this->assertEquals(Question::STATUS_ANSWERED, $question->status);
        $this->assertNotNull($question->last_reply_at);
        $this->assertEquals($admin->id, $question->last_reply_by);
    }

    public function test_internal_note_visibility(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        // Admin posts internal note
        QuestionMessage::create([
            'question_id' => $question->id,
            'sender_id' => $admin->id,
            'sender_role' => QuestionMessage::SENDER_ROLE_ADMIN,
            'message' => 'This is an internal note',
            'is_internal' => true,
        ]);

        // Student should NOT see internal message
        $response = $this->actingAs($student)->get(route('questions.show', $question));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Questions/Show')
            ->has('messages', 1) // Only the initial student message, not the internal one
            ->where('messages.0.message', 'Test body')
        );

        // Admin should see internal message
        $response = $this->actingAs($admin)->get(route('admin.questions.show', $question));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Questions/Show')
            ->has('messages', 2) // Both messages
        );
    }

    public function test_status_update_rules(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        // Admin updates status to resolved
        $response = $this->actingAs($admin)->patch(route('admin.questions.update', $question), [
            'status' => Question::STATUS_RESOLVED,
        ]);

        $response->assertRedirect();
        $question->refresh();
        $this->assertEquals(Question::STATUS_RESOLVED, $question->status);
        $this->assertNotNull($question->closed_at);

        // Student cannot post new message if resolved (enforced in controller)
        $response = $this->actingAs($student)->post(route('questions.message', $question), [
            'message' => 'Follow-up question',
        ]);

        $response->assertSessionHasErrors('message');
    }

    public function test_rate_limiting_for_question_creation(): void
    {
        $user = User::factory()->create();
        Carbon::setTestNow('2026-01-30 12:00:00');

        // Create 5 questions in the last hour
        for ($i = 1; $i <= 5; $i++) {
            Question::create([
                'user_id' => $user->id,
                'title' => "Question {$i}",
                'body' => 'Test body',
                'status' => Question::STATUS_OPEN,
                'created_at' => Carbon::now()->subMinutes(10 * $i),
            ]);
        }

        // 6th question should be rejected
        $response = $this->actingAs($user)->post(route('questions.store'), [
            'title' => 'Question 6',
            'body' => 'Test body',
        ]);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseMissing('questions', [
            'title' => 'Question 6',
        ]);
    }

    public function test_notifications_when_teacher_replies(): void
    {
        Notification::fake();

        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        // Admin replies
        $this->actingAs($admin)->post(route('admin.questions.message', $question), [
            'message' => 'Here is my reply.',
        ]);

        // Student should be notified
        Notification::assertSentTo(
            $student,
            QuestionReplyNotification::class,
            function ($notification) use ($question) {
                return $notification->question->id === $question->id;
            }
        );
    }

    public function test_notifications_when_student_asks(): void
    {
        Notification::fake();

        $student = User::factory()->create();
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);

        // Student creates question
        $this->actingAs($student)->post(route('questions.store'), [
            'title' => 'New Question',
            'body' => 'Test body',
        ]);

        // All admins should be notified
        Notification::assertSentTo(
            $admin1,
            NewQuestionNotification::class
        );
        Notification::assertSentTo(
            $admin2,
            NewQuestionNotification::class
        );
    }

    public function test_audio_upload_in_message(): void
    {
        Storage::fake('public');

        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        $audioFile = UploadedFile::fake()->create('audio.mp3', 1000); // 1MB

        $response = $this->actingAs($admin)->post(route('admin.questions.message', $question), [
            'message' => 'Here is my reply with audio.',
            'audio_file' => $audioFile,
        ]);

        $response->assertRedirect();
        $message = QuestionMessage::where('question_id', $question->id)
            ->where('sender_id', $admin->id)
            ->first();

        $this->assertNotNull($message);
        $this->assertEquals(QuestionMessage::AUDIO_TYPE_UPLOAD, $message->audio_type);
        $this->assertNotNull($message->audio_path);
        Storage::disk('public')->assertExists($message->audio_path);
        $this->assertNotNull($message->audio_playable_url);
    }

    public function test_audio_url_in_message(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.questions.message', $question), [
            'message' => 'Here is my reply with audio URL.',
            'audio_url' => 'https://example.com/audio.mp3',
        ]);

        $response->assertRedirect();
        $message = QuestionMessage::where('question_id', $question->id)
            ->where('sender_id', $admin->id)
            ->first();

        $this->assertNotNull($message);
        $this->assertEquals(QuestionMessage::AUDIO_TYPE_URL, $message->audio_type);
        $this->assertEquals('https://example.com/audio.mp3', $message->audio_url);
        $this->assertEquals('https://example.com/audio.mp3', $message->audio_playable_url);
    }

    public function test_admin_can_assign_question(): void
    {
        $student = User::factory()->create();
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
        ]);

        $response = $this->actingAs($admin1)->patch(route('admin.questions.update', $question), [
            'assigned_to' => $admin2->id,
        ]);

        $response->assertRedirect();
        $question->refresh();
        $this->assertEquals($admin2->id, $question->assigned_to);
    }

    public function test_admin_can_update_priority(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_OPEN,
            'priority' => Question::PRIORITY_NORMAL,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.questions.update', $question), [
            'priority' => Question::PRIORITY_HIGH,
        ]);

        $response->assertRedirect();
        $question->refresh();
        $this->assertEquals(Question::PRIORITY_HIGH, $question->priority);
    }

    public function test_student_can_mark_question_resolved(): void
    {
        $student = User::factory()->create();

        $question = Question::create([
            'user_id' => $student->id,
            'title' => 'Student Question',
            'body' => 'Test body',
            'status' => Question::STATUS_ANSWERED,
        ]);

        $response = $this->actingAs($student)->patch(route('questions.resolve', $question));

        $response->assertRedirect();
        $question->refresh();
        $this->assertEquals(Question::STATUS_RESOLVED, $question->status);
        $this->assertNotNull($question->closed_at);
    }

    public function test_voice_note_upload(): void
    {
        Storage::fake('public');

        $student = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $audioFile = UploadedFile::fake()->create('voice-note.mp3', 1000);

        $response = $this->actingAs($admin)->post(route('admin.users.voice-notes.store', $student), [
            'title' => 'Voice Note Title',
            'note' => 'Voice note description',
            'audio_file' => $audioFile,
            'is_private' => true,
        ]);

        $response->assertRedirect();
        $voiceNote = UserVoiceNote::where('user_id', $student->id)->first();

        $this->assertNotNull($voiceNote);
        $this->assertEquals(UserVoiceNote::AUDIO_TYPE_UPLOAD, $voiceNote->audio_type);
        $this->assertNotNull($voiceNote->audio_path);
        Storage::disk('public')->assertExists($voiceNote->audio_path);
        $this->assertEquals('Voice Note Title', $voiceNote->title);
    }
}
