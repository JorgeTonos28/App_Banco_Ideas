<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalCommunityModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizational_units_expose_their_path_and_descendants(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();

        $this->assertSame(
            [$regional->id, $direction->id, $department->id],
            $department->ancestorAndSelfIds()->all()
        );
        $this->assertSame(
            [$direction->id, $department->id],
            $regional->descendantIds()->sort()->values()->all()
        );
        $this->assertSame(
            'Oficina Nacional / Dirección Financiera / Departamento de Contabilidad',
            $department->path_label
        );
    }

    public function test_an_internal_share_can_include_dependent_communities(): void
    {
        [, $direction, $department] = $this->organizationTree();

        $owner = User::factory()->create(['organizational_unit_id' => $department->id]);
        $directionMember = User::factory()->create(['organizational_unit_id' => $direction->id]);
        $departmentMember = User::factory()->create(['organizational_unit_id' => $department->id]);

        $idea = Idea::factory()->for($owner)->create([
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $idea->communityUnits()->attach($direction->id, [
            'include_descendants' => true,
            'shared_by_user_id' => $owner->id,
        ]);

        $this->assertTrue($idea->isSharedWithOrganization($directionMember));
        $this->assertTrue($idea->isSharedWithOrganization($departmentMember));
    }

    public function test_an_exact_internal_share_does_not_leak_to_lower_levels(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $otherRegional = Regional::create([
            'type' => 'regional',
            'code' => 'DRN',
            'name' => 'Regional Norte',
            'is_active' => true,
            'order' => 2,
        ]);

        $owner = User::factory()->create(['organizational_unit_id' => $direction->id]);
        $directionMember = User::factory()->create(['organizational_unit_id' => $direction->id]);
        $departmentMember = User::factory()->create(['organizational_unit_id' => $department->id]);
        $outsider = User::factory()->create(['organizational_unit_id' => $otherRegional->id]);

        $idea = Idea::factory()->for($owner)->create([
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $idea->communityUnits()->attach($direction->id, [
            'include_descendants' => false,
            'shared_by_user_id' => $owner->id,
        ]);

        $this->assertTrue($idea->isSharedWithOrganization($directionMember));
        $this->assertFalse($idea->isSharedWithOrganization($departmentMember));
        $this->assertFalse($idea->isSharedWithOrganization($outsider));
        $this->assertTrue($regional->is($direction->parent));
    }

    public function test_microideas_inherit_the_internal_audience_of_the_root(): void
    {
        [, $direction, $department] = $this->organizationTree();
        $owner = User::factory()->create(['organizational_unit_id' => $department->id]);
        $viewer = User::factory()->create(['organizational_unit_id' => $department->id]);

        $root = Idea::factory()->for($owner)->create([
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $root->communityUnits()->attach($direction->id, ['include_descendants' => true]);

        $child = Idea::factory()->for($owner)->create([
            'parent_idea_id' => $root->id,
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);

        $this->assertTrue($child->isAccessibleToAuthenticatedAudience($viewer));
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
