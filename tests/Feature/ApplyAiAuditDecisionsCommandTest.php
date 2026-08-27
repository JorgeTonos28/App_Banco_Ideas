<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryDimension;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplyAiAuditDecisionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_previews_then_applies_the_approved_decisions_idempotently(): void
    {
        $user = User::factory()->create();
        $dimension = CategoryDimension::query()->where('slug', 'area-de-innovacion')->firstOrFail();
        $dimension->update([
            'selection_mode' => 'single',
            'is_required' => true,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $category = Category::unguarded(fn () => Category::create([
            'id' => 12,
            'category_dimension_id' => $dimension->id,
            'name' => 'Comunicación y Marca Institucional',
            'is_active' => false,
        ]));

        $ideas = collect([
            10 => ['Crear app Banco de Ideas', null],
            17 => ['Banco de ideas: Agregar sistema de tareas', 10],
            20 => ['Banco de ideas: Como red social abierta el público en general', 10],
            30 => ['Idea de prueba', null],
            31 => ['Proando segunda idea', null],
        ])->map(function (array $data, int $id) use ($user, $category): Idea {
            return Idea::factory()->for($user)->create([
                'id' => $id,
                'category_id' => $category->id,
                'title' => $data[0],
                'parent_idea_id' => $data[1],
                'workspace_status' => 'capturada',
            ]);
        });

        $this->artisan('ideas:apply-ai-audit-decisions')->assertSuccessful();
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame(10, $ideas->get(17)->fresh()->parent_idea_id);

        $this->artisan('ideas:apply-ai-audit-decisions', ['--apply' => true])->assertSuccessful();

        $this->assertTrue($category->fresh()->is_active);
        $this->assertNull($ideas->get(17)->fresh()->parent_idea_id);
        $this->assertSame(10, $ideas->get(20)->fresh()->parent_idea_id);
        $this->assertSame('archivada', $ideas->get(30)->fresh()->workspace_status);
        $this->assertSame('archivada', $ideas->get(31)->fresh()->workspace_status);
        $this->assertDatabaseHas('idea_relations', [
            'source_idea_id' => 17,
            'target_idea_id' => 10,
            'type' => 'complements',
            'status' => 'approved',
        ]);

        $historyCount = DB::table('idea_hierarchy_histories')->count() + DB::table('idea_status_histories')->count();
        $relationCount = DB::table('idea_relations')->count();
        $this->artisan('ideas:apply-ai-audit-decisions', ['--apply' => true])->assertSuccessful();
        $this->assertSame($historyCount, DB::table('idea_hierarchy_histories')->count() + DB::table('idea_status_histories')->count());
        $this->assertSame($relationCount, DB::table('idea_relations')->count());
    }
}
