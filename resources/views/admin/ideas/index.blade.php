@extends('layouts.app')

@section('title', 'Gestión de Ideas - Administración INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{ 
    drawerOpen: false, 
    selectedIdea: null,
    selectedIds: [],
    bulkAction: '',
    openDrawer(idea) {
        this.selectedIdea = idea;
        this.drawerOpen = true;
    }
}">

    <!-- Header Section -->
    <div class="space-y-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Gestión Administrativa de Ideas</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Control de ciclo de vida, moderación, asignación de responsables y seguimiento</p>
        </div>

        <x-admin-nav-tabs />
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
        <form method="GET" action="{{ route('admin.ideas.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                <input type="text" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Buscar por ID, título o autor..." 
                       class="w-full bg-surface-container-low text-xs rounded-xl py-2.5 pl-9 pr-3 border border-surface-container-high">
            </div>

            <!-- Status Filter -->
            <div>
                <select name="estado" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                    <option value="">Todos los estados</option>
                    <option value="nueva" {{ request('estado') == 'nueva' ? 'selected' : '' }}>💡 Nueva</option>
                    <option value="en_revision" {{ request('estado') == 'en_revision' ? 'selected' : '' }}>👀 En revisión</option>
                    <option value="priorizada" {{ request('estado') == 'priorizada' ? 'selected' : '' }}>⭐ Priorizada</option>
                    <option value="en_desarrollo" {{ request('estado') == 'en_desarrollo' ? 'selected' : '' }}>🧪 En desarrollo</option>
                    <option value="implementada" {{ request('estado') == 'implementada' ? 'selected' : '' }}>🚀 Implementada</option>
                    <option value="descartada" {{ request('estado') == 'descartada' ? 'selected' : '' }}>⛔ Descartada</option>
                    <option value="archivada" {{ request('estado') == 'archivada' ? 'selected' : '' }}>📦 Archivada</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <select name="categoria" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority Filter -->
            <div>
                <select name="prioridad" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                    <option value="">Todas las prioridades</option>
                    <option value="baja" {{ request('prioridad') == 'baja' ? 'selected' : '' }}>Baja</option>
                    <option value="media" {{ request('prioridad') == 'media' ? 'selected' : '' }}>Media</option>
                    <option value="alta" {{ request('prioridad') == 'alta' ? 'selected' : '' }}>Alta</option>
                    <option value="estrategica" {{ request('prioridad') == 'estrategica' ? 'selected' : '' }}>Estratégica</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Batch Actions Bar (Shows when rows selected) -->
    <div x-show="selectedIds.length > 0" x-transition class="bg-primary text-white p-3 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-md">
        <div class="text-xs font-mono-tech flex items-center gap-2">
            <span class="material-symbols-outlined text-base">checklist</span>
            <span><b x-text="selectedIds.length"></b> ideas seleccionadas</span>
        </div>

        <form action="{{ route('admin.ideas.batch') }}" method="POST" class="flex items-center gap-2">
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="idea_ids[]" :value="id">
            </template>

            <select name="action" x-model="bulkAction" required class="bg-primary-container text-white text-xs rounded-xl py-1.5 px-3 border border-white/20">
                <option value="">Seleccionar acción masiva</option>
                <option value="feature">Marcar como destacadas</option>
                <option value="unfeature">Quitar de destacadas</option>
                <option value="archive">Archivar ideas</option>
            </select>

            <button type="submit" class="px-4 py-1.5 bg-white text-primary text-xs font-bold rounded-xl hover:bg-white/90">
                Aplicar
            </button>
        </form>
    </div>

    <!-- Admin Ideas Table -->
    <div class="bg-surface-container-lowest rounded-3xl border border-surface-container-high/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-surface-container-low font-mono-tech text-[11px] uppercase tracking-wider text-outline border-b border-surface-container-high">
                    <tr>
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input type="checkbox" 
                                   @change="selectedIds = $el.checked ? {{ json_encode($ideas->pluck('id')) }} : []" 
                                   class="rounded text-primary focus:ring-primary border-outline-variant">
                        </th>
                        <th class="py-3.5 px-4">Idea</th>
                        <th class="py-3.5 px-4">Autor / Área</th>
                        <th class="py-3.5 px-4">Categoría</th>
                        <th class="py-3.5 px-4 text-center">Votos / Score</th>
                        <th class="py-3.5 px-4 text-center">Estado</th>
                        <th class="py-3.5 px-4 text-center">Responsable</th>
                        <th class="py-3.5 px-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-high/60">
                    @forelse($ideas as $idea)
                    <tr class="hover:bg-surface-container-low/60 transition-colors">
                        <td class="py-4 px-4 text-center">
                            <input type="checkbox" 
                                   value="{{ $idea->id }}" 
                                   x-model="selectedIds" 
                                   class="rounded text-primary focus:ring-primary border-outline-variant">
                        </td>
                        <td class="py-4 px-4 font-semibold text-on-surface max-w-xs">
                            <div class="flex items-center gap-1.5">
                                @if($idea->is_featured)
                                <span class="material-symbols-outlined text-amber-500 text-sm" style="font-variation-settings: 'FILL' 1;" title="Destacada">star</span>
                                @endif
                                <a href="{{ route('ideas.show', $idea->slug) }}" class="hover:text-primary transition-colors line-clamp-1">
                                    {{ $idea->title }}
                                </a>
                            </div>
                            <span class="text-[10px] font-mono-tech text-outline">ID #{{ $idea->id }} • {{ $idea->created_at->translatedFormat('d M, Y') }}</span>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-bold text-on-surface block text-xs truncate">{{ $idea->user->name }}</span>
                            <span class="text-[10px] text-on-surface-variant block truncate">{{ $idea->user->department ?: 'INFOTEP' }}</span>
                        </td>
                        <td class="py-4 px-4 text-xs text-on-surface-variant">
                            {{ $idea->category?->name ?: 'General' }}
                        </td>
                        <td class="py-4 px-4 text-center font-mono-tech text-xs">
                            <span class="font-bold text-primary">{{ $idea->innovation_score }} pts</span>
                            <span class="text-outline block text-[10px]">({{ $idea->votes_count }} votos)</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <x-status-badge :status="$idea->status" />
                        </td>
                        <td class="py-4 px-4 text-center text-xs">
                            @if($idea->assignedTo)
                            <span class="font-semibold text-primary font-mono-tech">{{ $idea->assignedTo->name }}</span>
                            @else
                            <span class="text-outline text-[11px] italic">Sin asignar</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button type="button" 
                                    @click="openDrawer({{ json_encode($idea) }})" 
                                    class="px-3 py-1.5 bg-primary-fixed hover:bg-primary hover:text-white text-primary text-xs font-semibold rounded-xl transition-colors">
                                Gestionar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-10 text-on-surface-variant text-xs">
                            No se encontraron ideas registradas con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-surface-container-high/60">
            {{ $ideas->links() }}
        </div>
    </div>

    <!-- Administrative Slide-Over Drawer -->
    <div x-show="drawerOpen" 
         class="fixed inset-0 z-50 overflow-hidden" 
         style="display: none;">
        
        <!-- Backdrop -->
        <div x-show="drawerOpen" 
             x-transition:enter="ease-in-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in-out duration-300" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             @click="drawerOpen = false" 
             class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs transition-opacity"></div>

        <!-- Drawer Panel -->
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="drawerOpen" 
                 x-transition:enter="transform transition ease-in-out duration-300" 
                 x-transition:enter-start="translate-x-full" 
                 x-transition:enter-end="translate-x-0" 
                 x-transition:leave="transform transition ease-in-out duration-300" 
                 x-transition:leave-start="translate-x-0" 
                 x-transition:leave-end="translate-x-full" 
                 class="w-screen max-w-md bg-surface-container-lowest shadow-2xl p-6 sm:p-8 flex flex-col justify-between overflow-y-auto">

                <div class="space-y-6">
                    <!-- Drawer Header -->
                    <div class="border-b border-surface-container-high pb-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-mono-tech uppercase font-bold text-outline">Gestión Administrativa</span>
                                <h2 class="font-headline font-bold text-lg text-on-surface mt-0.5" x-text="selectedIdea?.title"></h2>
                            </div>
                            <button @click="drawerOpen = false" class="text-outline hover:text-on-surface p-1">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="'/ideas/' + (selectedIdea?.id || '') + '/editar'" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-semibold transition-colors">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                <span>Editar Propuesta y Etiquetas</span>
                            </a>
                            <a :href="'/ideas/' + (selectedIdea?.slug || selectedIdea?.id || '')" 
                               target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                                <span>Ver Detalle</span>
                            </a>
                        </div>
                    </div>

                    <!-- Update Form -->
                    <form :action="'/admin/ideas/' + (selectedIdea?.id || '')" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- Status Selection -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                Estado del Ciclo de Vida <span class="text-error">*</span>
                            </label>
                            <select name="status" x-model="selectedIdea.status" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-semibold">
                                <option value="nueva">💡 Nueva</option>
                                <option value="en_revision">👀 En revisión</option>
                                <option value="priorizada">⭐ Priorizada</option>
                                <option value="en_desarrollo">🧪 En desarrollo</option>
                                <option value="implementada">🚀 Implementada</option>
                                <option value="descartada">⛔ Descartada</option>
                                <option value="archivada">📦 Archivada</option>
                            </select>
                        </div>

                        <!-- Status Comment (Public history log) -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                Observación Pública (Línea de Tiempo)
                            </label>
                            <textarea name="status_comment" rows="2" placeholder="Explica brevemente la razón del cambio de estado..." class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high resize-none"></textarea>
                        </div>

                        <!-- Assigned Reviewer -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                Responsable de Seguimiento
                            </label>
                            <select name="assigned_to_user_id" x-model="selectedIdea.assigned_to_user_id" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                                <option value="">Sin responsable asignado</option>
                                @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->department }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                Prioridad Institucional
                            </label>
                            <select name="priority" x-model="selectedIdea.priority" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                                <option value="">No definida</option>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                                <option value="estrategica">Estratégica</option>
                            </select>
                        </div>

                        <!-- Internal Observations (Private for admins) -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                Observaciones Internas (Admin)
                            </label>
                            <textarea name="admin_observations" x-model="selectedIdea.admin_observations" rows="3" placeholder="Notas internas para el equipo de innovación..." class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high resize-none"></textarea>
                        </div>

                        <!-- Next Action & Follow up date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                    Próxima Acción
                                </label>
                                <input type="text" name="next_action" x-model="selectedIdea.next_action" placeholder="Ej.: Cotización" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">
                                    Fecha Seguimiento
                                </label>
                                <input type="date" name="follow_up_date" x-model="selectedIdea.follow_up_date" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                            </div>
                        </div>

                        <!-- Featured Toggle -->
                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-on-surface">
                                <input type="checkbox" name="is_featured" value="1" :checked="selectedIdea?.is_featured" class="rounded text-primary focus:ring-primary">
                                <span>Destacar esta idea en la pantalla de inicio</span>
                            </label>
                        </div>

                        <!-- Action Submit -->
                        <div class="pt-4 border-t border-surface-container-high flex items-center justify-end gap-2">
                            <button type="button" @click="drawerOpen = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                            <button type="submit" class="px-6 py-2.5 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary-container">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
