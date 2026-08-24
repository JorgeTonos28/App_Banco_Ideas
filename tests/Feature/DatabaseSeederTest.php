<?php

namespace Tests\Feature;

use App\Models\Idea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_ideas_are_seeded_as_editorially_published(): void
    {
        $this->seed();

        $this->assertSame(6, Idea::count());
        $this->assertSame(6, Idea::published()->count());
        $this->assertSame(6, Idea::communityPublished()->count());
    }
}
