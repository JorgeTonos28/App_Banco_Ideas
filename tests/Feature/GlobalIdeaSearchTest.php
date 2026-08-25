<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalIdeaSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_only_returns_the_users_own_ideas_and_published_community_ideas(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $ownPrivate = Idea::factory()->for($viewer)->create(['title' => 'Mapa estratégico privado']);
        $otherPrivate = Idea::factory()->for($other)->create(['title' => 'Mapa estratégico ajeno']);
        $published = Idea::factory()->for($other)->create([
            'title' => 'Mapa estratégico comunitario',
            'publication_status' => 'published',
            'visibility' => 'public',
            'community_display' => 'standalone',
        ]);

        $ideas = $this->actingAs($viewer)
            ->getJson(route('api.search', ['q' => 'mapaestrategico']))
            ->assertOk()
            ->json('ideas');

        $ids = collect($ideas)->pluck('id');
        $this->assertTrue($ids->contains($ownPrivate->id));
        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($otherPrivate->id));
        $this->assertSame('Tu idea', collect($ideas)->firstWhere('id', $ownPrivate->id)['context']);
        $this->assertSame('Comunidad', collect($ideas)->firstWhere('id', $published->id)['context']);
    }

    public function test_search_ignores_spaces_and_covers_description_category_and_tags(): void
    {
        $viewer = User::factory()->create();
        $category = Category::create([
            'name' => 'Gestión Financiera',
            'slug' => 'gestion-financiera',
            'icon' => 'payments',
            'color' => '#003e6f',
        ]);
        $tag = Tag::create(['name' => 'Cierre Mensual', 'slug' => 'cierre-mensual']);
        $idea = Idea::factory()->for($viewer)->create([
            'category_id' => $category->id,
            'title' => 'Conciliación automática',
            'description' => 'Reduce errores en las facturas institucionales.',
        ]);
        $idea->tags()->attach($tag);

        foreach (['fac tu ras', 'gestionfinanciera', 'c i e r r e m e n s u a l'] as $query) {
            $this->actingAs($viewer)
                ->getJson(route('api.search', ['q' => $query]))
                ->assertOk()
                ->assertJsonFragment(['id' => $idea->id]);
        }
    }

    public function test_search_requires_two_actual_characters_and_authentication(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->getJson(route('api.search', ['q' => '  a  ']))
            ->assertOk()
            ->assertExactJson([
                'ideas' => [],
                'people' => [],
                'categories' => [],
                'tags' => [],
            ]);

        auth()->logout();
        $this->getJson(route('api.search', ['q' => 'idea']))->assertUnauthorized();
    }

    public function test_search_query_length_is_bounded(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->getJson(route('api.search', ['q' => str_repeat('a', 121)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }
}
