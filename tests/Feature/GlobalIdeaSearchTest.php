<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Regional;
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
        $this->assertSame('Comunidad general', collect($ideas)->firstWhere('id', $published->id)['context']);
    }

    public function test_search_includes_profile_and_authorized_internal_ideas_without_leaking_other_audiences(): void
    {
        $unit = Regional::create([
            'type' => 'department',
            'code' => 'CONT',
            'name' => 'Contabilidad',
            'is_active' => true,
            'order' => 1,
        ]);
        $otherUnit = Regional::create([
            'type' => 'department',
            'code' => 'COMP',
            'name' => 'Compras',
            'is_active' => true,
            'order' => 2,
        ]);
        $viewer = User::factory()->create(['organizational_unit_id' => $unit->id]);
        $author = User::factory()->create();
        $profileIdea = Idea::factory()->for($author)->create([
            'title' => 'Control presupuestario compartido',
            'visibility' => 'private',
            'access_scope' => 'profile',
        ]);
        $internalIdea = Idea::factory()->for($author)->create([
            'title' => 'Control presupuestario departamental',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $internalIdea->communityUnits()->attach($unit->id, ['include_descendants' => false]);
        $restrictedIdea = Idea::factory()->for($author)->create([
            'title' => 'Control presupuestario reservado',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $restrictedIdea->communityUnits()->attach($otherUnit->id, ['include_descendants' => false]);

        $ideas = $this->actingAs($viewer)
            ->getJson(route('api.search', ['q' => 'controlpresupuestario']))
            ->assertOk()
            ->json('ideas');

        $ids = collect($ideas)->pluck('id');
        $this->assertTrue($ids->contains($profileIdea->id));
        $this->assertTrue($ids->contains($internalIdea->id));
        $this->assertFalse($ids->contains($restrictedIdea->id));
        $this->assertSame('Perfil visible', collect($ideas)->firstWhere('id', $profileIdea->id)['context']);
        $this->assertSame('Comunidad interna', collect($ideas)->firstWhere('id', $internalIdea->id)['context']);
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
