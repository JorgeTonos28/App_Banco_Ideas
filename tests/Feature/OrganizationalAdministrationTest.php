<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_build_the_three_level_organizational_tree(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.regionals.store'), [
            'type' => 'regional',
            'code' => 'ona',
            'name' => 'Oficina Nacional',
            'order' => 1,
        ])->assertRedirect();

        $regional = Regional::where('code', 'ONA')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.regionals.store'), [
            'type' => 'direction',
            'parent_id' => $regional->id,
            'code' => 'DFIN',
            'name' => 'Dirección Financiera',
            'order' => 1,
        ])->assertRedirect();

        $direction = Regional::where('code', 'DFIN')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.regionals.store'), [
            'type' => 'department',
            'parent_id' => $direction->id,
            'code' => 'CONT',
            'name' => 'Departamento de Contabilidad',
            'order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('regionals', [
            'code' => 'CONT',
            'type' => 'department',
            'parent_id' => $direction->id,
        ]);
    }

    public function test_invalid_parent_types_and_cycles_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$regional, $direction, $department] = $this->organizationTree();

        $this->actingAs($admin)->post(route('admin.regionals.store'), [
            'type' => 'direction',
            'parent_id' => $direction->id,
            'code' => 'DTEC',
            'name' => 'Dirección de Tecnología',
        ])->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)->put(route('admin.regionals.update', $regional), [
            'type' => 'department',
            'parent_id' => $department->id,
            'code' => $regional->code,
            'name' => $regional->name,
            'order' => $regional->order,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_units_with_members_or_children_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$regional, $direction, $department] = $this->organizationTree();
        User::factory()->create(['organizational_unit_id' => $department->id]);

        $this->actingAs($admin)
            ->delete(route('admin.regionals.destroy', $direction))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->delete(route('admin.regionals.destroy', $department))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('regionals', ['id' => $regional->id]);
        $this->assertDatabaseHas('regionals', ['id' => $direction->id]);
        $this->assertDatabaseHas('regionals', ['id' => $department->id]);
    }

    public function test_admin_user_assignment_keeps_the_specific_unit_and_its_regional_root(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$regional, , $department] = $this->organizationTree();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'creation_type' => 'direct',
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@infotep.gob.do',
            'password' => 'Password123',
            'role' => 'user',
            'organizational_unit_id' => $department->id,
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'ana.perez@infotep.gob.do')->firstOrFail();

        $this->assertSame($department->id, $user->organizational_unit_id);
        $this->assertSame($regional->id, $user->regional_id);
    }

    public function test_internal_profile_contributions_respect_the_viewers_organization(): void
    {
        [$regional, $direction, $department] = $this->organizationTree();
        $author = User::factory()->create([
            'regional_id' => $regional->id,
            'organizational_unit_id' => $direction->id,
        ]);
        $allowedViewer = User::factory()->create(['organizational_unit_id' => $department->id]);
        $outsideViewer = User::factory()->create();

        $idea = Idea::factory()->for($author)->create([
            'title' => 'Tablero interno de conciliación',
            'visibility' => 'private',
            'access_scope' => 'organization',
        ]);
        $idea->communityUnits()->attach($direction->id, ['include_descendants' => true]);

        $this->actingAs($allowedViewer)
            ->get(route('profile.show', $author))
            ->assertOk()
            ->assertSee($idea->title);

        $this->actingAs($outsideViewer)
            ->get(route('profile.show', $author))
            ->assertOk()
            ->assertDontSee($idea->title);
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
