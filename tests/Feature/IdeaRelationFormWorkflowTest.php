<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaRelationFormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['is_active' => true]);
        $this->category = Category::create([
            'name' => 'Procesos',
            'slug' => 'procesos-relaciones-formulario',
            'icon' => 'account_tree',
            'color' => '#005696',
        ]);
    }

    public function test_relation_selected_in_create_form_is_saved_with_the_new_idea(): void
    {
        $target = $this->idea($this->author, ['title' => 'Inventario institucional existente']);

        $this->actingAs($this->author)
            ->post(route('ideas.store'), $this->payload([
                'title' => 'Alertas para reponer inventario institucional',
                'idea_relations' => [[
                    'target_idea_id' => $target->id,
                    'type' => 'depends_on',
                    'rationale' => 'Las alertas requieren el inventario institucional como fuente de datos.',
                ]],
            ]))
            ->assertSessionDoesntHaveErrors();

        $source = Idea::where('title', 'Alertas para reponer inventario institucional')->firstOrFail();

        $this->assertDatabaseHas('idea_relations', [
            'source_idea_id' => $source->id,
            'target_idea_id' => $target->id,
            'type' => 'depends_on',
            'status' => 'approved',
        ]);
    }

    public function test_edit_form_can_update_add_and_remove_outgoing_relations_atomically(): void
    {
        $source = $this->idea($this->author, ['title' => 'Plataforma de seguimiento']);
        $keptTarget = $this->idea($this->author, ['title' => 'Registro de participantes']);
        $removedTarget = $this->idea($this->author, ['title' => 'Reporte manual anterior']);
        $newTarget = $this->idea($this->author, ['title' => 'Panel institucional']);

        $kept = $this->relation($source, $keptTarget, 'related_to');
        $removed = $this->relation($source, $removedTarget, 'superseded_by');

        $this->actingAs($this->author)
            ->put(route('ideas.update', $source), $this->payload([
                'title' => $source->title,
                'idea_relations' => [
                    [
                        'id' => $kept->id,
                        'target_idea_id' => $keptTarget->id,
                        'type' => 'complements',
                        'rationale' => 'El registro alimenta el seguimiento operativo.',
                    ],
                    [
                        'target_idea_id' => $newTarget->id,
                        'type' => 'enables',
                        'rationale' => 'La plataforma habilita los indicadores del panel.',
                    ],
                ],
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('idea_relations', ['id' => $removed->id]);
        $this->assertDatabaseHas('idea_relations', [
            'id' => $kept->id,
            'type' => 'complements',
            'rationale' => 'El registro alimenta el seguimiento operativo.',
        ]);
        $this->assertDatabaseHas('idea_relations', [
            'source_idea_id' => $source->id,
            'target_idea_id' => $newTarget->id,
            'type' => 'enables',
        ]);
    }

    public function test_private_idea_cannot_add_a_cross_author_relation_from_the_form(): void
    {
        $otherAuthor = User::factory()->create(['is_active' => true]);
        $target = $this->idea($otherAuthor, [
            'title' => 'Idea pública de otro autor',
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now(),
        ]);

        $this->actingAs($this->author)
            ->post(route('ideas.store'), $this->payload([
                'idea_relations' => [[
                    'target_idea_id' => $target->id,
                    'type' => 'complements',
                    'rationale' => 'Intento de conexión antes de publicar la idea origen.',
                ]],
            ]))
            ->assertSessionHasErrors('idea_relations.0.target_idea_id');

        $this->assertDatabaseCount('idea_relations', 0);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Nueva propuesta conectada',
            'description' => 'Descripción suficientemente amplia para registrar y conectar esta propuesta institucional.',
            'problem_opportunity' => 'La información se encuentra dispersa entre varios procesos.',
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'access_scope' => 'only_me',
            'workspace_status' => 'capturada',
        ], $overrides);
    }

    private function idea(User $author, array $overrides = []): Idea
    {
        return Idea::factory()->for($author)->create(array_merge([
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'access_scope' => 'only_me',
            'workspace_status' => 'capturada',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
        ], $overrides));
    }

    private function relation(Idea $source, Idea $target, string $type): IdeaRelation
    {
        return IdeaRelation::create([
            'source_idea_id' => $source->id,
            'target_idea_id' => $target->id,
            'type' => $type,
            'status' => 'approved',
            'rationale' => 'Relación inicial.',
            'created_by_user_id' => $this->author->id,
            'reviewed_by_user_id' => $this->author->id,
            'reviewed_at' => now(),
        ]);
    }
}
