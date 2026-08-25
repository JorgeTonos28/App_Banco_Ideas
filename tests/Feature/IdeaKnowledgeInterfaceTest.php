<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryDimension;
use App\Models\Idea;
use App\Models\IdeaRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaKnowledgeInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $admin;

    private Category $primaryCategory;

    private CategoryDimension $scopeDimension;

    private Category $institutionalScope;

    private Category $regionalScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $primaryDimension = CategoryDimension::where('is_primary', true)->firstOrFail();
        $this->primaryCategory = Category::create([
            'category_dimension_id' => $primaryDimension->id,
            'name' => 'Procesos institucionales',
            'icon' => 'account_tree',
            'color' => '#003E6F',
        ]);
        $this->scopeDimension = CategoryDimension::create([
            'name' => 'Alcance',
            'selection_mode' => 'single',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->institutionalScope = $this->term('Institucional');
        $this->regionalScope = $this->term('Regional');
    }

    public function test_private_detail_renders_hierarchy_classifications_relations_and_publication_controls(): void
    {
        $parent = $this->privateIdea('Programa de transformación integral');
        $idea = $this->privateIdea('Automatización del registro operativo', $parent);
        $related = $this->privateIdea('Catálogo de procesos normalizados');
        $idea->categories()->sync([$this->primaryCategory->id, $this->institutionalScope->id]);

        IdeaRelation::create([
            'source_idea_id' => $idea->id,
            'target_idea_id' => $related->id,
            'type' => 'depends_on',
            'status' => 'approved',
            'created_by_user_id' => $this->author->id,
            'reviewed_by_user_id' => $this->author->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->author)
            ->get(route('ideas.show', $idea->slug))
            ->assertOk()
            ->assertSee('Relaciones semánticas')
            ->assertSee('Gestionar relaciones')
            ->assertSee('Editar relación')
            ->assertSee('Registrada por '.$this->author->name)
            ->assertSee('Trazabilidad de microideas')
            ->assertDontSee('Estructura madre e hijas')
            ->assertDontSee('Agregar madre')
            ->assertSee($parent->title)
            ->assertSee($related->title)
            ->assertSee($this->institutionalScope->name)
            ->assertSee('Solicitar revisión editorial')
            ->assertSee('Trabajo privado')
            ->assertDontSee('¿Qué te parece esta idea?');
    }

    public function test_tree_view_is_not_limited_to_first_card_page(): void
    {
        $root = $this->privateIdea('Raíz del programa de mejora');
        foreach (range(1, 10) as $index) {
            $this->privateIdea("Subidea operativa {$index}", $root);
        }

        $this->actingAs($this->author)
            ->get(route('my-ideas.index'))
            ->assertOk()
            ->assertSee('Raíz del programa de mejora')
            ->assertSee('Subidea operativa 10')
            ->assertSee('Idea madre');
    }

    public function test_community_lists_only_mother_cards_and_supports_dimension_facets(): void
    {
        $institutional = $this->publishedIdea('Ecosistema institucional conectado');
        $institutional->categories()->sync([$this->primaryCategory->id, $this->institutionalScope->id]);

        $child = $this->publishedIdea('Microidea representada', 'represented_by_parent', $institutional);
        $child->categories()->sync([$this->primaryCategory->id, $this->institutionalScope->id]);

        $regional = $this->publishedIdea('Laboratorio regional independiente');
        $regional->categories()->sync([$this->primaryCategory->id, $this->regionalScope->id]);

        $this->actingAs($this->author)
            ->get(route('ideas.index'))
            ->assertOk()
            ->assertSee($institutional->title)
            ->assertSee($regional->title)
            ->assertDontSee($child->title)
            ->assertSee('1 microidea trazable');

        $this->actingAs($this->author)
            ->get(route('ideas.index', [
                'facetas' => [$this->scopeDimension->slug => [$this->institutionalScope->slug]],
            ]))
            ->assertOk()
            ->assertSee($institutional->title)
            ->assertDontSee($regional->title)
            ->assertDontSee($child->title);
    }

    public function test_creation_and_editing_forms_render_parent_and_multidimensional_fields(): void
    {
        $parent = $this->privateIdea('Idea disponible como madre');

        $this->actingAs($this->author)
            ->get(route('ideas.create'))
            ->assertOk()
            ->assertSee('Idea madre')
            ->assertSee('Clasificación multidimensional')
            ->assertSee('Clasifica una vez, conecta muchas ideas')
            ->assertSee('Usa entre 4 y 7 términos concretos')
            ->assertSee($this->scopeDimension->name)
            ->assertSee($parent->title);

        $this->actingAs($this->author)
            ->get(route('ideas.edit', $parent))
            ->assertOk()
            ->assertSee('Idea madre')
            ->assertSee('Clasifica una vez, conecta muchas ideas')
            ->assertSee($this->institutionalScope->name);
    }

    public function test_admin_taxonomy_and_editorial_views_render_new_management_surfaces(): void
    {
        $idea = $this->privateIdea('Idea pendiente de curaduría editorial');
        $idea->update([
            'publication_status' => 'pending_review',
            'publication_requested_at' => now(),
            'publication_requested_by_user_id' => $this->author->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Taxonomía multidimensional')
            ->assertSee('Criterios para mantener una taxonomía útil')
            ->assertSee('debería servir para cinco ideas')
            ->assertSee($this->scopeDimension->name)
            ->assertSee('Nuevo término');

        $this->actingAs($this->admin)
            ->get(route('admin.ideas.index', ['publicacion' => 'pending_review']))
            ->assertOk()
            ->assertSee($idea->title)
            ->assertSee('Decisión editorial')
            ->assertSee('Subidea, se muestra dentro de su madre');
    }

    private function term(string $name): Category
    {
        return Category::create([
            'category_dimension_id' => $this->scopeDimension->id,
            'name' => $name,
            'icon' => 'label',
            'color' => '#005696',
        ]);
    }

    private function privateIdea(string $title, ?Idea $parent = null): Idea
    {
        $idea = Idea::factory()->for($this->author)->create([
            'category_id' => $this->primaryCategory->id,
            'parent_idea_id' => $parent?->id,
            'title' => $title,
            'visibility' => 'private',
            'workspace_status' => 'capturada',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
        ]);
        $idea->categories()->sync([$this->primaryCategory->id]);

        return $idea;
    }

    private function publishedIdea(string $title, string $display = 'standalone', ?Idea $parent = null): Idea
    {
        return Idea::factory()->for($this->author)->create([
            'category_id' => $this->primaryCategory->id,
            'parent_idea_id' => $parent?->id,
            'title' => $title,
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => $display,
            'published_at' => now(),
        ]);
    }
}
