<?php

namespace App\AI\Services;

class AiAmbiguityDetector
{
    public function organization(array $result, float $threshold): bool
    {
        if (($result['confidence'] ?? 0) < $threshold) {
            return true;
        }

        $parent = $result['parent_suggestion'] ?? [];

        return ($parent['idea_id'] ?? null) !== null
            && ($parent['confidence'] ?? 0) < $threshold;
    }

    public function relations(array $result, float $threshold): bool
    {
        return ($result['confidence'] ?? 0) < $threshold
            || collect($result['relations'] ?? [])->contains(fn (array $relation) => ($relation['confidence'] ?? 0) < $threshold);
    }
}
