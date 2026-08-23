@extends('layouts.app')

@section('title', 'Gestión de Categorías - Administración INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{ modalOpen: false, editMode: false, currentCat: { id: '', name: '', icon: 'lightbulb', color: '#003e6f', description: '' } }">

    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Categorías del Banco de Ideas</h1>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Organización y taxonomía para clasificar las propuestas institucionales</p>
            </div>

            <button type="button" 
                    @click="editMode = false; currentCat = { id: '', name: '', icon: 'lightbulb', color: '#003e6f', description: '' }; modalOpen = true;"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container">
                <span class="material-symbols-outlined text-base">add</span>
                <span>Nueva Categoría</span>
            </button>
        </div>

        <x-admin-nav-tabs />
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($categories as $category)
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-surface-container-high/80 shadow-2xs flex flex-col justify-between group">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-2xs" style="background-color: {{ $category->color }}">
                        <span class="material-symbols-outlined text-xl">{{ $category->icon }}</span>
                    </div>
                    <span class="text-[11px] font-mono-tech text-outline font-bold">{{ $category->ideas_count }} ideas</span>
                </div>

                <h3 class="font-headline font-bold text-base text-on-surface mb-1">{{ $category->name }}</h3>
                <p class="text-xs text-on-surface-variant line-clamp-2">{{ $category->description ?: 'Sin descripción' }}</p>
            </div>

            <div class="pt-4 border-t border-surface-container-high/60 flex items-center justify-between mt-4">
                <button type="button" 
                        @click="editMode = true; currentCat = {{ json_encode($category) }}; modalOpen = true;"
                        class="text-xs font-semibold text-primary hover:underline">
                    Editar
                </button>

                @if($category->ideas_count == 0)
                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta categoría?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-error hover:underline">Eliminar</button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Category Create / Edit Modal -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="modalOpen = false"></div>

        <div class="relative bg-surface-container-lowest rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-lg text-on-surface mb-4" x-text="editMode ? 'Editar Categoría' : 'Nueva Categoría'"></h3>

            <form :action="editMode ? '{{ url('/admin/categorias') }}/' + currentCat.id : '{{ route('admin.categories.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre</label>
                    <input type="text" name="name" x-model="currentCat.name" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Ícono (Material Symbol)</label>
                        <input type="text" name="icon" x-model="currentCat.icon" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Color HEX</label>
                        <input type="text" name="color" x-model="currentCat.color" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Descripción</label>
                    <textarea name="description" x-model="currentCat.description" rows="3" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high resize-none"></textarea>
                </div>

                <div class="pt-4 border-t border-surface-container-high flex items-center justify-end gap-2">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container">Guardar</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
