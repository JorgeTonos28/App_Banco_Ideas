@extends('layouts.app')

@section('title', 'Gestión de Usuarios - Administración INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{ roleModal: false, selectedUser: null }">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Directorio de Colaboradores</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Gestión de accesos, roles administrativos y estado de usuarios</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                <input type="text" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Buscar por nombre, correo, cargo o área..." 
                       class="w-full bg-surface-container-low text-xs rounded-xl py-2.5 pl-9 pr-3 border border-surface-container-high">
            </div>

            <div>
                <select name="rol" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                    <option value="">Todos los roles</option>
                    <option value="user" {{ request('rol') == 'user' ? 'selected' : '' }}>Usuario</option>
                    <option value="admin" {{ request('rol') == 'admin' ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-surface-container-lowest rounded-3xl border border-surface-container-high/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-surface-container-low font-mono-tech text-[11px] uppercase tracking-wider text-outline border-b border-surface-container-high">
                    <tr>
                        <th class="py-3.5 px-4">Usuario</th>
                        <th class="py-3.5 px-4">Cargo / Área</th>
                        <th class="py-3.5 px-4 text-center">Rol</th>
                        <th class="py-3.5 px-4 text-center">Ideas</th>
                        <th class="py-3.5 px-4 text-center">Estado</th>
                        <th class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-high/60">
                    @forelse($users as $u)
                    <tr class="hover:bg-surface-container-low/60 transition-colors">
                        <td class="py-4 px-4 flex items-center gap-3">
                            <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-9 h-9 rounded-full object-cover">
                            <div>
                                <a href="{{ route('profile.show', $u->id) }}" class="font-bold text-on-surface hover:text-primary transition-colors block">
                                    {{ $u->name }}
                                </a>
                                <span class="text-[11px] text-outline font-mono-tech">{{ $u->email }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-semibold text-on-surface block">{{ $u->job_title ?: 'Colaborador' }}</span>
                            <span class="text-[10px] text-on-surface-variant block font-mono-tech">{{ $u->department ?: 'General' }}</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($u->isAdmin())
                            <span class="px-2.5 py-1 rounded-full bg-primary text-white text-[10px] font-mono-tech font-bold uppercase">
                                Administrador
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full bg-surface-container text-on-surface-variant text-[10px] font-mono-tech font-bold uppercase">
                                Usuario
                            </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center font-mono-tech font-bold text-primary">
                            {{ $u->ideas_count }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($u->is_active)
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Activo</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full bg-error-container text-on-error-container text-[10px] font-bold">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" 
                                        @click="selectedUser = {{ json_encode($u) }}; roleModal = true;"
                                        class="px-2.5 py-1 bg-surface-container hover:bg-surface-container-high rounded-lg text-xs font-semibold text-on-surface">
                                    Cambiar Rol
                                </button>
                                @if($u->id !== auth()->id())
                                <form action="{{ route('admin.users.status', $u->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $u->is_active ? 'bg-error-container/40 text-error hover:bg-error-container' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' }}">
                                        {{ $u->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-xs text-on-surface-variant">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-surface-container-high/60">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Role Change Modal -->
    <div x-show="roleModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="roleModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-surface-container-high z-10">
            <h3 class="font-headline font-bold text-base text-on-surface mb-2">Cambiar Rol de Usuario</h3>
            <p class="text-xs text-on-surface-variant mb-4" x-text="'Modificar permisos para ' + (selectedUser?.name || '')"></p>

            <form :action="'/admin/usuarios/' + (selectedUser?.id || '') + '/rol'" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Rol en la plataforma</label>
                    <select name="role" x-model="selectedUser.role" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                        <option value="user">Usuario (Crear, votar, comentar)</option>
                        <option value="admin">Administrador (Control total y moderación)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="roleModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl">Guardar Rol</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
