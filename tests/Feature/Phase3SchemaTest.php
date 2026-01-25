<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PointsEvent;
use App\Models\Badge;
use App\Models\UserSettings;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\ModerationAction;

class Phase3SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_points_events_can_be_created()
    {
        $event = PointsEvent::factory()->create();
        $this->assertModelExists($event);
    }

    public function test_badges_can_be_created()
    {
        $badge = Badge::factory()->create();
        $this->assertModelExists($badge);
    }

    public function test_user_settings_can_be_created()
    {
        $settings = UserSettings::factory()->create();
        $this->assertModelExists($settings);
    }

    public function test_discussions_can_be_created()
    {
        $discussion = Discussion::factory()->create();
        $this->assertModelExists($discussion);
    }

    public function test_replies_can_be_created()
    {
        $reply = DiscussionReply::factory()->create();
        $this->assertModelExists($reply);
    }

    public function test_moderation_actions_can_be_created()
    {
        $action = ModerationAction::factory()->create();
        $this->assertModelExists($action);
    }
}
