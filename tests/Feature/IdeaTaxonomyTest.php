<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryDimension;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $admin;

    private CategoryDimension $primaryDimension;

    private Category $primaryCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->primaryDimension = CategoryDimension::where('is_primary', true)->firstOrFail();
        $this->primaryCategory = Category::create([
            'name' => 'Transformación digital',
            'icon' => 'memory',
            'color' => '#231FB5',
        ]);
    }

    public function test_legacy_category_is_assigned_to_primary_dimension_and_new_idea_syncs_pivot(): void
    {
        $this->assertSame($this->primaryDimension->id, $this->primaryCategory->category_dimension_id);

        $this->actingAs($this->author)->post(route('ideas.store'), $this->ideaPayload())
            ->assertSessionDoesntHaveErrors();

        $idea = Idea::where('title', 'Clasificación multidimensional verificable')->firstOrFail();

        $this->assertSame($this->primaryCategory->id, $idea->category_id);
        $this->assertDatabaseHas('idea_category', [
            'idea_id' => $idea->id,
            'category_id' => $this->primaryCategory->id,
        ]);
    }

    public function test_required_dimension_is_enforced_once_it_has_available_values(): void
    {
        $scope = $this->dimension('Alcance', 'single', required: true);
        Category::create([
            'category_dimension_id' => $scope->id,
            'name' => 'Institucional',
            'icon' => 'domain',
            'color' => '#005696',
        ]);

        $this->actingAs($this->author)->post(route('ideas.store'), $this->ideaPayload())
            ->assertSessionHasErrors("classifications.{$scope->id}");

        $this->assertDatabaseMissing('ideas', ['title' => 'Clasificación multidimensional verificable']);
    }

    public function test_multiple_dimensions_accept_valid_selections_and_preserve_primary_category(): void
    {
        $beneficiaries = $this->dimension('Beneficiarios', 'multiple', required: true);
        $students = $this->term($beneficiaries, 'Participantes');
        $teachers = $this->term($beneficiaries, 'Facilitadores');

        $payload = $this->ideaPayload([
            'classifications' => [
                $beneficiaries->id => [$students->id, $teachers->id],
            ],
        ]);

        $this->actingAs($this->author)->post(route('ideas.store'), $payload)
            ->assertSessionDoesntHaveErrors();

        $idea = Idea::where('title', $payload['title'])->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$this->primaryCategory->id, $students->id, $teachers->id],
            $idea->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_taxonomy_rejects_values_from_another_dimension_and_multiple_values_in_single_dimension(): void
    {
        $scope = $this->dimension('Alcance', 'single');
        $institutional = $this->term($scope, 'Institucional');
        $regional = $this->term($scope, 'Regional');
        $beneficiaries = $this->dimension('Beneficiarios', 'multiple');
        $participants = $this->term($beneficiaries, 'Participantes');

        $this->actingAs($this->author)->post(route('ideas.store'), $this->ideaPayload([
            'classifications' => [$scope->id => [$participants->id]],
        ]))->assertSessionHasErrors("classifications.{$scope->id}");

        $this->actingAs($this->author)->post(route('ideas.store'), $this->ideaPayload([
            'title' => 'Otra clasificación multidimensional',
            'classifications' => [$scope->id => [$institutional->id, $regional->id]],
        ]))->assertSessionHasErrors("classifications.{$scope->id}");
    }

    public function test_admin_can_build_category_tree_but_cycles_are_rejected(): void
    {
        $this->primaryDimension->update(['is_hierarchical' => true]);

        $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'category_dimension_id' => $this->primaryDimension->id,
            'parent_id' => $this->primaryCategory->id,
            'name' => 'Automatización de procesos',
            'icon' => 'account_tree',
            'color' => '#003E6F',
            'is_active' => '1',
        ])->assertSessionDoesntHaveErrors();

        $child = Category::where('name', 'Automatización de procesos')->firstOrFail();
        $this->assertSame('Transformación digital / Automatización de procesos', $child->path_label);

        $this->actingAs($this->admin)->put(route('admin.categories.update', $this->primaryCategory), [
            'category_dimension_id' => $this->primaryDimension->id,
            'parent_id' => $child->id,
            'name' => $this->primaryCategory->name,
            'icon' => $this->primaryCategory->icon,
            'color' => $this->primaryCategory->color,
            'is_active' => '1',
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($this->primaryCategory->fresh()->parent_id);
    }

    public function test_non_admin_cannot_mutate_taxonomy(): void
    {
        $this->actingAs($this->author)->post(route('admin.category-dimensions.store'), [
            'name' => 'Tipo de innovación',
            'selection_mode' => 'multiple',
        ])->assertRedirect(route('home'));

        $this->actingAs($this->author)->delete(route('admin.categories.destroy', $this->primaryCategory))
            ->assertRedirect(route('home'));
    }

    private function dimension(string $name, string $mode, bool $required = false): CategoryDimension
    {
        return CategoryDimension::create([
            'name' => $name,
            'selection_mode' => $mode,
            'is_required' => $required,
            'is_active' => true,
        ]);
    }

    private function term(CategoryDimension $dimension, string $name): Category
    {
        return Category::create([
            'category_dimension_id' => $dimension->id,
            'name' => $name,
            'icon' => 'label',
            'color' => '#005696',
        ]);
    }

    private function ideaPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Clasificación multidimensional verificable',
            'description' => 'Esta idea cuenta con suficiente detalle para comprobar el nuevo sistema de clasificación.',
            'category_id' => $this->primaryCategory->id,
            'visibility' => 'private',
        ], $overrides);
    }
}
