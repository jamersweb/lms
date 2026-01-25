<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Note;
use App\Models\JournalEntry;

class Phase2SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_habits_can_be_created()
    {
        $habit = Habit::factory()->create();
        $this->assertModelExists($habit);
        $this->assertDatabaseHas('habits', ['id' => $habit->id]);
    }

    public function test_habit_logs_can_be_created()
    {
        $habit = Habit::factory()->create();
        $log = HabitLog::factory()->create([
            'habit_id' => $habit->id,
            'user_id' => $habit->user_id, // Ensure user matches
            'log_date' => now()->toDateString(),
        ]);
        $this->assertModelExists($log);
    }

    public function test_notes_can_be_created()
    {
        $note = Note::factory()->create();
        $this->assertModelExists($note);
    }

    public function test_journal_entries_can_be_created()
    {
        $entry = JournalEntry::factory()->create();
        $this->assertModelExists($entry);
    }
}
