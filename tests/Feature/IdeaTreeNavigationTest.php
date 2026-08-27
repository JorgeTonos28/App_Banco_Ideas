<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Tag;
use App\Models\User;
use App\Services\IdeaTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaTreeNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_picker_only_contains_the_current_users_ideas(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownIdea = Idea::factory()->for($author)->create(['title' => 'Programa propio de innovación']);
        $publicIdea = Idea::factory()->for($otherUser)->create([
            'title' => 'Idea pública de otra persona',
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);

        $this->actingAs($author)
            ->get(route('ideas.create', ['parent' => $ownIdea->id]))
            ->assertOk()
            ->assertViewHas('parentCandidates', fn ($candidates) => $candidates->contains('id', $ownIdea->id)
                && ! $candidates->contains('id', $publicIdea->id))
            ->assertViewHas('relationCandidates', fn ($candidates) => $candidates->contains('id', $publicIdea->id))
            ->assertSee('Elegir idea madre')
            ->assertSee('Buscar sin importar espacios')
            ->assertSee($ownIdea->title)
            ->assertSee($publicIdea->title);
    }

    public function test_my_ideas_tree_is_collapsed_searchable_and_offers_add_child_actions(): void
    {
        $author = User::factory()->create();
        $root = Idea::factory()->for($author)->create([
            'visibility' => 'private',
            'workspace_status' => 'capturada',
        ]);
        $child = Idea::factory()->for($author)->create([
            'parent_idea_id' => $root->id,
            'visibility' => 'private',
            'workspace_status' => 'capturada',
        ]);

        $this->actingAs($author)
            ->get(route('my-ideas.index', ['vista' => 'tree']))
            ->assertOk()
            ->assertSee('Buscar dentro de este árbol sin importar espacios')
            ->assertSee('ideaTreeNode', false)
            ->assertSee(route('ideas.create', ['parent' => $root->id]), false)
            ->assertSee($child->title);
    }

    public function test_traceability_is_available_from_a_microidea_and_marks_the_open_node(): void
    {
        $author = User::factory()->create();
        $root = Idea::factory()->for($author)->create();
        $child = Idea::factory()->for($author)->create(['parent_idea_id' => $root->id]);

        $this->actingAs($author)
            ->get(route('ideas.show', $child->slug))
            ->assertOk()
            ->assertSee('Trazabilidad de microideas')
            ->assertSee('Buscar en la trazabilidad sin importar espacios')
            ->assertSee('Idea abierta')
            ->assertSee($root->title)
            ->assertSee($child->title);
    }

    public function test_tree_search_terms_cover_content_category_and_tags_without_spaces(): void
    {
        $author = User::factory()->create();
        $category = Category::create([
            'name' => 'Procesos Financieros',
            'slug' => 'procesos-financieros',
            'icon' => 'payments',
            'color' => '#003e6f',
        ]);
        $tag = Tag::create(['name' => 'Automatización contable', 'slug' => 'automatizacion-contable']);
        $root = Idea::factory()->for($author)->create(['title' => 'Programa financiero']);
        $child = Idea::factory()->for($author)->create([
            'parent_idea_id' => $root->id,
            'category_id' => $category->id,
            'title' => 'Conciliar facturas',
            'description' => 'Reduce el trabajo manual en el cierre mensual.',
        ]);
        $child->tags()->attach($tag);

        $tree = app(IdeaTreeService::class)->prepare(
            Idea::with(['category', 'tags'])->whereIn('id', [$root->id, $child->id])->get()
        );
        $rootTerms = $tree['searchTerms']->get($root->id);

        $this->assertStringContainsString('procesosfinancieros', $rootTerms);
        $this->assertStringContainsString('automatizacioncontable', $rootTerms);
        $this->assertStringContainsString('cierremensual', $rootTerms);
    }

    public function test_descendants_are_not_offered_as_possible_mothers_during_editing(): void
    {
        $author = User::factory()->create();
        $root = Idea::factory()->for($author)->create(['title' => 'Raíz que será editada']);
        $child = Idea::factory()->for($author)->create([
            'title' => 'Hija que no puede ser madre de la raíz',
            'parent_idea_id' => $root->id,
        ]);

        $this->actingAs($author)
            ->get(route('ideas.edit', $root))
            ->assertOk()
            ->assertViewHas('parentCandidates', fn ($candidates) => ! $candidates->contains('id', $child->id))
            ->assertViewHas('relationCandidates', fn ($candidates) => $candidates->contains('id', $child->id));
    }
}
