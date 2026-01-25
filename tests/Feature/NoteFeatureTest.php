<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Note;

class NoteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_personal_note()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notes.store'), [
            'title' => 'My Thoughts',
            'content' => 'This is a personal note.',
            'scope' => 'personal',
            'pinned' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'title' => 'My Thoughts',
            'pinned' => true,
        ]);
    }

    public function test_can_filter_notes_by_scope()
    {
        $user = User::factory()->create();
        Note::factory()->create(['user_id' => $user->id, 'scope' => 'personal', 'title' => 'Personal Note']);
        Note::factory()->create(['user_id' => $user->id, 'scope' => 'course', 'title' => 'Course Note']);

        $response = $this->actingAs($user)->get(route('notes.index', ['scope' => 'personal']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->has('notes', 1)
            ->where('notes.0.title', 'Personal Note')
        );
    }
}
