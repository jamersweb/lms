<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_journal_entries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/journal', [
            'content' => 'Today was a blessed day. Alhamdulillah.',
            'mood' => 'great',
            'entry_date' => today()->toDateString()
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'user_id' => $user->id,
            'mood' => 'great',
        ]);

        $response->assertRedirect();
    }

    public function test_users_can_update_existing_journal_entry_for_same_day(): void
    {
        $user = User::factory()->create();
        
        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => today()->toDateString(),
            'content' => 'Original content',
            'mood' => 'good'
        ]);

        $response = $this->actingAs($user)->post('/journal', [
            'content' => 'Updated content',
            'mood' => 'great',
            'entry_date' => today()->toDateString()
        ]);

        $this->assertEquals(1, JournalEntry::where('user_id', $user->id)
            ->whereDate('entry_date', today())
            ->count());

        $this->assertDatabaseHas('journal_entries', [
            'user_id' => $user->id,
            'content' => 'Updated content',
            'mood' => 'great',
        ]);
    }

    public function test_journal_index_shows_todays_entry_and_history(): void
    {
        $user = User::factory()->create();
        
        // Today's entry
        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => today()->toDateString(),
            'content' => 'Today',
            'mood' => 'great'
        ]);

        // Yesterday's entry
        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => today()->subDay()->toDateString(),
            'content' => 'Yesterday',
            'mood' => 'good'
        ]);

        $response = $this->actingAs($user)->get('/journal');

        $response->assertInertia(fn ($page) => $page
            ->has('todayEntry')
            ->has('entries.data', 1) // Yesterday only (today excluded from history)
        );
    }

    public function test_mood_validation_only_allows_valid_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/journal', [
            'content' => 'Test content',
            'mood' => 'invalid_mood',
        ]);

        $response->assertSessionHasErrors('mood');
    }
}
