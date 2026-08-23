<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TagSimilarityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::withCount('ideas')->orderByDesc('ideas_count')->orderBy('name')->get();
        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rawName = trim(ltrim(trim($request->input('name', '')), '#'));
        
        if (empty($rawName)) {
            return back()->with('error', 'El nombre de la etiqueta no puede estar vacío.');
        }

        $slug = Str::slug($rawName);
        $normalized = TagSimilarityService::normalize($rawName);

        // Check if already exists
        $existing = Tag::where('slug', $slug)
            ->orWhere('name', $rawName)
            ->first();

        if (!$existing) {
            foreach (Tag::all() as $t) {
                if (TagSimilarityService::normalize($t->name) === $normalized) {
                    $existing = $t;
                    break;
                }
            }
        }

        if ($existing) {
            return back()->with('info', "La etiqueta '#{$existing->name}' ya existía en el catálogo.");
        }

        $tag = Tag::create([
            'name' => Str::title($rawName),
            'slug' => $slug,
        ]);

        return back()->with('success', "Etiqueta '#{$tag->name}' creada exitosamente.");
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $rawName = trim(ltrim(trim($request->input('name', '')), '#'));

        if (empty($rawName)) {
            return back()->with('error', 'El nombre de la etiqueta no puede estar vacío.');
        }

        $newSlug = Str::slug($rawName);
        $newNormalized = TagSimilarityService::normalize($rawName);
        $formattedName = Str::title($rawName);

        // Check if there is another tag (different id) with same slug, name or normalized stem
        $existingOther = Tag::where('id', '!=', $tag->id)
            ->where(function ($q) use ($rawName, $newSlug) {
                $q->where('name', $rawName)->orWhere('slug', $newSlug);
            })
            ->first();

        if (!$existingOther) {
            $otherTags = Tag::where('id', '!=', $tag->id)->get();
            foreach ($otherTags as $other) {
                if (TagSimilarityService::normalize($other->name) === $newNormalized) {
                    $existingOther = $other;
                    break;
                }
            }
        }

        // If another identical/normalized tag already exists -> Automatically Merge (Fusión inteligente)
        if ($existingOther) {
            DB::beginTransaction();
            try {
                // Reassign idea relations to existing tag
                $ideaIds = $tag->ideas()->pluck('ideas.id')->toArray();
                $existingOther->ideas()->syncWithoutDetaching($ideaIds);

                $oldName = $tag->name;
                $targetName = $existingOther->name;

                // Delete source tag
                $tag->ideas()->detach();
                $tag->delete();

                DB::commit();

                return back()->with('success', "La etiqueta '#{$oldName}' fue fusionada exitosamente con '#{$targetName}'. Todas sus ideas han sido reasignadas.");
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error al fusionar la etiqueta: ' . $e->getMessage());
            }
        }

        // Otherwise simply rename
        $tag->update([
            'name' => $formattedName,
            'slug' => $newSlug,
        ]);

        return back()->with('success', "Etiqueta actualizada a '#{$tag->name}' exitosamente.");
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tagName = $tag->name;
        $tag->ideas()->detach();
        $tag->delete();

        return back()->with('success', "Etiqueta '#{$tagName}' eliminada con éxito.");
    }

    public function merge(Request $request): RedirectResponse
    {
        $request->validate([
            'source_tag_id' => ['required', 'exists:tags,id'],
            'target_tag_id' => ['required', 'exists:tags,id', 'different:source_tag_id'],
        ]);

        DB::beginTransaction();
        try {
            $source = Tag::findOrFail($request->source_tag_id);
            $target = Tag::findOrFail($request->target_tag_id);

            // Reassign idea relations
            $ideaIds = $source->ideas()->pluck('ideas.id')->toArray();
            $target->ideas()->syncWithoutDetaching($ideaIds);

            $sourceName = $source->name;
            $targetName = $target->name;

            // Delete source tag
            $source->ideas()->detach();
            $source->delete();

            DB::commit();

            return back()->with('success', "La etiqueta '#{$sourceName}' fue fusionada exitosamente en '#{$targetName}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al fusionar etiquetas: ' . $e->getMessage());
        }
    }
}
