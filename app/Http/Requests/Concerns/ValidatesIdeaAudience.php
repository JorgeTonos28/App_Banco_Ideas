<?php

namespace App\Http\Requests\Concerns;

use App\Models\Regional;
use Illuminate\Validation\Validator;

trait ValidatesIdeaAudience
{
    public function validateIdeaAudience(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $scope = $this->input('access_scope', 'only_me');
        $isDraft = $this->input('visibility') === 'draft';

        if ($isDraft && in_array($scope, ['profile', 'organization'], true)) {
            $validator->errors()->add('access_scope', 'Completa la idea antes de compartirla con otras personas.');

            return;
        }

        if ($scope !== 'organization') {
            return;
        }

        if (! $this->filled('organizational_unit_id')) {
            $validator->errors()->add('organizational_unit_id', 'Selecciona la comunidad interna que podrá consultar la idea.');

            return;
        }

        $unit = Regional::query()->where('is_active', true)->find($this->integer('organizational_unit_id'));
        $userUnit = $this->user()?->effectiveOrganizationalUnit();

        if (! $unit || ! $userUnit) {
            $validator->errors()->add('organizational_unit_id', 'La comunidad interna seleccionada no está disponible.');

            return;
        }

        $allowedIds = $userUnit->ancestorAndSelfIds()
            ->concat($userUnit->descendantIds())
            ->unique();

        if (! $allowedIds->contains($unit->id)) {
            $validator->errors()->add('organizational_unit_id', 'Sólo puedes compartir en comunidades de tu estructura organizacional.');
        }
    }
}
