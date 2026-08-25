<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Regional;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $regional = Regional::create([
            'code' => 'ONA',
            'name' => 'Oficina Nacional',
            'order' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'regional_id' => $regional->id,
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'regional_id' => $regional->id,
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Tecnología',
            'slug' => 'tecnologia',
            'icon' => 'memory',
            'color' => '#231fb5',
            'description' => 'Soluciones digitales.',
        ]);
    }

    public function test_admin_can_view_tags_index(): void
    {
        Tag::create(['name' => 'Robótica', 'slug' => 'robotica']);
        Tag::create(['name' => 'IA', 'slug' => 'ia']);

        $response = $this->actingAs($this->admin)->get(route('admin.tags.index'));

        $response->assertStatus(200);
        $response->assertSee('Robótica');
        $response->assertSee('IA');
    }

    public function test_regular_user_cannot_access_admin_tags(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.tags.index'));
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_update_and_rename_tag(): void
    {
        $tag = Tag::create(['name' => 'Robotika', 'slug' => 'robotika']);

        $response = $this->actingAs($this->admin)->put(route('admin.tags.update', $tag->id), [
            'name' => 'Robótica Avanzada',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tag->refresh();
        $this->assertEquals('Robótica Avanzada', $tag->name);
        $this->assertEquals('robotica-avanzada', $tag->slug);
    }

    public function test_admin_renaming_to_existing_tag_triggers_automatic_fusion(): void
    {
        $targetTag = Tag::create(['name' => 'Inteligencia Artificial', 'slug' => 'inteligencia-artificial']);
        $typoTag = Tag::create(['name' => 'Inteligencia Artifical', 'slug' => 'inteligencia-artifical']);

        // Create an idea associated with typo tag
        $idea = Idea::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
        ]);
        $idea->tags()->attach($typoTag->id);

        $this->assertEquals(1, $typoTag->ideas()->count());
        $this->assertEquals(0, $targetTag->ideas()->count());

        // Admin fixes the typo tag to match the target tag's name
        $response = $this->actingAs($this->admin)->put(route('admin.tags.update', $typoTag->id), [
            'name' => 'Inteligencia Artificial',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify typo tag was removed and idea was re-linked to target tag
        $this->assertDatabaseMissing('tags', ['id' => $typoTag->id]);
        $this->assertDatabaseHas('tags', ['id' => $targetTag->id]);
        $this->assertEquals(1, $targetTag->ideas()->count());
        $this->assertTrue($idea->fresh()->tags->contains('id', $targetTag->id));
    }

    public function test_admin_can_manually_merge_tags(): void
    {
        $sourceTag = Tag::create(['name' => 'Sensores', 'slug' => 'sensores']);
        $targetTag = Tag::create(['name' => 'Sensores IoT', 'slug' => 'sensores-iot']);

        $idea = Idea::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
        ]);
        $idea->tags()->attach($sourceTag->id);

        $response = $this->actingAs($this->admin)->post(route('admin.tags.merge'), [
            'source_tag_id' => $sourceTag->id,
            'target_tag_id' => $targetTag->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('tags', ['id' => $sourceTag->id]);
        $this->assertTrue($idea->fresh()->tags->contains('id', $targetTag->id));
    }
}
