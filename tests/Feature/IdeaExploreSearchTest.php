<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaExploreSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_explore_defaults_to_all_and_filters_normalized_content_while_typing(): void
    {
        $viewer = User::factory()->create();
        $category = Category::create([
            'name' => 'Gestión Documental',
            'slug' => 'gestion-documental',
            'icon' => 'folder',
            'color' => '#003e6f',
        ]);
        $matching = Idea::factory()->create([
            'category_id' => $category->id,
            'title' => 'Archivo institucional inteligente',
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);
        $other = Idea::factory()->create([
            'title' => 'Programa de mentoría',
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);

        $this->actingAs($viewer)
            ->get(route('ideas.index', ['q' => 'gestiondocumental']))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee($other->title)
            ->assertSee('<option value="todas" selected>Todas las ideas</option>', false)
            ->assertSee('@input.debounce.450ms="$refs.exploreSearchForm.requestSubmit()"', false);
    }
}
