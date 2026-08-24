<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaHierarchyAndRelationTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $otherAuthor;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->otherAuthor = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->category = Category::create([
            'name' => 'Procesos',
            'slug' => 'procesos',
            'icon' => 'account_tree',
            'color' => '#005696',
        ]);
    }

    public function test_author_can_build_a_nested_hierarchy_and_every_move_is_audited(): void
    {
        $root = $this->privateIdea($this->author);
        $middle = $this->privateIdea($this->author);
        $leaf = $this->privateIdea($this->author);

        $this->actingAs($this->author)->put(route('ideas.hierarchy.update', $middle), [
            'parent_idea_id' => $root->id,
            'note' => 'Agrupa el frente operativo.',
        ])->assertRedirect();

        $this->actingAs($this->author)->put(route('ideas.hierarchy.update', $leaf), [
            'parent_idea_id' => $middle->id,
        ])->assertRedirect();

        $this->assertSame([$root->id, $middle->id], $leaf->fresh()->ancestors()->pluck('id')->all());
        $this->assertDatabaseHas('idea_hierarchy_histories', [
            'idea_id' => $middle->id,
            'old_parent_idea_id' => null,
            'new_parent_idea_id' => $root->id,
            'changed_by_user_id' => $this->author->id,
            'note' => 'Agrupa el frente operativo.',
        ]);
    }

    public function test_hierarchy_rejects_self_parenting_cycles_and_foreign_private_parents(): void
    {
        $root = $this->privateIdea($this->author);
        $child = $this->privateIdea($this->author);
        $foreign = $this->privateIdea($this->otherAuthor);

        $this->actingAs($this->author)
            ->put(route('ideas.hierarchy.update', $root), ['parent_idea_id' => $root->id])
            ->assertSessionHasErrors('parent_idea_id');

        $this->actingAs($this->author)
            ->put(route('ideas.hierarchy.update', $child), ['parent_idea_id' => $root->id])
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($this->author)
            ->put(route('ideas.hierarchy.update', $root), ['parent_idea_id' => $child->id])
            ->assertSessionHasErrors('parent_idea_id');

        $this->actingAs($this->author)
            ->put(route('ideas.hierarchy.update', $root), ['parent_idea_id' => $foreign->id])
            ->assertSessionHasErrors('parent_idea_id');

        $this->assertNull($root->fresh()->parent_idea_id);
    }

    public function test_admin_can_curate_a_hierarchy_across_authors(): void
    {
        $parent = $this->privateIdea($this->otherAuthor);
        $child = $this->privateIdea($this->author);

        $this->actingAs($this->admin)
            ->put(route('ideas.hierarchy.update', $child), ['parent_idea_id' => $parent->id])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($parent->id, $child->fresh()->parent_idea_id);
    }

    public function test_same_author_relation_is_approved_immediately_and_duplicates_are_rejected(): void
    {
        $source = $this->privateIdea($this->author);
        $target = $this->privateIdea($this->author);

        $payload = [
            'target_idea_id' => $target->id,
            'type' => 'depends_on',
            'rationale' => 'La segunda capacidad debe existir primero.',
        ];

        $this->actingAs($this->author)
            ->post(route('ideas.relations.store', $source), $payload)
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('idea_relations', [
            'source_idea_id' => $source->id,
            'target_idea_id' => $target->id,
            'type' => 'depends_on',
            'status' => 'approved',
        ]);

        $this->actingAs($this->author)
            ->post(route('ideas.relations.store', $source), $payload)
            ->assertSessionHasErrors('target_idea_id');
    }

    public function test_cross_author_relation_requires_public_ideas_and_target_author_review(): void
    {
        $privateSource = $this->privateIdea($this->author);
        $publicTarget = $this->publishedIdea($this->otherAuthor);

        $this->actingAs($this->author)
            ->post(route('ideas.relations.store', $privateSource), [
                'target_idea_id' => $publicTarget->id,
                'type' => 'complements',
            ])
            ->assertSessionHasErrors('target_idea_id');

        $publicSource = $this->publishedIdea($this->author);
        $this->actingAs($this->author)
            ->post(route('ideas.relations.store', $publicSource), [
                'target_idea_id' => $publicTarget->id,
                'type' => 'complements',
            ])
            ->assertSessionDoesntHaveErrors();

        $relation = IdeaRelation::firstOrFail();
        $this->assertSame('pending', $relation->status);

        $this->actingAs($this->otherAuthor)
            ->put(route('ideas.relations.update', $relation), ['status' => 'approved'])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('approved', $relation->fresh()->status);
        $this->assertSame($this->otherAuthor->id, $relation->fresh()->reviewed_by_user_id);
    }

    public function test_unrelated_user_cannot_review_or_delete_a_relation(): void
    {
        $source = $this->publishedIdea($this->author);
        $target = $this->publishedIdea($this->otherAuthor);
        $unrelated = User::factory()->create();

        $this->actingAs($this->author)->post(route('ideas.relations.store', $source), [
            'target_idea_id' => $target->id,
            'type' => 'related_to',
        ]);

        $relation = IdeaRelation::firstOrFail();

        $this->actingAs($unrelated)
            ->put(route('ideas.relations.update', $relation), ['status' => 'approved'])
            ->assertForbidden();

        $this->actingAs($unrelated)
            ->delete(route('ideas.relations.destroy', $relation))
            ->assertForbidden();
    }

    public function test_represented_publication_requires_a_published_community_parent_and_cannot_be_detached(): void
    {
        $parent = $this->privateIdea($this->author);
        $child = $this->privateIdea($this->author);

        $this->actingAs($this->author)->put(route('ideas.hierarchy.update', $child), [
            'parent_idea_id' => $parent->id,
        ]);
        $this->actingAs($this->author)->post(route('ideas.publication.request', $child));

        $this->actingAs($this->admin)->put(route('admin.ideas.publication.update', $child), [
            'publication_status' => 'published',
            'community_display' => 'represented_by_parent',
        ])->assertSessionHasErrors('community_display');

        $this->publishThroughWorkflow($parent);

        $this->actingAs($this->admin)->put(route('admin.ideas.publication.update', $child), [
            'publication_status' => 'published',
            'community_display' => 'represented_by_parent',
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($this->author)
            ->put(route('ideas.hierarchy.update', $child), ['parent_idea_id' => null])
            ->assertSessionHasErrors('parent_idea_id');

        $this->assertSame($parent->id, $child->fresh()->parent_idea_id);
        $this->assertFalse(Idea::communityPublished()->whereKey($child)->exists());
    }

    public function test_canonical_parent_cannot_be_deleted_while_it_represents_a_published_child(): void
    {
        $parent = $this->publishedIdea($this->author);
        $child = Idea::factory()->for($this->author)->create([
            'category_id' => $this->category->id,
            'parent_idea_id' => $parent->id,
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'represented_by_parent',
            'published_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('ideas.destroy', $parent))
            ->assertForbidden();

        $this->assertDatabaseHas('ideas', ['id' => $parent->id]);
        $this->assertSame($parent->id, $child->fresh()->parent_idea_id);
    }

    private function privateIdea(User $user): Idea
    {
        return Idea::factory()->for($user)->create([
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'workspace_status' => 'capturada',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
        ]);
    }

    private function publishedIdea(User $user): Idea
    {
        return Idea::factory()->for($user)->create([
            'category_id' => $this->category->id,
            'visibility' => 'public',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now(),
        ]);
    }

    private function publishThroughWorkflow(Idea $idea): void
    {
        $this->actingAs($idea->user)->post(route('ideas.publication.request', $idea));
        $this->actingAs($this->admin)->put(route('admin.ideas.publication.update', $idea), [
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);
    }
}
