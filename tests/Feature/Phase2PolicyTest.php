<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Note;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Gate;

class Phase2PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_habit_policy_ownership()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $userA->id]);

        $this->assertTrue(Gate::forUser($userA)->allows('update', $habit));
        $this->assertFalse(Gate::forUser($userB)->allows('update', $habit));
        $this->assertTrue(Gate::forUser($userA)->allows('delete', $habit));
        $this->assertFalse(Gate::forUser($userB)->allows('delete', $habit));
    }

    public function test_habit_log_policy_ownership()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $userA->id]);
        $log = HabitLog::factory()->create(['user_id' => $userA->id, 'habit_id' => $habit->id]);

        $this->assertTrue(Gate::forUser($userA)->allows('update', $log));
        $this->assertFalse(Gate::forUser($userB)->allows('update', $log));
    }

    public function test_note_policy_ownership()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $userA->id]);

        $this->assertTrue(Gate::forUser($userA)->allows('view', $note));
        $this->assertFalse(Gate::forUser($userB)->allows('view', $note));
    }

    public function test_journal_entry_policy_ownership()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entry = JournalEntry::factory()->create(['user_id' => $userA->id]);

        $this->assertTrue(Gate::forUser($userA)->allows('update', $entry));
        $this->assertFalse(Gate::forUser($userB)->allows('update', $entry));
    }
}
