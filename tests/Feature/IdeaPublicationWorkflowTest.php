<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaPublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $otherUser;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['role' => 'user']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->category = Category::create([
            'name' => 'Tecnología',
            'slug' => 'tecnologia',
            'icon' => 'memory',
            'color' => '#231fb5',
        ]);
    }

    public function test_new_ideas_are_saved_in_private_workspace_instead_of_being_published_directly(): void
    {
        $response = $this->actingAs($this->author)->post(route('ideas.store'), [
            'title' => 'Mapa institucional de ideas',
            'description' => 'Organizar las ideas institucionales mediante relaciones verificadas.',
            'category_id' => $this->category->id,
            'visibility' => 'private',
        ]);

        $response->assertRedirect();

        $idea = Idea::where('title', 'Mapa institucional de ideas')->firstOrFail();
        $this->assertSame('private', $idea->visibility);
        $this->assertSame('capturada', $idea->workspace_status);
        $this->assertSame('not_submitted', $idea->publication_status);
        $this->assertSame('hidden', $idea->community_display);
        $this->assertFalse($idea->isPublished());
        $this->assertDatabaseHas('idea_status_histories', [
            'idea_id' => $idea->id,
            'workflow' => 'workspace',
            'new_status' => 'capturada',
        ]);
    }

    public function test_regular_user_cannot_publish_directly_from_creation_form(): void
    {
        $response = $this->actingAs($this->author)->post(route('ideas.store'), [
            'title' => 'Publicación directa no permitida',
            'description' => 'Esta propuesta debe pasar primero por una revisión editorial humana.',
            'category_id' => $this->category->id,
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors('visibility');
        $this->assertDatabaseMissing('ideas', ['title' => 'Publicación directa no permitida']);
    }

    public function test_author_can_request_publication_and_idea_remains_private_while_pending(): void
    {
        $idea = $this->privateIdea();

        $response = $this->actingAs($this->author)
            ->post(route('ideas.publication.request', $idea));

        $response->assertRedirect();
        $idea->refresh();

        $this->assertSame('pending_review', $idea->publication_status);
        $this->assertSame('private', $idea->visibility);
        $this->assertSame($this->author->id, $idea->publication_requested_by_user_id);
        $this->assertNotNull($idea->publication_requested_at);
        $this->assertFalse(Idea::communityPublished()->whereKey($idea)->exists());
    }

    public function test_another_user_cannot_view_or_request_publication_for_private_idea(): void
    {
        $idea = $this->privateIdea();

        $this->actingAs($this->otherUser)
            ->get(route('ideas.show', $idea->slug))
            ->assertForbidden();

        $this->actingAs($this->otherUser)
            ->post(route('ideas.publication.request', $idea))
            ->assertForbidden();
    }

    public function test_admin_can_publish_standalone_idea_and_activate_community_lifecycle(): void
    {
        $idea = $this->privateIdea();
        $this->actingAs($this->author)->post(route('ideas.publication.request', $idea));

        $response = $this->actingAs($this->admin)
            ->put(route('admin.ideas.publication.update', $idea), [
                'publication_status' => 'published',
                'community_display' => 'standalone',
                'publication_notes' => 'Aprobada por su alcance institucional.',
            ]);

        $response->assertRedirect();
        $idea->refresh();

        $this->assertSame('published', $idea->publication_status);
        $this->assertSame('standalone', $idea->community_display);
        $this->assertSame('public', $idea->visibility);
        $this->assertSame($this->admin->id, $idea->publication_reviewed_by_user_id);
        $this->assertTrue($idea->usesCommunityLifecycle());
        $this->assertTrue(Idea::communityPublished()->whereKey($idea)->exists());
    }

    public function test_editorial_form_starts_neutral_and_does_not_allow_changing_hierarchy_representation(): void
    {
        $parent = $this->privateIdea();
        $child = $this->privateIdea();

        $this->actingAs($this->author)->post(route('ideas.publication.request', $parent));
        $this->actingAs($this->admin)
            ->put(route('admin.ideas.publication.update', $parent), [
                'publication_status' => 'published',
                'community_display' => 'represented_by_parent',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($this->author)->put(route('ideas.hierarchy.update', $child), [
            'parent_idea_id' => $parent->id,
        ]);
        $this->actingAs($this->author)->post(route('ideas.publication.request', $child));

        $this->actingAs($this->admin)
            ->put(route('admin.ideas.publication.update', $child), [
                'publication_status' => 'published',
                'community_display' => 'standalone',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('standalone', $parent->fresh()->community_display);
        $this->assertSame('represented_by_parent', $child->fresh()->community_display);

        $this->actingAs($this->admin)
            ->get(route('admin.ideas.index'))
            ->assertOk()
            ->assertSee('<option value="">Sin decisión</option>', false)
            ->assertDontSee('name="community_display"', false)
            ->assertSee('Se deriva automáticamente de la jerarquía definida por el autor');
    }

    public function test_empty_editorial_decision_is_rejected_without_mutating_the_idea(): void
    {
        $idea = $this->privateIdea();
        $this->actingAs($this->author)->post(route('ideas.publication.request', $idea));

        $this->actingAs($this->admin)
            ->put(route('admin.ideas.publication.update', $idea), [
                'publication_status' => '',
            ])
            ->assertSessionHasErrors('publication_status');

        $this->assertSame('pending_review', $idea->fresh()->publication_status);
    }

    public function test_published_child_can_be_represented_by_a_published_parent_without_appearing_as_a_community_card(): void
    {
        $parent = $this->privateIdea();
        $child = $this->privateIdea();

        $this->actingAs($this->author)->post(route('ideas.publication.request', $parent));
        $this->actingAs($this->admin)
            ->put(route('admin.ideas.publication.update', $parent), [
                'publication_status' => 'published',
                'community_display' => 'standalone',
            ]);

        $this->actingAs($this->author)->put(route('ideas.hierarchy.update', $child), [
            'parent_idea_id' => $parent->id,
        ]);
        $this->actingAs($this->author)->post(route('ideas.publication.request', $child));

        $this->actingAs($this->admin)
            ->put(route('admin.ideas.publication.update', $child), [
                'publication_status' => 'published',
                'community_display' => 'represented_by_parent',
            ])
            ->assertRedirect();

        $child->refresh();

        $this->assertTrue(Idea::published()->whereKey($child)->exists());
        $this->assertFalse(Idea::communityPublished()->whereKey($child)->exists());
    }

    public function test_private_workspace_status_changes_without_advancing_community_lifecycle(): void
    {
        $idea = $this->privateIdea();

        $response = $this->actingAs($this->author)->put(route('ideas.update', $idea), [
            'title' => $idea->title,
            'description' => $idea->description,
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'workspace_status' => 'en_ejecucion',
        ]);

        $response->assertRedirect(route('ideas.show', $idea->slug));
        $idea->refresh();

        $this->assertSame('en_ejecucion', $idea->workspace_status);
        $this->assertSame('nueva', $idea->status);
        $this->assertDatabaseHas('idea_status_histories', [
            'idea_id' => $idea->id,
            'workflow' => 'workspace',
            'old_status' => 'capturada',
            'new_status' => 'en_ejecucion',
        ]);
    }

    public function test_admin_only_changes_community_lifecycle_after_publication(): void
    {
        $privateIdea = $this->privateIdea();

        $this->actingAs($this->admin)->put(route('admin.ideas.update', $privateIdea), [
            'status' => 'priorizada',
        ]);

        $this->assertSame('nueva', $privateIdea->fresh()->status);

        $this->actingAs($this->author)->post(route('ideas.publication.request', $privateIdea));
        $this->actingAs($this->admin)->put(route('admin.ideas.publication.update', $privateIdea), [
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);

        $this->actingAs($this->admin)->put(route('admin.ideas.update', $privateIdea), [
            'status' => 'priorizada',
            'status_comment' => 'Priorizada después de su publicación.',
        ]);

        $this->assertSame('priorizada', $privateIdea->fresh()->status);
        $this->assertDatabaseHas('idea_status_histories', [
            'idea_id' => $privateIdea->id,
            'workflow' => 'community',
            'new_status' => 'priorizada',
        ]);
    }

    private function privateIdea(): Idea
    {
        return Idea::factory()->for($this->author)->create([
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'workspace_status' => 'capturada',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
        ]);
    }
}
