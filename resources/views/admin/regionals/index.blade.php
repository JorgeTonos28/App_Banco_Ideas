@extends('layouts.app')

@section('title', 'Gestión de Regionales INFOTEP - Administración')

@section('content')
<div class="space-y-6" x-data="{ createModal: false, editModal: false, currentRegional: { id: '', code: '', name: '', order: 0 } }">

    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">location_city</span>
                    <span>Regionales y Centros INFOTEP</span>
                </h1>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Configuración de direcciones regionales, creación, edición de nombres y control de disponibilidad</p>
            </div>

            <button type="button" 
                    @click="createModal = true" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-all">
                <span class="material-symbols-outlined text-base">add_location_alt</span>
                <span>Nueva Regional</span>
            </button>
        </div>

        <x-admin-nav-tabs />
    </div>

    <!-- Regionals Table -->
    <div class="bg-surface-container-lowest rounded-3xl border border-surface-container-high/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-surface-container-low font-mono-tech text-[11px] uppercase tracking-wider text-outline border-b border-surface-container-high">
                    <tr>
                        <th class="py-3.5 px-4 w-16 text-center">Orden</th>
                        <th class="py-3.5 px-4">Código</th>
                        <th class="py-3.5 px-4">Nombre de la Dirección Regional</th>
                        <th class="py-3.5 px-4 text-center">Colaboradores</th>
                        <th class="py-3.5 px-4 text-center">Estado</th>
                        <th class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-high/60">
                    @forelse($regionals as $r)
                    <tr class="hover:bg-surface-container-low/60 transition-colors">
                        <td class="py-4 px-4 text-center font-mono-tech font-bold text-outline">
                            {{ $r->order }}
                        </td>
                        <td class="py-4 px-4 font-mono-tech font-bold text-primary">
                            <span class="px-2.5 py-1 rounded-lg bg-primary-fixed text-on-primary-fixed text-xs">
                                {{ $r->code }}
                            </span>
                        </td>
                        <td class="py-4 px-4 font-semibold text-on-surface">
                            {{ $r->name }}
                        </td>
                        <td class="py-4 px-4 text-center font-mono-tech font-bold text-on-surface-variant">
                            {{ $r->users_count }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($r->is_active)
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Habilitada</span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container text-[10px] font-bold">Inhabilitada</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" 
                                        @click="currentRegional = { id: '{{ $r->id }}', code: '{{ addslashes($r->code) }}', name: '{{ addslashes($r->name) }}', order: {{ $r->order }} }; editModal = true;"
                                        class="px-2.5 py-1 bg-surface-container hover:bg-surface-container-high rounded-lg text-xs font-semibold text-on-surface transition-colors">
                                    Editar
                                </button>

                                <form action="{{ route('admin.regionals.status', $r->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors {{ $r->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' }}">
                                        {{ $r->is_active ? 'Inhabilitar' : 'Habilitar' }}
                                    </button>
                                </form>

                                @if($r->users_count == 0)
                                <form action="{{ route('admin.regionals.destroy', $r->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta regional?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-error hover:bg-error-container/40 rounded-lg">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-xs text-on-surface-variant">No se encontraron regionales registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Regional Modal -->
    <div x-show="createModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="createModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-lg text-on-surface mb-1">Nueva Regional INFOTEP</h3>
            <p class="text-xs text-on-surface-variant mb-4">Ingresa el código oficial y nombre descriptivo de la regional.</p>

            <form action="{{ route('admin.regionals.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Código (Ej.: DRM, ONA, DRE) <span class="text-error">*</span></label>
                    <input type="text" name="code" required placeholder="DRM" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-mono-tech uppercase font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre Completo <span class="text-error">*</span></label>
                    <input type="text" name="name" required placeholder="Regional Metropolitana" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Posición / Orden</label>
                    <input type="number" name="order" value="{{ $regionals->count() + 1 }}" min="1" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-mono-tech">
                </div>

                <div class="pt-4 border-t border-surface-container-high flex justify-end gap-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container">Crear Regional</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Regional Modal -->
    <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="editModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-lg text-on-surface mb-1">Editar Regional INFOTEP</h3>
            <p class="text-xs text-on-surface-variant mb-4" x-text="'Modificar datos de ' + currentRegional.code"></p>

            <form :action="'{{ url('/admin/regionales') }}/' + currentRegional.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Código <span class="text-error">*</span></label>
                    <input type="text" name="code" x-model="currentRegional.code" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-mono-tech uppercase font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre Completo <span class="text-error">*</span></label>
                    <input type="text" name="name" x-model="currentRegional.name" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Posición / Orden</label>
                    <input type="number" name="order" x-model="currentRegional.order" min="1" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-mono-tech">
                </div>

                <div class="pt-4 border-t border-surface-container-high flex justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
