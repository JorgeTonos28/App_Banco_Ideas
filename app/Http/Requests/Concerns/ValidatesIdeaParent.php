<?php

namespace App\Http\Requests\Concerns;

use App\Models\Idea;
use Illuminate\Validation\Validator;

trait ValidatesIdeaParent
{
    public function validateIdeaParent(Validator $validator, ?Idea $idea = null): void
    {
        if ($validator->errors()->isNotEmpty() || ! $this->filled('parent_idea_id')) {
            return;
        }

        $parent = Idea::find($this->integer('parent_idea_id'));
        $user = $this->user();

        if (! $parent || ! $user?->can('view', $parent)) {
            $validator->errors()->add('parent_idea_id', 'No tienes acceso a la idea madre seleccionada.');

            return;
        }

        if (! $user->isAdmin() && $parent->user_id !== $user->id) {
            $validator->errors()->add('parent_idea_id', 'Solo puedes organizar tus ideas bajo otra idea propia.');

            return;
        }

        $visited = [];
        while ($parent) {
            if (($idea && $parent->is($idea)) || isset($visited[$parent->id])) {
                $validator->errors()->add('parent_idea_id', 'La selección crearía un ciclo en la jerarquía de ideas.');
                break;
            }

            $visited[$parent->id] = true;
            $parent = $parent->parentIdea;
        }
    }
}
