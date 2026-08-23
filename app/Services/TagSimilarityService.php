<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TagSimilarityService
{
    /**
     * Normalize a tag string: lowercase, remove accents, strip symbols and trim.
     */
    public static function normalize(string $string): string
    {
        $string = trim(ltrim(trim($string), '#'));
        $string = mb_strtolower($string, 'UTF-8');

        // Replace accents and diacritics
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n',
        ];
        $string = strtr($string, $unwanted);

        // Replace hyphens and underscores with spaces
        $string = str_replace(['-', '_'], ' ', $string);

        // Remove non-alphanumeric characters except spaces
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);

        // Normalize multiple spaces to single space
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Basic Spanish stemmer to normalize plurals and common suffixes.
     */
    public static function stem(string $word): string
    {
        $normalized = self::normalize($word);

        if (mb_strlen($normalized) <= 3) {
            return $normalized;
        }

        // Plurals ending in 'ces' -> 'z' (e.g., luces -> luz, matrices -> matriz)
        if (str_ends_with($normalized, 'ces') && mb_strlen($normalized) > 4) {
            return substr($normalized, 0, -3) . 'z';
        }

        // Plurals ending in 'es' after consonants (e.g., sensores -> sensor, talleres -> taller, innovaciones -> innovacion)
        if (str_ends_with($normalized, 'es') && mb_strlen($normalized) > 4) {
            $base = substr($normalized, 0, -2);
            $lastChar = substr($base, -1);
            if (in_array($lastChar, ['r', 'l', 'n', 'd', 'z', 'j', 'm'])) {
                return $base;
            }
        }

        // Plurals ending in 's' after vowels (e.g., capacitaciones -> capacitacion, herramientas -> herramienta)
        if (str_ends_with($normalized, 's') && mb_strlen($normalized) > 3) {
            $base = substr($normalized, 0, -1);
            $lastChar = substr($base, -1);
            if (in_array($lastChar, ['a', 'e', 'i', 'o', 'u'])) {
                return $base;
            }
        }

        return $normalized;
    }

    /**
     * Calculates similarity percentage between two terms (0.0 to 1.0).
     */
    public static function calculateSimilarity(string $term1, string $term2): float
    {
        $norm1 = self::normalize($term1);
        $norm2 = self::normalize($term2);

        if ($norm1 === '' || $norm2 === '') {
            return 0.0;
        }

        // Exact normalized match
        if ($norm1 === $norm2) {
            return 1.0;
        }

        // Stem match (plural / singular variation)
        $stem1 = self::stem($term1);
        $stem2 = self::stem($term2);
        if ($stem1 === $stem2 && mb_strlen($stem1) >= 3) {
            return 0.95;
        }

        // Token containment (e.g., "Inteligencia Artificial" contains "Artificial" or "Inteligencia")
        if (mb_strlen($norm1) >= 4 && mb_strlen($norm2) >= 4) {
            if (str_contains($norm1, $norm2) || str_contains($norm2, $norm1)) {
                $minLen = min(mb_strlen($norm1), mb_strlen($norm2));
                $maxLen = max(mb_strlen($norm1), mb_strlen($norm2));
                return max(0.80, $minLen / $maxLen);
            }
        }

        // Levenshtein distance calculation
        $maxLen = max(mb_strlen($norm1), mb_strlen($norm2));
        if ($maxLen === 0) return 0.0;

        $lev = levenshtein($norm1, $norm2);
        if ($lev < 0) return 0.0;

        $levScore = 1.0 - ($lev / $maxLen);

        // Similar_text comparison
        similar_text($norm1, $norm2, $similarPercent);
        $simScore = $similarPercent / 100.0;

        return max($levScore, $simScore);
    }

    /**
     * Find existing tags that are similar to the provided name.
     *
     * @param string $name
     * @param iterable|null $existingTags
     * @param float $threshold Default 0.70 (70% similarity)
     * @param int $limit Maximum results
     * @return Collection
     */
    public static function findSimilar(string $name, ?iterable $tags = null, float $threshold = 0.70, int $limit = 5): Collection
    {
        $name = trim($name);
        if (mb_strlen($name) < 2) {
            return collect();
        }

        $allTags = $tags ? collect($tags) : Tag::withCount('ideas')->get();
        $targetNorm = self::normalize($name);
        $targetSlug = Str::slug($name);

        $results = collect();

        foreach ($allTags as $tag) {
            $tagName = is_array($tag) ? ($tag['name'] ?? '') : $tag->name;
            $tagId = is_array($tag) ? ($tag['id'] ?? 0) : $tag->id;
            $tagSlug = is_array($tag) ? ($tag['slug'] ?? '') : $tag->slug;
            $ideasCount = is_array($tag) ? ($tag['ideas_count'] ?? 0) : ($tag->ideas_count ?? 0);

            // Skip exact match in similar list (handled separately)
            if ($tagSlug === $targetSlug || self::normalize($tagName) === $targetNorm) {
                continue;
            }

            $sim = self::calculateSimilarity($name, $tagName);

            if ($sim >= $threshold) {
                $results->push([
                    'id' => $tagId,
                    'name' => $tagName,
                    'slug' => $tagSlug,
                    'ideas_count' => (int) $ideasCount,
                    'similarity' => round($sim * 100),
                ]);
            }
        }

        return $results->sortByDesc('similarity')->take($limit)->values();
    }

    /**
     * Find exact canonical match or create a new Tag without redundant duplicates.
     */
    public static function findOrCreateNormalized(string $name): Tag
    {
        $cleanName = trim(ltrim(trim($name), '#'));
        if ($cleanName === '') {
            throw new \InvalidArgumentException('Tag name cannot be empty.');
        }

        $slug = Str::slug($cleanName);
        $normalized = self::normalize($cleanName);

        // 1. Try exact name match
        $existing = Tag::where('name', $cleanName)->first();
        if ($existing) {
            return $existing;
        }

        // 2. Try slug match (handles casing and accents differences, e.g. "robotica" vs "Robótica")
        $existingBySlug = Tag::where('slug', $slug)->first();
        if ($existingBySlug) {
            return $existingBySlug;
        }

        // 3. Try normalized stem match if exact same stem exists
        $stem = self::stem($cleanName);
        $allTags = Tag::all();
        foreach ($allTags as $tag) {
            if (self::normalize($tag->name) === $normalized) {
                return $tag;
            }
        }

        // 4. Create new Tag formatted neatly (Capitalized first letter of words)
        $formattedName = Str::title($cleanName);
        return Tag::create([
            'name' => $formattedName,
            'slug' => $slug,
        ]);
    }
}
