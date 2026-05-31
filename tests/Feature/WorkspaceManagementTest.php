<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_workspace_experience(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-workspaces-dashboard-root', false);
        $response->assertSee('data-workspaces-payload', false);
    }

    public function test_workspace_creation_page_renders_steps_root(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workspaces.create'));

        $response->assertOk();
        $response->assertSee('data-workspace-create-root', false);
        $response->assertSee(route('workspaces.store'), false);
    }

    public function test_user_can_create_workspace_after_accepting_guidelines(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('workspaces.store'), [
            'name' => 'Produto Principal',
            'description' => 'Ambiente principal para organizar os projetos SaaS.',
            'accepted_guidelines' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('workspace.name', 'Produto Principal');
        $response->assertJsonPath('workspace.plan_model', 'micro');

        $this->assertDatabaseHas('workspaces', [
            'owner_user_id' => $user->id,
            'name' => 'Produto Principal',
            'plan_model' => 'micro',
        ]);
    }

    public function test_workspace_requires_guidelines_acceptance(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('workspaces.store'), [
            'name' => 'Produto Principal',
            'accepted_guidelines' => false,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('accepted_guidelines');
    }

    public function test_user_cannot_create_more_than_eight_workspaces(): void
    {
        $user = User::factory()->create();

        Workspace::factory()
            ->count(Workspace::LIMIT_PER_USER)
            ->create(['owner_user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('workspaces.store'), [
            'name' => 'Workspace Extra',
            'accepted_guidelines' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }
}
