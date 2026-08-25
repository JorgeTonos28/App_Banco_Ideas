<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIdeaHierarchyNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_management_lists_one_hierarchy_level_and_drills_into_direct_children(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $root = Idea::factory()->create(['title' => 'Programa madre de transformación']);
        $otherRoot = Idea::factory()->create(['title' => 'Programa madre independiente']);
        $child = Idea::factory()->create([
            'parent_idea_id' => $root->id,
            'title' => 'Frente operativo digital',
        ]);
        $grandchild = Idea::factory()->create([
            'parent_idea_id' => $child->id,
            'title' => 'Validación de la microidea',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ideas.index'))
            ->assertOk()
            ->assertSee($root->title)
            ->assertSee($otherRoot->title)
            ->assertDontSee($child->title)
            ->assertSee(route('admin.ideas.index', ['parent' => $root->id]), false)
            ->assertSee('Ver ficha');

        $this->actingAs($admin)
            ->get(route('admin.ideas.index', ['parent' => $root->id]))
            ->assertOk()
            ->assertSee($child->title)
            ->assertDontSee($otherRoot->title)
            ->assertDontSee($grandchild->title)
            ->assertSee('Volver al nivel anterior');
    }

    public function test_admin_search_filters_the_current_level_automatically_and_ignores_spaces(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matching = Idea::factory()->create(['title' => 'Control presupuestario institucional']);
        $other = Idea::factory()->create(['title' => 'Programa de mentoría']);

        $this->actingAs($admin)
            ->get(route('admin.ideas.index', ['q' => 'controlpresupuestario']))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee($other->title)
            ->assertSee('@input.debounce.450ms="$refs.adminIdeaFilters.requestSubmit()"', false);
    }
}
