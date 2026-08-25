<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalCommunityNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_entry_opens_the_users_most_specific_level(): void
    {
        [, , $department] = $this->organizationTree();
        $user = User::factory()->create(['organizational_unit_id' => $department->id]);

        $this->actingAs($user)
            ->get(route('community'))
            ->assertRedirect(route('community', ['nivel' => $department->id]));
    }

    public function test_a_user_can_navigate_their_path_but_not_an_unrelated_structure(): void
    {
        [, $direction, $department] = $this->organizationTree();
        $otherRegional = Regional::create([
            'type' => 'regional',
            'code' => 'DRN',
            'name' => 'Regional Norte',
            'is_active' => true,
            'order' => 2,
        ]);
        $user = User::factory()->create(['organizational_unit_id' => $department->id]);

        $this->actingAs($user)
            ->get(route('community', ['nivel' => $direction->id]))
            ->assertOk()
            ->assertSee('Dirección Financiera')
            ->assertSee('Departamento de Contabilidad');

        $this->actingAs($user)
            ->get(route('community', ['nivel' => $otherRegional->id]))
            ->assertForbidden();
    }

    public function test_each_internal_community_only_lists_ideas_enabled_for_that_audience(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $owner = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $direction->id,
        ]);
        $departmentViewer = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
        ]);

        $visibleIdea = Idea::factory()->for($owner)->create([
            'title' => 'Idea habilitada para niveles dependientes',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $visibleIdea->communityUnits()->attach($direction->id, ['include_descendants' => true]);

        $exactIdea = Idea::factory()->for($owner)->create([
            'title' => 'Idea reservada a miembros directos',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $exactIdea->communityUnits()->attach($direction->id, ['include_descendants' => false]);

        $privateIdea = Idea::factory()->for($owner)->create([
            'title' => 'Idea completamente privada',
            'visibility' => 'private',
            'access_scope' => 'only_me',
        ]);

        $this->actingAs($departmentViewer)
            ->get(route('community', ['nivel' => $department->id]))
            ->assertOk()
            ->assertSee($visibleIdea->title)
            ->assertDontSee($exactIdea->title)
            ->assertDontSee($privateIdea->title);
    }

    public function test_general_level_remains_the_editorially_approved_community(): void
    {
        $user = User::factory()->create();
        $published = Idea::factory()->create([
            'title' => 'Idea publicada para todo INFOTEP',
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);
        $private = Idea::factory()->create([
            'title' => 'Idea de trabajo sin aprobación',
            'visibility' => 'private',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
        ]);

        $this->actingAs($user)
            ->get(route('community', ['nivel' => 'general']))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($private->title);
    }

    public function test_community_search_is_live_and_scoped_to_the_current_level(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $viewer = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $department->id,
        ]);
        $visible = Idea::factory()->create([
            'title' => 'Conciliación de inventario digital',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $visible->communityUnits()->attach($direction->id, ['include_descendants' => true]);
        $outside = Idea::factory()->create([
            'title' => 'Conciliación de inventario externa',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $outside->communityUnits()->attach($regional->id, ['include_descendants' => false]);

        $this->actingAs($viewer)
            ->get(route('community', ['nivel' => $department->id, 'q' => 'inventariodigital']))
            ->assertOk()
            ->assertSee($visible->title)
            ->assertDontSee($outside->title)
            ->assertSee('@input.debounce.450ms="$refs.communitySearchForm.requestSubmit()"', false);
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
