<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::withCount('ideas')->orderByDesc('ideas_count')->get();
        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Tag::create($validated);

        return back()->with('success', 'Etiqueta creada exitosamente.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name,' . $tag->id],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $tag->update($validated);

        return back()->with('success', 'Etiqueta actualizada exitosamente.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->ideas()->detach();
        $tag->delete();

        return back()->with('success', 'Etiqueta eliminada con éxito.');
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

            // Delete source tag
            $source->ideas()->detach();
            $source->delete();

            DB::commit();

            return back()->with('success', "La etiqueta '{$source->name}' fue fusionada exitosamente en '{$target->name}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al fusionar etiquetas: ' . $e->getMessage());
        }
    }
}
