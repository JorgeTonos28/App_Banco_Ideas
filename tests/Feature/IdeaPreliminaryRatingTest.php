<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaPreliminaryRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->voter = User::factory()->create();
    }

    public function test_shared_unpublished_root_accepts_preliminary_ratings_without_an_official_score(): void
    {
        $idea = $this->root(['access_scope' => 'profile']);

        $this->actingAs($this->voter)
            ->postJson(route('ideas.vote', $idea), ['rating' => 5])
            ->assertOk()
            ->assertJsonPath('rating_context', 'preliminary')
            ->assertJsonPath('votes_count', 1)
            ->assertJsonPath('innovation_score', 0);

        $idea->refresh();
        $this->assertSame(1, $idea->votes_count);
        $this->assertSame(5.0, $idea->average_rating);
        $this->assertSame(0, $idea->innovation_score);
    }

    public function test_only_me_root_does_not_accept_ratings(): void
    {
        $idea = $this->root(['access_scope' => 'only_me']);

        $this->actingAs($this->voter)
            ->postJson(route('ideas.vote', $idea), ['rating' => 4])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('idea_ratings', ['idea_id' => $idea->id]);
    }

    public function test_microideas_never_accept_ratings_even_when_shared_or_published(): void
    {
        $root = $this->root([
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
        ]);
        $child = Idea::factory()->for($this->author)->create([
            'parent_idea_id' => $root->id,
            'visibility' => 'private',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'represented_by_parent',
        ]);

        $this->actingAs($this->voter)
            ->postJson(route('ideas.vote', $child), ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Las valoraciones se concentran en la idea madre.');

        $this->assertDatabaseMissing('idea_ratings', ['idea_id' => $child->id]);
    }

    public function test_author_cannot_rate_their_shared_root(): void
    {
        $idea = $this->root(['access_scope' => 'profile']);

        $this->actingAs($this->author)
            ->postJson(route('ideas.vote', $idea), ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'No puedes votar por tu propia idea.');
    }

    public function test_preliminary_ratings_are_retained_and_activate_the_score_after_publication(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $idea = $this->root(['access_scope' => 'profile']);

        $this->actingAs($this->voter)
            ->postJson(route('ideas.vote', $idea), ['rating' => 5])
            ->assertOk();

        $this->actingAs($this->author)
            ->post(route('ideas.publication.request', $idea))
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.ideas.publication.update', $idea), [
                'publication_status' => 'published',
                'community_display' => 'standalone',
            ])
            ->assertRedirect();

        $idea->refresh();
        $this->assertSame(1, $idea->votes_count);
        $this->assertSame(5.0, $idea->average_rating);
        $this->assertGreaterThan(0, $idea->innovation_score);
        $this->assertTrue($idea->acceptsRatings());
    }

    private function root(array $attributes = []): Idea
    {
        return Idea::factory()->for($this->author)->create(array_merge([
            'parent_idea_id' => null,
            'visibility' => 'private',
            'access_scope' => 'only_me',
            'workspace_status' => 'capturada',
            'publication_status' => 'not_submitted',
            'community_display' => 'hidden',
            'innovation_score' => 0,
        ], $attributes));
    }
}
