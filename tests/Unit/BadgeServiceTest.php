<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Badge;
use App\Models\PointsEvent;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BadgeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_awards_points_badge()
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'criteria' => ['type' => 'points', 'count' => 100],
        ]);
        
        PointsEvent::factory()->create(['user_id' => $user->id, 'points' => 100]);

        $service = new BadgeService();
        $awarded = $service->evaluateAndAward($user);

        $this->assertCount(1, $awarded);
        $this->assertEquals($badge->id, $awarded[0]->id);
        $this->assertTrue($user->badges()->where('badge_id', $badge->id)->exists());
    }

    public function test_awards_event_count_badge()
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'criteria' => ['type' => 'event_count', 'event_type' => 'lesson_completed', 'count' => 1],
        ]);
        
        PointsEvent::factory()->create([
            'user_id' => $user->id, 
            'event_type' => 'lesson_completed'
        ]);

        $service = new BadgeService();
        $awarded = $service->evaluateAndAward($user);

        $this->assertCount(1, $awarded);
        $this->assertEquals($badge->id, $awarded[0]->id);
    }
    
    public function test_does_not_award_duplicate_badges()
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'criteria' => ['type' => 'points', 'count' => 100],
        ]);
        
        PointsEvent::factory()->create(['user_id' => $user->id, 'points' => 100]);

        $service = new BadgeService();
        $service->evaluateAndAward($user); // First time
        $awarded = $service->evaluateAndAward($user); // Second time

        $this->assertCount(0, $awarded);
        $this->assertEquals(1, $user->badges()->count());
    }
}
