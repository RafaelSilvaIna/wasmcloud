<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_render_global_header_data(): void
    {
        $user = User::factory()->create(['name' => 'Rafael Silva Inacio']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-authenticated-header-root', false);
        $response->assertSee('data-user-name="Rafael Silva Inacio"', false);
        $response->assertSee(route('projects.create'), false);
        $response->assertSee(route('documentation'), false);
    }

    public function test_authenticated_menu_routes_are_available(): void
    {
        $user = User::factory()->create();

        foreach ([route('profile'), route('documentation.article', 'assinaturas'), route('projects.create'), route('api.docs'), route('system.specs'), route('settings')] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_documentation_articles_have_shareable_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('documentation'))
            ->assertRedirect('/documentacao/assinaturas');

        $this->actingAs($user)
            ->get(route('documentation.article', 'cobranca-consumo'))
            ->assertOk()
            ->assertSee('data-current-article="cobranca-consumo"', false);
    }
}
