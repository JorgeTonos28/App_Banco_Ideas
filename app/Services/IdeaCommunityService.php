<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IdeaCommunityService
{
    public function syncAudience(
        Idea $idea,
        User $actor,
        ?int $organizationalUnitId,
        bool $includeDescendants = false
    ): void {
        if ($idea->parent_idea_id || $idea->access_scope !== 'organization') {
            $idea->communityUnits()->detach();

            return;
        }

        if (! $organizationalUnitId) {
            throw ValidationException::withMessages([
                'organizational_unit_id' => 'Selecciona la comunidad interna que podrá consultar esta idea.',
            ]);
        }

        $unit = Regional::query()->where('is_active', true)->find($organizationalUnitId);

        if (! $unit || ! $this->availableUnitsFor($actor)->contains('id', $unit->id)) {
            throw ValidationException::withMessages([
                'organizational_unit_id' => 'La comunidad seleccionada no pertenece a tu contexto organizacional.',
            ]);
        }

        $idea->communityUnits()->sync([
            $unit->id => [
                'include_descendants' => $includeDescendants,
                'shared_by_user_id' => $actor->id,
            ],
        ]);
    }

    public function availableUnitsFor(User $user): Collection
    {
        $unit = $user->effectiveOrganizationalUnit();

        if (! $unit) {
            return collect();
        }

        $ids = $unit->ancestorAndSelfIds()
            ->concat($unit->descendantIds())
            ->unique()
            ->values();

        return Regional::query()
            ->whereKey($ids)
            ->where('is_active', true)
            ->with('parent')
            ->orderBy('type')
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->sortBy(fn (Regional $candidate) => $candidate->path_label)
            ->values();
    }

    public function ideasForUnit(Regional $unit, User $viewer): Builder
    {
        $unitAncestorIds = $unit->ancestors()->pluck('id');
        $viewerUnit = $viewer->effectiveOrganizationalUnit();
        $viewerAncestorIds = $viewerUnit?->ancestors()->pluck('id') ?? collect();

        return Idea::query()
            ->whereNull('parent_idea_id')
            ->where('visibility', '!=', 'draft')
            ->where('access_scope', 'organization')
            ->whereHas('communityUnits', function (Builder $shares) use (
                $unit,
                $unitAncestorIds,
                $viewer,
                $viewerUnit,
                $viewerAncestorIds
            ): void {
                $shares->where(function (Builder $pageAudience) use ($unit, $unitAncestorIds): void {
                    $pageAudience
                        ->where('regionals.id', $unit->id)
                        ->orWhere(function (Builder $inheritedAudience) use ($unitAncestorIds): void {
                            $inheritedAudience
                                ->whereIn('regionals.id', $unitAncestorIds)
                                ->where('idea_community_shares.include_descendants', true);
                        });
                });

                if (! $viewer->isAdmin()) {
                    if (! $viewerUnit) {
                        $shares->whereRaw('1 = 0');

                        return;
                    }

                    $shares->where(function (Builder $viewerAudience) use ($viewerUnit, $viewerAncestorIds): void {
                        $viewerAudience
                            ->where('regionals.id', $viewerUnit->id)
                            ->orWhere(function (Builder $inheritedViewerAudience) use ($viewerAncestorIds): void {
                                $inheritedViewerAudience
                                    ->whereIn('regionals.id', $viewerAncestorIds)
                                    ->where('idea_community_shares.include_descendants', true);
                            });
                    });
                }
            });
    }

    public function canNavigateTo(User $user, Regional $unit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->availableUnitsFor($user)->contains('id', $unit->id);
    }
}
