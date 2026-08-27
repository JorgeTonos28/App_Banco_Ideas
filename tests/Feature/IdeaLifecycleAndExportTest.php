<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\User;
use App\Services\IdeaStatusCascadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaLifecycleAndExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminal_status_cascades_to_every_descendant_with_history(): void
    {
        $user = User::factory()->create();
        $root = Idea::factory()->for($user)->create(['workspace_status' => 'en_ejecucion']);
        $child = Idea::factory()->for($user)->create(['parent_idea_id' => $root->id, 'workspace_status' => 'capturada']);
        $grandchild = Idea::factory()->for($user)->create(['parent_idea_id' => $child->id, 'workspace_status' => 'en_clarificacion']);

        $count = app(IdeaStatusCascadeService::class)->cascadeTerminalStatus($root, 'archivada', $user);

        $this->assertSame(2, $count);
        $this->assertSame('archivada', $child->fresh()->workspace_status);
        $this->assertSame('archivada', $grandchild->fresh()->workspace_status);
        $this->assertDatabaseHas('idea_status_histories', [
            'idea_id' => $grandchild->id,
            'new_status' => 'archivada',
            'workflow' => 'workspace',
        ]);
    }

    public function test_author_can_export_a_visible_tree_with_selected_fields(): void
    {
        $user = User::factory()->create();
        $root = Idea::factory()->for($user)->create(['title' => 'Idea madre exportable']);
        Idea::factory()->for($user)->create([
            'parent_idea_id' => $root->id,
            'title' => 'Subidea incluida',
            'problem_opportunity' => 'Proceso manual y repetitivo.',
        ]);

        $this->actingAs($user)
            ->get(route('ideas.export', ['idea' => $root, 'format' => 'json', 'fields' => ['problem_opportunity']]))
            ->assertOk()
            ->assertHeader('content-type', 'application/json; charset=UTF-8')
            ->assertSee('Idea madre exportable')
            ->assertSee('Subidea incluida')
            ->assertSee('Proceso manual y repetitivo.');
    }

    public function test_tree_controls_explain_terminal_state_visibility(): void
    {
        $user = User::factory()->create();
        $root = Idea::factory()->for($user)->create(['visibility' => 'private', 'workspace_status' => 'en_ejecucion']);
        Idea::factory()->for($user)->create([
            'parent_idea_id' => $root->id,
            'visibility' => 'private',
            'workspace_status' => 'descartada',
        ]);

        $this->actingAs($user)
            ->get(route('my-ideas.index', ['vista' => 'tree']))
            ->assertOk()
            ->assertSee('Estados en primer nivel')
            ->assertSee('Subideas visibles');
    }
}
