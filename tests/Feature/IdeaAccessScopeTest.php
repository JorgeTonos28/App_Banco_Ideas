<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $visitor;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->visitor = User::factory()->create();
        $this->category = Category::create([
            'name' => 'Transformación institucional',
            'slug' => 'transformacion-institucional',
            'icon' => 'account_tree',
            'color' => '#003e6f',
        ]);
    }

    public function test_author_can_share_a_complete_root_idea_from_their_profile_without_publishing_it(): void
    {
        $idea = $this->idea(['access_scope' => 'profile']);

        $this->actingAs($this->visitor)
            ->get(route('profile.show', $this->author))
            ->assertOk()
            ->assertSee($idea->title);

        $this->actingAs($this->visitor)
            ->get(route('ideas.show', $idea->slug))
            ->assertOk();

        $this->assertFalse($idea->isPublished());
        $this->assertFalse(Idea::communityPublished()->whereKey($idea)->exists());
    }

    public function test_only_me_ideas_remain_inaccessible_to_other_users_and_absent_from_profile(): void
    {
        $idea = $this->idea(['access_scope' => 'only_me']);

        $this->actingAs($this->visitor)
            ->get(route('profile.show', $this->author))
            ->assertOk()
            ->assertDontSee($idea->title);

        $this->actingAs($this->visitor)
            ->get(route('ideas.show', $idea->slug))
            ->assertForbidden();
    }

    public function test_shared_child_never_exceeds_the_effective_access_of_its_ancestors(): void
    {
        $root = $this->idea(['access_scope' => 'only_me']);
        $child = $this->idea([
            'parent_idea_id' => $root->id,
            'access_scope' => 'profile',
        ]);

        $this->actingAs($this->visitor)
            ->get(route('ideas.show', $child->slug))
            ->assertForbidden();

        $root->update(['access_scope' => 'profile']);

        $this->actingAs($this->visitor)
            ->get(route('ideas.show', $child->slug))
            ->assertOk();

        $child->update(['access_scope' => 'only_me']);

        $this->actingAs($this->visitor)
            ->get(route('ideas.show', $child->slug))
            ->assertForbidden();
    }

    public function test_only_root_ideas_are_listed_as_profile_contributions(): void
    {
        $root = $this->idea(['access_scope' => 'profile']);
        $child = $this->idea([
            'parent_idea_id' => $root->id,
            'access_scope' => 'profile',
        ]);

        $this->actingAs($this->visitor)
            ->get(route('profile.show', $this->author))
            ->assertOk()
            ->assertSee($root->title)
            ->assertDontSee($child->title);
    }

    public function test_draft_cannot_be_shared_on_profile(): void
    {
        $this->actingAs($this->author)
            ->post(route('ideas.store'), [
                'title' => 'Borrador todavía incompleto',
                'description' => 'Esta propuesta todavía requiere completar sus detalles institucionales.',
                'category_id' => $this->category->id,
                'visibility' => 'draft',
                'access_scope' => 'profile',
            ])
            ->assertSessionHasErrors('access_scope');

        $this->assertDatabaseMissing('ideas', ['title' => 'Borrador todavía incompleto']);
    }

    public function test_changing_access_scope_is_audited_independently_from_publication(): void
    {
        $idea = $this->idea(['access_scope' => 'only_me']);

        $this->actingAs($this->author)
            ->put(route('ideas.update', $idea), [
                'title' => $idea->title,
                'description' => $idea->description,
                'category_id' => $this->category->id,
                'visibility' => 'private',
                'access_scope' => 'profile',
                'workspace_status' => $idea->workspace_status,
            ])
            ->assertRedirect(route('ideas.show', $idea->slug));

        $this->assertSame('profile', $idea->fresh()->access_scope);
        $this->assertDatabaseHas('idea_status_histories', [
            'idea_id' => $idea->id,
            'workflow' => 'access',
            'old_status' => 'only_me',
            'new_status' => 'profile',
        ]);
    }

    private function idea(array $attributes = []): Idea
    {
        return Idea::factory()->for($this->author)->create(array_merge([
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'access_scope' => 'only_me',
            'workspace_status' => 'capturada',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
        ], $attributes));
    }
}
