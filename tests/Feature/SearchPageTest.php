<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_from_search(): void
    {
        $response = $this->get('/search?q=test');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/search?q=test');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->where('query', 'test')
            ->has('results')
        );
    }

    public function test_search_props_include_query_and_results_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/search?q=test');

        $response->assertInertia(fn ($page) => $page
            ->where('query', 'test')
            ->where('results', [])
        );
    }
}

