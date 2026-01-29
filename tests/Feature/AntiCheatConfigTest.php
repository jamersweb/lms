<?php

namespace Tests\Feature;

use Tests\TestCase;

class AntiCheatConfigTest extends TestCase
{
    public function test_youtube_player_has_required_player_vars(): void
    {
        $path = base_path('resources/js/Components/YouTubePlayer.vue');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('controls: 0', $content);
        $this->assertStringContainsString('disablekb: 1', $content);
        $this->assertStringContainsString('playsinline: 1', $content);
        $this->assertStringContainsString('rel: 0', $content);
        $this->assertStringContainsString('iv_load_policy: 3', $content);
        $this->assertStringContainsString('hl: \'en\'', $content);
        $this->assertStringContainsString('origin: window.location.origin', $content);
      }
}
