@extends('layouts.app')

@section('title', 'Estructura organizacional - Administración INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{
    createModal: false,
    editModal: false,
    createUnit: { type: 'regional', parent_id: '' },
    currentUnit: { id: '', type: 'regional', parent_id: '', code: '', name: '', order: 0 }
}">
    <div class="space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="flex items-center gap-2 font-headline text-2xl font-extrabold text-on-surface sm:text-3xl">
                    <span class="material-symbols-outlined text-3xl text-primary" aria-hidden="true">account_tree</span>
                    Estructura organizacional
                </h1>
                <p class="mt-1 text-xs text-on-surface-variant sm:text-sm">Organiza regionales o sedes, direcciones funcionales y departamentos para controlar las comunidades internas.</p>
            </div>

            <button type="button" @click="createModal = true; createUnit = { type: 'regional', parent_id: '' }"
                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-primary-container">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">add_business</span>
                Nueva unidad
            </button>
        </div>

        <x-admin-nav-tabs />
    </div>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Resumen de estructura">
        @foreach([
            ['type' => 'regional', 'label' => 'Regionales y sedes', 'icon' => 'location_city'],
            ['type' => 'direction', 'label' => 'Direcciones funcionales', 'icon' => 'corporate_fare'],
            ['type' => 'department', 'label' => 'Departamentos', 'icon' => 'domain'],
        ] as $summary)
            <div class="rounded-2xl border border-surface-container-high/70 bg-surface-container-lowest p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <span class="block text-[10px] font-mono-tech font-bold uppercase text-outline">{{ $summary['label'] }}</span>
                        <span class="mt-1 block font-headline text-2xl font-extrabold text-primary">{{ $regionals->where('type', $summary['type'])->count() }}</span>
                    </div>
                    <span class="material-symbols-outlined text-2xl text-primary" aria-hidden="true">{{ $summary['icon'] }}</span>
                </div>
            </div>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-3xl border border-surface-container-high/80 bg-surface-container-lowest shadow-xs">
        <div class="border-b border-surface-container-high/70 p-5 sm:p-6">
            <h2 class="font-headline text-lg font-bold text-on-surface">Árbol institucional</h2>
            <p class="mt-1 text-xs text-on-surface-variant">Cada colaborador se asigna a su unidad más específica. Su ruta superior se calcula automáticamente.</p>
        </div>

        <div class="space-y-3 p-4 sm:p-6">
            @forelse($treeRoots as $rootUnit)
                <x-organizational-unit-node :unit="$rootUnit" :tree-by-parent="$treeByParent" />
            @empty
                <div class="rounded-2xl border border-dashed border-surface-container-high p-10 text-center">
                    <span class="material-symbols-outlined text-4xl text-outline" aria-hidden="true">account_tree</span>
                    <p class="mt-2 text-sm font-bold text-on-surface">Aún no existe una estructura organizacional</p>
                    <p class="mt-1 text-xs text-on-surface-variant">Comienza creando una regional o sede.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div x-show="createModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="createModal = false"></div>
        <div class="relative z-10 w-full max-w-lg rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6 shadow-2xl sm:p-8">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-headline text-lg font-bold text-on-surface">Nueva unidad organizacional</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">El tipo elegido determina cuál puede ser su nivel superior.</p>
                </div>
                <button type="button" @click="createModal = false" class="rounded-lg p-1 text-outline hover:bg-surface-container hover:text-on-surface" aria-label="Cerrar">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>

            <form action="{{ route('admin.regionals.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="create_unit_type" class="mb-1.5 block text-xs font-bold text-on-surface">Tipo de unidad</label>
                    <select id="create_unit_type" name="type" x-model="createUnit.type" @change="if (createUnit.type === 'regional') createUnit.parent_id = ''" required class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                        <option value="regional">Regional o sede</option>
                        <option value="direction">Dirección funcional</option>
                        <option value="department">Departamento</option>
                    </select>
                </div>

                <div x-show="createUnit.type !== 'regional'">
                    <label for="create_parent_id" class="mb-1.5 block text-xs font-bold text-on-surface">Nivel superior</label>
                    <select id="create_parent_id" name="parent_id" x-model="createUnit.parent_id" :required="createUnit.type !== 'regional'" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                        <option value="">Selecciona una unidad</option>
                        @foreach($regionals as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->path_label }} ({{ $candidate->type_label }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-on-surface-variant">Direcciones dependen de una regional; departamentos dependen de una dirección.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="create_code" class="mb-1.5 block text-xs font-bold text-on-surface">Código</label>
                        <input id="create_code" type="text" name="code" required maxlength="20" placeholder="DFIN" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs font-mono-tech uppercase">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="create_name" class="mb-1.5 block text-xs font-bold text-on-surface">Nombre</label>
                        <input id="create_name" type="text" name="name" required maxlength="100" placeholder="Dirección Financiera" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                    </div>
                </div>

                <div>
                    <label for="create_order" class="mb-1.5 block text-xs font-bold text-on-surface">Orden</label>
                    <input id="create_order" type="number" name="order" min="0" max="10000" value="{{ $regionals->count() + 1 }}" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs font-mono-tech">
                </div>

                <div class="flex justify-end gap-2 border-t border-surface-container-high pt-4">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-bold text-on-surface-variant">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white hover:bg-primary-container">Crear unidad</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="editModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="editModal = false"></div>
        <div class="relative z-10 w-full max-w-lg rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6 shadow-2xl sm:p-8">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-headline text-lg font-bold text-on-surface">Editar unidad</h3>
                    <p class="mt-1 text-xs text-on-surface-variant" x-text="currentUnit.name"></p>
                </div>
                <button type="button" @click="editModal = false" class="rounded-lg p-1 text-outline hover:bg-surface-container hover:text-on-surface" aria-label="Cerrar">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>

            <form :action="'{{ url('/admin/regionales') }}/' + currentUnit.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_unit_type" class="mb-1.5 block text-xs font-bold text-on-surface">Tipo de unidad</label>
                    <select id="edit_unit_type" name="type" x-model="currentUnit.type" @change="if (currentUnit.type === 'regional') currentUnit.parent_id = ''" required class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                        <option value="regional">Regional o sede</option>
                        <option value="direction">Dirección funcional</option>
                        <option value="department">Departamento</option>
                    </select>
                </div>

                <div x-show="currentUnit.type !== 'regional'">
                    <label for="edit_parent_id" class="mb-1.5 block text-xs font-bold text-on-surface">Nivel superior</label>
                    <select id="edit_parent_id" name="parent_id" x-model="currentUnit.parent_id" :required="currentUnit.type !== 'regional'" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                        <option value="">Selecciona una unidad</option>
                        @foreach($regionals as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->path_label }} ({{ $candidate->type_label }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="edit_code" class="mb-1.5 block text-xs font-bold text-on-surface">Código</label>
                        <input id="edit_code" type="text" name="code" x-model="currentUnit.code" required maxlength="20" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs font-mono-tech uppercase">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="edit_name" class="mb-1.5 block text-xs font-bold text-on-surface">Nombre</label>
                        <input id="edit_name" type="text" name="name" x-model="currentUnit.name" required maxlength="100" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                    </div>
                </div>

                <div>
                    <label for="edit_order" class="mb-1.5 block text-xs font-bold text-on-surface">Orden</label>
                    <input id="edit_order" type="number" name="order" x-model="currentUnit.order" min="0" max="10000" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs font-mono-tech">
                </div>

                <div class="flex justify-end gap-2 border-t border-surface-container-high pt-4">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-bold text-on-surface-variant">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white hover:bg-primary-container">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
