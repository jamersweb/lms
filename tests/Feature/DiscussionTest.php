<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_discussions(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        // User must be enrolled to create discussions
        Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id]);

        $response = $this->actingAs($user)->post("/courses/{$course->id}/discussions", [
            'title' => 'Question about Intention',
            'body' => 'How often should we renew our intention?'
        ]);

        $this->assertDatabaseHas('discussions', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'title' => 'Question about Intention',
        ]);
    }

    public function test_creating_discussion_awards_points(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        // User must be enrolled to create discussions
        Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id]);

        $this->actingAs($user)->post("/courses/{$course->id}/discussions", [
            'title' => 'Test Discussion',
            'body' => 'Test body'
        ]);

        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'discussion_created',
            'points' => 2
        ]);
    }

    public function test_users_can_reply_to_discussions(): void
    {
        $user = User::factory()->create();
        $discussion = Discussion::factory()->create();

        $response = $this->actingAs($user)->post("/discussions/{$discussion->id}/replies", [
            'body' => 'This is my reply'
        ]);

        $this->assertDatabaseHas('discussion_replies', [
            'user_id' => $user->id,
            'discussion_id' => $discussion->id,
            'body' => 'This is my reply',
        ]);
    }

    public function test_replying_to_discussion_awards_points(): void
    {
        $user = User::factory()->create();
        $discussion = Discussion::factory()->create();

        $this->actingAs($user)->post("/discussions/{$discussion->id}/replies", [
            'body' => 'Test reply'
        ]);

        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'reply_created',
            'points' => 1
        ]);
    }

    public function test_discussion_list_shows_reply_count(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $discussion = Discussion::factory()->create(['course_id' => $course->id]);
        
        DiscussionReply::factory()->count(3)->create(['discussion_id' => $discussion->id]);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/discussions");

        $response->assertInertia(fn ($page) => $page
            ->where('discussions.data.0.replies_count', 3)
        );
    }

    public function test_pinned_discussions_appear_first(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        $regular = Discussion::factory()->create([
            'course_id' => $course->id,
            'is_pinned' => false,
            'created_at' => now()
        ]);
        
        $pinned = Discussion::factory()->create([
            'course_id' => $course->id,
            'is_pinned' => true,
            'created_at' => now()->subDay()
        ]);

        $response = $this->actingAs($user)->get("/courses/{$course->id}/discussions");

        $response->assertInertia(fn ($page) => $page
            ->where('discussions.data.0.is_pinned', true)
        );
    }
}
