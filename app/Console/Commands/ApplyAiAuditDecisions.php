<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaHierarchyHistory;
use App\Models\IdeaRelation;
use App\Models\IdeaStatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyAiAuditDecisions extends Command
{
    protected $signature = 'ideas:apply-ai-audit-decisions {--apply : Aplica los cambios; sin esta opción sólo muestra la vista previa}';

    protected $description = 'Aplica de forma idempotente las decisiones humanas aprobadas en la auditoría de IA V1';

    public function handle(): int
    {
        $category = Category::find(12);
        $ideas = Idea::query()->whereIn('id', [10, 17, 20, 30, 31])->get()->keyBy('id');
        $errors = $this->identityErrors($category, $ideas);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            $this->warn('No se aplicó ningún cambio. Verifica que la base corresponda a la exportación auditada.');

            return self::FAILURE;
        }

        $this->table(['Registro', 'Decisión aprobada'], [
            ['Categoría 12', 'Reactivar Comunicación y Marca Institucional'],
            ['Idea 17', 'Convertir en raíz y relacionar como complemento de la idea 10'],
            ['Idea 20', 'Conservar como hija de la idea 10'],
            ['Ideas 30 y 31', 'Archivar y excluir del contexto positivo de IA'],
        ]);

        if (! $this->option('apply')) {
            $this->info('Vista previa completada. Ejecuta nuevamente con --apply para confirmar.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($category, $ideas): void {
            $category->update(['is_active' => true]);

            $idea10 = $ideas->get(10);
            $idea17 = $ideas->get(17);
            $idea20 = $ideas->get(20);

            if ($idea17->parent_idea_id !== null) {
                $oldParentId = $idea17->parent_idea_id;
                $idea17->update([
                    'parent_idea_id' => null,
                    'requested_community_display' => 'standalone',
                    'community_display' => $idea17->isPublished() ? 'standalone' : $idea17->community_display,
                ]);
                IdeaHierarchyHistory::create([
                    'idea_id' => $idea17->id,
                    'old_parent_idea_id' => $oldParentId,
                    'new_parent_idea_id' => null,
                    'changed_by_user_id' => $idea17->user_id,
                    'note' => 'Decisión humana aprobada en la auditoría de IA V1: producto autónomo.',
                ]);
            }

            IdeaRelation::updateOrCreate([
                'source_idea_id' => $idea17->id,
                'target_idea_id' => $idea10->id,
                'type' => 'complements',
            ], [
                'status' => 'approved',
                'rationale' => 'El sistema de tareas puede operar de forma autónoma y complementa el Banco de Ideas.',
                'created_by_user_id' => $idea17->user_id,
                'reviewed_by_user_id' => $idea17->user_id,
                'reviewed_at' => now(),
            ]);

            if ($idea20->parent_idea_id !== $idea10->id) {
                $oldParentId = $idea20->parent_idea_id;
                $idea20->update([
                    'parent_idea_id' => $idea10->id,
                    'requested_community_display' => 'represented_by_parent',
                    'community_display' => $idea20->isPublished() ? 'represented_by_parent' : $idea20->community_display,
                ]);
                IdeaHierarchyHistory::create([
                    'idea_id' => $idea20->id,
                    'old_parent_idea_id' => $oldParentId,
                    'new_parent_idea_id' => $idea10->id,
                    'changed_by_user_id' => $idea20->user_id,
                    'note' => 'Decisión humana aprobada en la auditoría de IA V1: rama evolutiva.',
                ]);
            }

            foreach ([30, 31] as $ideaId) {
                $idea = $ideas->get($ideaId);

                if ($idea->workspace_status === 'archivada') {
                    continue;
                }

                $oldStatus = $idea->workspace_status;
                $idea->update(['workspace_status' => 'archivada']);
                IdeaStatusHistory::create([
                    'idea_id' => $idea->id,
                    'user_id' => $idea->user_id,
                    'workflow' => 'workspace',
                    'old_status' => $oldStatus,
                    'new_status' => 'archivada',
                    'comment' => 'Registro de prueba archivado por decisión humana de la auditoría de IA V1.',
                ]);
            }
        });

        $this->info('Decisiones aplicadas correctamente. Una segunda ejecución no duplicará historial ni relaciones.');

        return self::SUCCESS;
    }

    private function identityErrors(?Category $category, $ideas): array
    {
        $errors = [];

        if (! $category || $category->name !== 'Comunicación y Marca Institucional') {
            $errors[] = 'La categoría 12 no coincide con “Comunicación y Marca Institucional”.';
        }

        $expectedTitles = [
            10 => 'Crear app Banco de Ideas',
            17 => 'Banco de ideas: Agregar sistema de tareas',
            20 => 'Banco de ideas: Como red social abierta el público en general',
            30 => 'Idea de prueba',
            31 => 'Proando segunda idea',
        ];

        foreach ($expectedTitles as $id => $title) {
            if (! $ideas->has($id) || $ideas->get($id)->title !== $title) {
                $errors[] = "La idea {$id} no existe o su título no coincide con la exportación auditada.";
            }
        }

        if ($ideas->has(10) && $ideas->has(17) && $ideas->get(10)->user_id !== $ideas->get(17)->user_id) {
            $errors[] = 'Las ideas 10 y 17 ya no pertenecen al mismo autor; no es seguro crear la relación aprobada.';
        }

        return $errors;
    }
}
