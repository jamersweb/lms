<?php

namespace Tests\Feature;

use App\Models\DuaPrayer;
use App\Models\DuaRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuaWallTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_dua_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dua.store'), [
            'request_text' => 'Please make dua for my exams.',
            'is_anonymous' => false,
        ]);

        $response->assertRedirect(route('dua.index'));

        $this->assertDatabaseHas('dua_requests', [
            'request_text' => 'Please make dua for my exams.',
            'user_id' => $user->id,
            'is_anonymous' => false,
        ]);
    }

    public function test_prayed_for_you_increments_once_per_user(): void
    {
        $user = User::factory()->create();

        $dua = DuaRequest::create([
            'user_id' => null,
            'is_anonymous' => true,
            'request_text' => 'Anonymous dua',
        ]);

        $this->actingAs($user)->post(route('dua.pray', $dua))
            ->assertRedirect(route('dua.index'));

        $this->assertDatabaseHas('dua_prayers', [
            'dua_request_id' => $dua->id,
            'user_id' => $user->id,
        ]);

        // Second attempt should not create a duplicate
        $this->actingAs($user)->post(route('dua.pray', $dua))
            ->assertRedirect(route('dua.index'));

        $this->assertEquals(1, DuaPrayer::where('dua_request_id', $dua->id)->where('user_id', $user->id)->count());
    }
}
