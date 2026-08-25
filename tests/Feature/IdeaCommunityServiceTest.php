<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Regional;
use App\Models\User;
use App\Services\IdeaCommunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaCommunityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_author_can_share_a_root_idea_with_an_internal_community(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $author = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
        ]);
        $category = $this->category();

        $response = $this->actingAs($author)->post(route('ideas.store'), [
            'title' => 'Conciliación digital de facturas',
            'description' => 'Automatizar la conciliación de facturas para reducir errores y tiempos de validación.',
            'category_id' => $category->id,
            'visibility' => 'private',
            'access_scope' => 'organization',
            'organizational_unit_id' => $direction->id,
            'include_descendants' => '1',
        ]);

        $idea = Idea::where('title', 'Conciliación digital de facturas')->firstOrFail();

        $response->assertRedirect(route('ideas.show', $idea->slug));
        $this->assertDatabaseHas('idea_community_shares', [
            'idea_id' => $idea->id,
            'organizational_unit_id' => $direction->id,
            'include_descendants' => true,
            'shared_by_user_id' => $author->id,
        ]);
    }

    public function test_an_author_cannot_share_into_an_unrelated_community(): void
    {
        [$regional, , $department] = $this->organizationTree();
        $otherRegional = Regional::create([
            'type' => 'regional',
            'code' => 'DRS',
            'name' => 'Regional Sur',
            'is_active' => true,
            'order' => 2,
        ]);
        $author = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
        ]);

        $this->actingAs($author)->post(route('ideas.store'), [
            'title' => 'Conciliación digital de facturas',
            'description' => 'Automatizar la conciliación de facturas para reducir errores y tiempos de validación.',
            'category_id' => $this->category()->id,
            'visibility' => 'private',
            'access_scope' => 'organization',
            'organizational_unit_id' => $otherRegional->id,
        ])->assertSessionHasErrors('organizational_unit_id');

        $this->assertDatabaseMissing('ideas', ['title' => 'Conciliación digital de facturas']);
    }

    public function test_community_query_respects_exact_and_descendant_audiences(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $owner = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $direction->id,
        ]);
        $directionViewer = User::factory()->create(['organizational_unit_id' => $direction->id]);
        $departmentViewer = User::factory()->create(['organizational_unit_id' => $department->id]);

        $exactIdea = Idea::factory()->for($owner)->create([
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $exactIdea->communityUnits()->attach($direction->id, ['include_descendants' => false]);

        $broadIdea = Idea::factory()->for($owner)->create([
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $broadIdea->communityUnits()->attach($direction->id, ['include_descendants' => true]);

        $service = app(IdeaCommunityService::class);

        $this->assertEqualsCanonicalizing(
            [$exactIdea->id, $broadIdea->id],
            $service->ideasForUnit($direction, $directionViewer)->pluck('ideas.id')->all()
        );
        $this->assertSame(
            [$broadIdea->id],
            $service->ideasForUnit($department, $departmentViewer)->pluck('ideas.id')->all()
        );
    }

    public function test_hierarchy_changes_define_the_requested_community_representation(): void
    {
        $author = User::factory()->create();
        $root = Idea::factory()->for($author)->create();
        $child = Idea::factory()->for($author)->create();

        $this->actingAs($author)->put(route('ideas.hierarchy.update', $child), [
            'parent_idea_id' => $root->id,
        ])->assertRedirect();

        $this->assertSame('represented_by_parent', $child->fresh()->requested_community_display);

        $this->actingAs($author)->put(route('ideas.hierarchy.update', $child), [
            'parent_idea_id' => null,
        ])->assertRedirect();

        $this->assertSame('standalone', $child->fresh()->requested_community_display);
    }

    public function test_an_internal_child_of_a_published_mother_keeps_its_audience_and_requires_its_own_approval(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $author = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
        ]);
        $outsider = User::factory()->create();
        $parent = Idea::factory()->for($author)->create([
            'title' => 'Programa institucional publicado',
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);

        $this->actingAs($author)->post(route('ideas.store'), [
            'title' => 'Validación contable de la microidea',
            'description' => 'Validar el componente contable sólo con la comunidad interna antes de solicitar publicación general.',
            'category_id' => $this->category()->id,
            'visibility' => 'private',
            'access_scope' => 'organization',
            'organizational_unit_id' => $direction->id,
            'include_descendants' => '1',
            'parent_idea_id' => $parent->id,
        ])->assertRedirect();

        $child = Idea::where('title', 'Validación contable de la microidea')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_idea_id);
        $this->assertSame('not_submitted', $child->publication_status);
        $this->assertSame('hidden', $child->community_display);
        $this->assertDatabaseHas('idea_community_shares', [
            'idea_id' => $child->id,
            'organizational_unit_id' => $direction->id,
            'include_descendants' => true,
        ]);
        $this->assertFalse(Idea::communityPublished()->whereKey($child)->exists());

        $this->actingAs($outsider)
            ->get(route('ideas.show', $child->slug))
            ->assertForbidden();

        $editResponse = $this->actingAs($author)->get(route('ideas.edit', $child));
        $editResponse->assertOk();
        $this->assertStringContainsString(
            '<option value="'.$direction->id.'" selected>',
            $editResponse->getContent()
        );
    }

    public function test_my_ideas_separates_internal_shares_from_the_personal_workspace(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $author = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
        ]);
        $personal = Idea::factory()->for($author)->create([
            'title' => 'Idea exclusiva del espacio personal',
            'visibility' => 'private',
            'access_scope' => 'only_me',
        ]);
        $internal = Idea::factory()->for($author)->create([
            'title' => 'Idea para la comunidad de contabilidad',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $internal->communityUnits()->attach($direction->id, ['include_descendants' => true]);

        $this->actingAs($author)
            ->get(route('my-ideas.index'))
            ->assertOk()
            ->assertSee($personal->title)
            ->assertDontSee($internal->title);

        $this->actingAs($author)
            ->get(route('my-ideas.index', ['tab' => 'internas']))
            ->assertOk()
            ->assertSee($internal->title)
            ->assertDontSee($personal->title);
    }

    private function category(): Category
    {
        return Category::create([
            'name' => 'Procesos',
            'slug' => 'procesos',
            'icon' => 'account_tree',
            'color' => '#003e6f',
        ]);
    }

    private function organizationTree(): array
    {
        $regional = Regional::create([
            'type' => 'regional',
            'code' => 'ONA',
            'name' => 'Oficina Nacional',
            'is_active' => true,
            'order' => 1,
        ]);
        $direction = Regional::create([
            'parent_id' => $regional->id,
            'type' => 'direction',
            'code' => 'DFIN',
            'name' => 'Dirección Financiera',
            'is_active' => true,
            'order' => 1,
        ]);
        $department = Regional::create([
            'parent_id' => $direction->id,
            'type' => 'department',
            'code' => 'CONT',
            'name' => 'Departamento de Contabilidad',
            'is_active' => true,
            'order' => 1,
        ]);

        return [$regional, $direction, $department];
    }
}
