<?php

namespace App\AI\Services;

use App\Models\Idea;
use App\Models\IdeaRelation;
use App\Models\User;
use Illuminate\Support\Str;

class ConfirmedAiRelationService
{
    public function createApproved(Idea $source, User $actor, array $relations): void
    {
        $targets = Idea::query()
            ->whereIn('id', collect($relations)->pluck('target_idea_id'))
            ->where('user_id', $actor->id)
            ->whereNotIn('workspace_status', ['archivada', 'descartada'])
            ->get()
            ->keyBy('id');

        foreach ($relations as $relation) {
            $target = $targets->get((int) $relation['target_idea_id']);

            if (! $target || $source->is($target) || ! in_array($relation['type'] ?? null, IdeaRelation::TYPES, true)) {
                continue;
            }

            IdeaRelation::updateOrCreate([
                'source_idea_id' => $source->id,
                'target_idea_id' => $target->id,
                'type' => $relation['type'],
            ], [
                'status' => 'approved',
                'rationale' => Str::limit(trim(strip_tags((string) $relation['rationale'])), 1000, ''),
                'created_by_user_id' => $actor->id,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
            ]);
        }
    }
}
