<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_aggregates_ideas_by_department(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $firstAuthor = User::factory()->create([
            'department' => 'Tecnologia',
        ]);

        $secondAuthor = User::factory()->create([
            'department' => 'Tecnologia',
        ]);

        Idea::create([
            'user_id' => $firstAuthor->id,
            'title' => 'Primera idea',
            'slug' => 'primera-idea',
            'description' => 'Descripción de la primera idea.',
        ]);

        Idea::create([
            'user_id' => $firstAuthor->id,
            'title' => 'Segunda idea',
            'slug' => 'segunda-idea',
            'description' => 'Descripción de la segunda idea.',
        ]);

        Idea::create([
            'user_id' => $secondAuthor->id,
            'title' => 'Tercera idea',
            'slug' => 'tercera-idea',
            'description' => 'Descripción de la tercera idea.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Tecnologia');
        $response->assertSee('3 ideas');
    }
}
