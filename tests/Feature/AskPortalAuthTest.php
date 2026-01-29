<?php

namespace Tests\Feature;

use App\Models\AskMessage;
use App\Models\AskThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class AskPortalAuthTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    protected function createThreadFor(User $owner): AskThread
    {
        $thread = AskThread::create([
            'user_id' => $owner->id,
            'subject' => 'Private Question',
            'status' => 'open',
        ]);

        AskMessage::create([
            'ask_thread_id' => $thread->id,
            'user_id' => $owner->id,
            'sender_type' => 'user',
            'body' => 'My private question body',
        ]);

        return $thread;
    }

    public function test_student_cannot_view_others_threads(): void
    {
        $this->seed();

        $owner = User::where('email', 'umar@example.com')->firstOrFail();
        $other = User::where('email', 'fatima@example.com')->firstOrFail();

        $thread = $this->createThreadFor($owner);

        $this->actingAs($other);

        $this->get(route('ask.show', $thread))
            ->assertStatus(403);
    }

    public function test_admin_can_view_all_threads(): void
    {
        $this->seed();

        $owner = User::where('email', 'umar@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $thread = $this->createThreadFor($owner);

        $this->actingAs($admin);

        $this->get(route('admin.ask.show', $thread))
            ->assertStatus(200);
    }

    public function test_posting_reply_works_for_student_and_admin(): void
    {
        $this->seed();

        $owner = User::where('email', 'umar@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $thread = $this->createThreadFor($owner);

        // Student can reply to own thread
        $this->actingAs($owner);

        $this->post(route('ask.reply', $thread), [
            'body' => 'Follow-up from student',
        ])->assertRedirect();

        $this->assertDatabaseHas('ask_messages', [
            'ask_thread_id' => $thread->id,
            'body' => 'Follow-up from student',
            'sender_type' => 'user',
        ]);

        // Admin can reply via admin portal
        $this->actingAs($admin);

        $this->post(route('admin.ask.reply', $thread), [
            'body' => 'Reply from mentor',
        ])->assertRedirect();

        $this->assertDatabaseHas('ask_messages', [
            'ask_thread_id' => $thread->id,
            'body' => 'Reply from mentor',
            'sender_type' => 'mentor',
        ]);
    }
}

