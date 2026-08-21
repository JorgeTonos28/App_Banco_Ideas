@extends('layouts.app')

@section('title', 'Gestión de Etiquetas - Administración INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{ mergeModal: false, createModal: false, editModal: false, currentTag: { id: '', name: '' } }">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Etiquetas y Palabras Clave</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Control de descriptores temáticos y consolidación de términos</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="mergeModal = true" 
                    class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-headline font-bold text-xs rounded-xl transition-colors">
                Fusionar Etiquetas
            </button>
            <button type="button" 
                    @click="createModal = true" 
                    class="px-4 py-2 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-colors">
                + Nueva Etiqueta
            </button>
        </div>
    </div>

    <!-- Tags Table & Chips Grid -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
        <h2 class="font-headline font-bold text-base text-on-surface">Etiquetas Activas ({{ $tags->count() }})</h2>

        <div class="flex flex-wrap gap-2.5 pt-2">
            @foreach($tags as $tag)
            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-surface-container-low border border-surface-container-high text-xs group hover:border-primary/40 transition-colors">
                <span class="font-mono-tech font-semibold text-on-surface">#{{ $tag->name }}</span>
                <span class="px-1.5 py-0.5 rounded-full bg-surface-container text-[10px] font-mono-tech text-outline font-bold">{{ $tag->ideas_count }}</span>
                
                <button type="button" @click="currentTag = {{ json_encode($tag) }}; editModal = true;" class="text-outline hover:text-primary p-0.5">
                    <span class="material-symbols-outlined text-xs">edit</span>
                </button>
                <form action="{{ route('admin.tags.destroy', $tag->id) }}" method="POST" onsubmit="return confirm('¿Eliminar etiqueta #{{ $tag->name }}?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-outline hover:text-error p-0.5">
                        <span class="material-symbols-outlined text-xs">close</span>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Create Tag Modal -->
    <div x-show="createModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="createModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-base text-on-surface mb-3">Nueva Etiqueta</h3>
            <form action="{{ route('admin.tags.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre</label>
                    <input type="text" name="name" required placeholder="Ej.: Inteligencia Artificial" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl">Crear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Tag Modal -->
    <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="editModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-base text-on-surface mb-3">Editar Etiqueta</h3>
            <form :action="'/admin/etiquetas/' + currentTag.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre</label>
                    <input type="text" name="name" x-model="currentTag.name" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Merge Tags Modal -->
    <div x-show="mergeModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="mergeModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-md w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-base text-on-surface mb-1">Fusionar Etiquetas Duplicadas</h3>
            <p class="text-xs text-on-surface-variant mb-4">Todas las ideas vinculadas a la etiqueta origen se reasignarán a la etiqueta destino, eliminando la duplicada.</p>

            <form action="{{ route('admin.tags.merge') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Etiqueta de Origen (Será eliminada)</label>
                    <select name="source_tag_id" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                        <option value="">Selecciona la etiqueta origen</option>
                        @foreach($tags as $t)
                        <option value="{{ $t->id }}">#{{ $t->name }} ({{ $t->ideas_count }} ideas)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Etiqueta de Destino (Permanecerá)</label>
                    <select name="target_tag_id" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                        <option value="">Selecciona la etiqueta destino</option>
                        @foreach($tags as $t)
                        <option value="{{ $t->id }}">#{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="mergeModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl">Fusionar</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
