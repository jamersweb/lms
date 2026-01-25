<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\JournalEntry;
use Carbon\Carbon;

class JournalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_journal_entry()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'mood' => 'good',
            'content' => 'Day was productive.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', [
            'user_id' => $user->id,
            'entry_date' => Carbon::today()->toDateString(),
            'mood' => 'good',
        ]);
    }

    public function test_entry_is_upserted_for_same_date()
    {
        $user = User::factory()->create();
        
        // First entry
        $this->actingAs($user)->post(route('journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'mood' => 'good',
            'content' => 'First draft.',
        ]);
        
        // Update same day
        $this->actingAs($user)->post(route('journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'mood' => 'great',
            'content' => 'Updated draft.',
        ]);

        // Should count 1
        $this->assertEquals(1, JournalEntry::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('journal_entries', [
            'mood' => 'great',
            'content' => 'Updated draft.',
        ]);
    }
}
