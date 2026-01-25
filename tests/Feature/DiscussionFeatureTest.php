<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Enrollment;

class DiscussionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_course_discussions()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $discussion = Discussion::factory()->create(['course_id' => $course->id, 'title' => 'Hello World']);

        $response = $this->actingAs($user)->get(route('courses.discussions.index', $course));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Discussions/Index')
            ->has('discussions.data', 1)
            ->where('discussions.data.0.title', 'Hello World')
        );
    }

    public function test_can_create_discussion()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        // User must be enrolled to create discussions
        Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id]);

        $response = $this->actingAs($user)->post(route('courses.discussions.store', $course), [
            'title' => 'New Topic',
            'body' => 'I have a question.',
        ]);

        // Expect redirect to show page (Route is discussions.show, passing discussion ID)
        // Since ID is dynamic, we just check redirect status and DB
        $response->assertRedirect();
        
        $this->assertDatabaseHas('discussions', [
            'course_id' => $course->id,
            'title' => 'New Topic',
            'user_id' => $user->id,
        ]);
        
        // Check points awarded
        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'discussion_created',
            'points' => 2,
        ]);
    }

    public function test_non_enrolled_user_cannot_create_discussion()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        // User is NOT enrolled

        $response = $this->actingAs($user)->post(route('courses.discussions.store', $course), [
            'title' => 'New Topic',
            'body' => 'I have a question.',
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseMissing('discussions', [
            'course_id' => $course->id,
            'title' => 'New Topic',
        ]);
    }

    public function test_can_reply_to_discussion()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $discussion = Discussion::factory()->create(['course_id' => $course->id, 'status' => 'open']);

        $response = $this->actingAs($user)->post(route('discussions.replies.store', $discussion), [
            'body' => 'Great point!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('discussion_replies', [
            'discussion_id' => $discussion->id,
            'body' => 'Great point!',
            'user_id' => $user->id,
        ]);

        // Check points awarded
        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'reply_created',
            'points' => 1,
        ]);
    }

    public function test_cannot_reply_to_closed_discussion()
    {
        $user = User::factory()->create();
        $discussion = Discussion::factory()->create(['status' => 'closed']);

        $response = $this->actingAs($user)->post(route('discussions.replies.store', $discussion), [
            'body' => 'Late reply',
        ]);

        $response->assertStatus(403);
    }
}
