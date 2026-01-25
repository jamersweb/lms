<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\PointsEvent;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PointsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_award_points()
    {
        $user = User::factory()->create();
        $service = new PointsService();

        $event = $service->awardOnce('test_key_1', $user->id, 10, 'test_event');

        $this->assertNotNull($event);
        $this->assertEquals(10, $event->points);
        $this->assertEquals($user->id, $event->user_id);
    }

    public function test_award_is_idempotent()
    {
        $user = User::factory()->create();
        $service = new PointsService();
        $key = 'unique_event_key';

        $event1 = $service->awardOnce($key, $user->id, 10, 'test_event');
        $event2 = $service->awardOnce($key, $user->id, 50, 'test_event'); // Different points to verify it doesn't update

        $this->assertEquals($event1->id, $event2->id);
        $this->assertEquals(10, $event2->points); // Should stay 10
        $this->assertEquals(1, PointsEvent::count());
    }

    public function test_calculates_total_points()
    {
        $user = User::factory()->create();
        $service = new PointsService();

        $service->awardOnce('key1', $user->id, 10, 'event');
        $service->awardOnce('key2', $user->id, 25, 'event');
        $service->awardOnce('key3', $user->id, -5, 'penalty');

        $this->assertEquals(30, $service->totalPoints($user->id));
    }
}
