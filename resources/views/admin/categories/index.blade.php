@extends('layouts.app')

@section('title', 'Taxonomía de Ideas - Administración INNOVATEP')

@section('content')
<div class="space-y-6"
     x-data="{
        dimensionModal: false,
        categoryModal: false,
        editDimension: false,
        editCategory: false,
        currentDimension: { id: '', name: '', description: '', selection_mode: 'multiple', is_required: false, is_hierarchical: false, is_active: true, is_primary: false, sort_order: 0 },
        currentCategory: { id: '', category_dimension_id: '', parent_id: '', name: '', icon: 'label', color: '#005696', description: '', is_active: true, sort_order: 0 },
        newDimension() {
            this.editDimension = false;
            this.currentDimension = { id: '', name: '', description: '', selection_mode: 'multiple', is_required: false, is_hierarchical: false, is_active: true, is_primary: false, sort_order: 0 };
            this.dimensionModal = true;
        },
        openDimension(dimension) {
            this.editDimension = true;
            this.currentDimension = dimension;
            this.dimensionModal = true;
        },
        newCategory(dimensionId) {
            this.editCategory = false;
            this.currentCategory = { id: '', category_dimension_id: dimensionId, parent_id: '', name: '', icon: 'label', color: '#005696', description: '', is_active: true, sort_order: 0 };
            this.categoryModal = true;
        },
        openCategory(category) {
            this.editCategory = true;
            this.currentCategory = category;
            this.categoryModal = true;
        }
     }">

    <div class="space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <span class="text-[11px] font-mono-tech uppercase font-bold text-primary">Arquitectura de conocimiento</span>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface mt-1">Taxonomía multidimensional</h1>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1 max-w-2xl">Configura los ejes con los que se describen las ideas. Las etiquetas siguen siendo libres; estas dimensiones mantienen un vocabulario controlado y navegable.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="newDimension()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container text-on-surface text-xs font-bold hover:bg-surface-container-high">
                    <span class="material-symbols-outlined text-base">add_chart</span>
                    Nueva dimensión
                </button>
                <button type="button" @click="newCategory({{ $dimensions->firstWhere('is_primary', true)?->id ?? 'null' }})" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-container">
                    <span class="material-symbols-outlined text-base">add</span>
                    Nuevo término
                </button>
            </div>
        </div>

        <x-admin-nav-tabs />
    </div>

    @if($errors->any())
    <div class="rounded-2xl bg-error-container/55 border border-error/25 p-4 text-sm text-on-error-container">
        <p class="font-bold">No se guardaron los cambios.</p>
        <ul class="mt-1 list-disc pl-5 text-xs space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <x-classification-guidance context="admin" />

    <div class="space-y-5">
        @foreach($dimensions as $dimension)
        <section class="bg-surface-container-lowest rounded-3xl border border-surface-container-high/80 shadow-2xs overflow-hidden">
            <div class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 border-b border-surface-container-high/70">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $dimension->is_primary ? 'bg-primary text-white' : 'bg-tertiary/10 text-tertiary' }} flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">{{ $dimension->is_hierarchical ? 'account_tree' : 'view_week' }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-headline font-bold text-lg text-on-surface">{{ $dimension->name }}</h2>
                            @if($dimension->is_primary)<span class="px-2 py-0.5 rounded-lg bg-primary-fixed text-primary text-[10px] font-mono-tech font-bold">Principal</span>@endif
                            @unless($dimension->is_active)<span class="px-2 py-0.5 rounded-lg bg-surface-container text-outline text-[10px] font-bold">Inactiva</span>@endunless
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1">{{ $dimension->description ?: 'Sin descripción.' }}</p>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[10px] font-mono-tech text-outline">
                            <span>{{ $dimension->selection_mode_label }}</span>
                            <span>{{ $dimension->is_required ? 'Obligatoria' : 'Opcional' }}</span>
                            <span>{{ $dimension->is_hierarchical ? 'Admite niveles' : 'Lista plana' }}</span>
                            <span>{{ $dimension->categories->count() }} términos</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="newCategory({{ $dimension->id }})" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary-fixed text-primary text-xs font-bold hover:bg-primary hover:text-white">
                        <span class="material-symbols-outlined text-base">add</span>
                        Agregar término
                    </button>
                    <button type="button" @click="openDimension(@js($dimension))" class="p-2 rounded-xl bg-surface-container text-on-surface hover:bg-surface-container-high" title="Editar dimensión">
                        <span class="material-symbols-outlined text-base">settings</span>
                    </button>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                @if($dimension->categories->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($dimension->categories as $category)
                    @php $usageCount = max($category->ideas_count, $category->classified_ideas_count); @endphp
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-low/45 p-4 {{ $category->parent_id ? 'ml-4 border-l-4' : '' }}" @if($category->parent_id) style="border-left-color: {{ $category->color }}" @endif>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl text-white flex items-center justify-center shrink-0" style="background-color: {{ $category->color }}">
                                    <span class="material-symbols-outlined text-lg">{{ $category->icon }}</span>
                                </div>
                                <div class="min-w-0">
                                    @if($category->parent)<span class="block text-[9px] font-mono-tech uppercase text-outline">Bajo {{ $category->parent->name }}</span>@endif
                                    <h3 class="font-headline font-bold text-sm text-on-surface truncate">{{ $category->name }}</h3>
                                    <p class="text-[11px] text-on-surface-variant mt-0.5 line-clamp-2">{{ $category->description ?: 'Sin descripción.' }}</p>
                                </div>
                            </div>
                            @unless($category->is_active)<span class="w-2 h-2 rounded-full bg-outline mt-1" title="Inactiva"></span>@endunless
                        </div>

                        <div class="mt-3 pt-3 border-t border-surface-container-high/70 flex items-center justify-between gap-2">
                            <span class="text-[10px] font-mono-tech text-outline">{{ $usageCount }} ideas</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="openCategory(@js($category))" class="text-xs font-bold text-primary hover:underline">Editar</button>
                                @if($usageCount === 0 && $category->children_count === 0)
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('¿Eliminar este término de la taxonomía?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-error hover:underline">Eliminar</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
                @else
                <button type="button" @click="newCategory({{ $dimension->id }})" class="w-full p-8 rounded-2xl border border-dashed border-outline-variant text-center hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-2xl text-outline">label_off</span>
                    <span class="block text-xs font-bold text-on-surface mt-2">Esta dimensión todavía no tiene términos</span>
                    <span class="block text-[11px] text-on-surface-variant mt-1">Agrega el primer valor para activarla en los formularios.</span>
                </button>
                @endif
            </div>
        </section>
        @endforeach
    </div>

    <div x-show="dimensionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-on-surface/45" @click="dimensionModal = false"></div>
        <div class="relative w-full max-w-lg bg-surface-container-lowest rounded-3xl border border-surface-container-high shadow-2xl p-6 sm:p-7 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-3 mb-5">
                <h3 class="font-headline font-bold text-lg text-on-surface" x-text="editDimension ? 'Editar dimensión' : 'Nueva dimensión'"></h3>
                <button type="button" @click="dimensionModal = false" class="p-1 text-outline"><span class="material-symbols-outlined">close</span></button>
            </div>
            <form :action="editDimension ? '{{ url('/admin/dimensiones-categoria') }}/' + currentDimension.id : '{{ route('admin.category-dimensions.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editDimension"><input type="hidden" name="_method" value="PUT"></template>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Nombre</label>
                    <input type="text" name="name" x-model="currentDimension.name" required maxlength="100" class="w-full bg-surface-container-low text-sm rounded-xl p-3 border border-surface-container-high">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Descripción</label>
                    <textarea name="description" x-model="currentDimension.description" rows="3" maxlength="1000" class="w-full bg-surface-container-low text-sm rounded-xl p-3 border border-surface-container-high"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Modo de selección</label>
                        <select name="selection_mode" x-model="currentDimension.selection_mode" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                            <option value="single">Una opción</option>
                            <option value="multiple">Varias opciones</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Orden</label>
                        <input type="number" name="sort_order" x-model="currentDimension.sort_order" min="0" max="1000" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach(['is_required' => 'Obligatoria', 'is_hierarchical' => 'Jerárquica', 'is_active' => 'Activa'] as $field => $label)
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-surface-container-low text-xs font-bold text-on-surface cursor-pointer">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" x-model="currentDimension.{{ $field }}" class="rounded text-primary">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
                <div class="pt-4 border-t border-surface-container-high flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold">Guardar dimensión</button>
                </div>
            </form>
            <form x-show="editDimension && !currentDimension.is_primary" :action="'{{ url('/admin/dimensiones-categoria') }}/' + currentDimension.id" method="POST" class="mt-3 text-right" onsubmit="return confirm('¿Eliminar esta dimensión vacía?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-bold text-error hover:underline">Eliminar dimensión</button>
            </form>
        </div>
    </div>

    <div x-show="categoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-on-surface/45" @click="categoryModal = false"></div>
        <div class="relative w-full max-w-lg bg-surface-container-lowest rounded-3xl border border-surface-container-high shadow-2xl p-6 sm:p-7 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-3 mb-5">
                <h3 class="font-headline font-bold text-lg text-on-surface" x-text="editCategory ? 'Editar término' : 'Nuevo término'"></h3>
                <button type="button" @click="categoryModal = false" class="p-1 text-outline"><span class="material-symbols-outlined">close</span></button>
            </div>
            <form :action="editCategory ? '{{ url('/admin/categorias') }}/' + currentCategory.id : '{{ route('admin.categories.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editCategory"><input type="hidden" name="_method" value="PUT"></template>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Dimensión</label>
                        <select name="category_dimension_id" x-model="currentCategory.category_dimension_id" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                            @foreach($dimensions as $dimension)<option value="{{ $dimension->id }}">{{ $dimension->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Término superior</label>
                        <select name="parent_id" x-model="currentCategory.parent_id" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                            <option value="">Sin término superior</option>
                            @foreach($dimensions as $dimension)
                                @foreach($dimension->categories as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $dimension->name }} · {{ $candidate->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Nombre</label>
                    <input type="text" name="name" x-model="currentCategory.name" required maxlength="100" class="w-full bg-surface-container-low text-sm rounded-xl p-3 border border-surface-container-high">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Material Symbol</label>
                        <input type="text" name="icon" x-model="currentCategory.icon" required maxlength="50" pattern="[a-z0-9_]+" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Color</label>
                        <input type="color" name="color" x-model="currentCategory.color" required class="w-full h-11 bg-surface-container-low rounded-xl p-1.5 border border-surface-container-high">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Descripción</label>
                    <textarea name="description" x-model="currentCategory.description" rows="3" maxlength="500" class="w-full bg-surface-container-low text-sm rounded-xl p-3 border border-surface-container-high"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 p-3 rounded-xl bg-surface-container-low text-xs font-bold text-on-surface cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="currentCategory.is_active" class="rounded text-primary">
                        Término activo
                    </label>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1.5">Orden</label>
                        <input type="number" name="sort_order" x-model="currentCategory.sort_order" min="0" max="1000" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>
                <div class="pt-4 border-t border-surface-container-high flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold">Guardar término</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
