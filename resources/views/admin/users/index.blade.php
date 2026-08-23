@extends('layouts.app')

@section('title', 'Gestión de Usuarios e Invitaciones - Administración INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{ 
    createModal: false, 
    editModal: false, 
    creationTab: 'invitation',
    currentUser: { id: '', name: '', email: '', role: 'user', job_title: '', department: '', regional_id: '', is_active: 1 } 
}">

    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">group</span>
                    <span>Directorio y Onboarding de Colaboradores</span>
                </h1>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Gestión de accesos, roles, envío de invitaciones por correo y contraseñas temporales</p>
            </div>

            <button type="button" 
                    @click="createModal = true" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-all">
                <span class="material-symbols-outlined text-base">person_add</span>
                <span>+ Nuevo Colaborador / Invitación</span>
            </button>
        </div>

        <x-admin-nav-tabs />
    </div>

    <!-- Active Invitation Alert / Copy Link Banner -->
    @if(session('invitation_link'))
    <div class="bg-secondary-fixed/20 border border-secondary-fixed p-4 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs" x-data="{ copied: false }">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">outgoing_mail</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface">Enlace de Activación de Onboarding Generado</p>
                <p class="text-[11px] font-mono-tech text-on-surface-variant break-all select-all mt-0.5">{{ session('invitation_link') }}</p>
            </div>
        </div>

        <button type="button" 
                @click="navigator.clipboard.writeText('{{ session('invitation_link') }}'); copied = true; setTimeout(() => copied = false, 3000)"
                class="px-4 py-2 bg-white rounded-xl text-xs font-bold text-primary shadow-xs hover:bg-white/90 shrink-0 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base" x-text="copied ? 'done' : 'content_copy'"></span>
            <span x-text="copied ? '¡Copiado!' : 'Copiar Enlace'"></span>
        </button>
    </div>
    @endif

    <!-- Pending Invitations Section (If any) -->
    @if($pendingInvitations->isNotEmpty())
    <div class="bg-surface-container-low rounded-2xl p-4 border border-surface-container-high/80 space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold font-mono-tech uppercase tracking-wider text-outline flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base text-amber-600">hourglass_top</span>
                <span>Invitaciones Pendientes de Activación ({{ $pendingInvitations->count() }})</span>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($pendingInvitations as $inv)
            <div class="bg-surface-container-lowest rounded-xl p-3.5 border border-surface-container-high/60 shadow-2xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-bold text-xs text-on-surface truncate">{{ $inv->name }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-mono-tech font-bold uppercase {{ $inv->role === 'admin' ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant' }}">
                            {{ $inv->role }}
                        </span>
                    </div>
                    <p class="text-[11px] font-mono-tech text-outline truncate">{{ $inv->email }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-1">Expira: {{ $inv->expires_at->diffForHumans() }}</p>
                </div>

                <div class="pt-3 border-t border-surface-container-high/40 flex items-center justify-between mt-3 text-xs">
                    <form action="{{ route('admin.users.invitations.resend', $inv->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-primary hover:underline font-semibold text-[11px] flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">refresh</span>
                            <span>Reenviar enlace</span>
                        </button>
                    </form>

                    <form action="{{ route('admin.users.invitations.cancel', $inv->id) }}" method="POST" onsubmit="return confirm('¿Cancelar esta invitación?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-error hover:underline text-[11px]">Cancelar</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Filters Bar -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                <input type="text" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Buscar por nombre, correo, cargo o área..." 
                       class="w-full bg-surface-container-low text-xs rounded-xl py-2.5 pl-9 pr-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <select name="regional_id" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                    <option value="">Todas las regionales</option>
                    @foreach($regionals as $reg)
                    <option value="{{ $reg->id }}" {{ request('regional_id') == $reg->id ? 'selected' : '' }}>
                        {{ $reg->code }} - {{ $reg->name }}
                    </option>
                    @endforeach
                </select>
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
                        <th class="py-3.5 px-4">Regional</th>
                        <th class="py-3.5 px-4 text-center">Seguridad 2FA</th>
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
                            <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-9 h-9 rounded-full object-cover shadow-2xs">
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
                        <td class="py-4 px-4 text-xs text-on-surface-variant">
                            @if($u->regionalModel)
                            <span class="px-2 py-0.5 rounded-md bg-surface-container text-primary font-mono-tech text-[11px] font-bold">
                                {{ $u->regionalModel->code }}
                            </span>
                            <span class="block text-[10px] text-outline">{{ $u->regionalModel->name }}</span>
                            @else
                            <span class="text-outline text-xs">{{ $u->regional ?: 'Sede Central' }}</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($u->two_factor_enabled)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200" title="2FA {{ strtoupper($u->two_factor_type) }}">
                                <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">verified_user</span>
                                <span>2FA Activo</span>
                            </span>
                            @else
                            <span class="text-outline text-[11px] font-mono-tech">Desactivado</span>
                            @endif
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
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Activo</span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container text-[10px] font-bold">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Edit Button -->
                                <button type="button" 
                                        @click="currentUser = { 
                                            id: '{{ $u->id }}', 
                                            name: '{{ addslashes($u->name) }}', 
                                            email: '{{ addslashes($u->email) }}', 
                                            role: '{{ $u->role }}', 
                                            job_title: '{{ addslashes($u->job_title) }}', 
                                            department: '{{ addslashes($u->department) }}', 
                                            regional_id: '{{ $u->regional_id }}', 
                                            bio: '{{ addslashes($u->bio) }}', 
                                            is_active: {{ $u->is_active ? '1' : '0' }} 
                                        }; editModal = true;"
                                        class="px-2.5 py-1 bg-surface-container hover:bg-surface-container-high rounded-lg text-xs font-semibold text-on-surface transition-colors"
                                        title="Editar Usuario">
                                    Editar
                                </button>

                                <!-- Toggle Status -->
                                @if($u->id !== auth()->id())
                                <form action="{{ route('admin.users.status', $u->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors {{ $u->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' }}">
                                        {{ $u->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                <!-- Delete -->
                                <form action="{{ route('admin.users.destroy', $u->id) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('¿Eliminar permanentemente a {{ addslashes($u->name) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded-lg text-error hover:bg-error-container/40">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-10 text-xs text-on-surface-variant">No se encontraron colaboradores.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-surface-container-high/60">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Create User & Invitation Modal -->
    <div x-show="createModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="createModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-surface-container-high z-10 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between mb-4 border-b border-surface-container-high pb-3">
                <h3 class="font-headline font-bold text-lg text-on-surface">Alta de Nuevo Colaborador</h3>
                <button @click="createModal = false" class="text-outline hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Creation Method Selector Tabs -->
            <div class="flex items-center gap-2 bg-surface-container-low p-1 rounded-xl mb-5">
                <button type="button" 
                        @click="creationTab = 'invitation'" 
                        :class="creationTab === 'invitation' ? 'bg-white text-primary shadow-xs font-bold' : 'text-on-surface-variant font-medium'"
                        class="flex-1 py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-base">mail</span>
                    <span>Enviar Invitación (Onboarding)</span>
                </button>
                <button type="button" 
                        @click="creationTab = 'direct'" 
                        :class="creationTab === 'direct' ? 'bg-white text-primary shadow-xs font-bold' : 'text-on-surface-variant font-medium'"
                        class="flex-1 py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-base">key</span>
                    <span>Contraseña Temporal</span>
                </button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="creation_type" :value="creationTab">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre Completo <span class="text-error">*</span></label>
                        <input type="text" name="name" required placeholder="Ej.: Lic. Juan Pérez" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Correo Institucional <span class="text-error">*</span></label>
                        <input type="email" name="email" required placeholder="juan.perez@infotep.gob.do" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Rol de Acceso <span class="text-error">*</span></label>
                        <select name="role" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-semibold">
                            <option value="user">Usuario Colaborador</option>
                            <option value="admin">Administrador General</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Dirección Regional</label>
                        <select name="regional_id" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                            <option value="">Seleccionar Regional</option>
                            @foreach($regionals as $r)
                            <option value="{{ $r->id }}">{{ $r->code }} - {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Cargo</label>
                        <input type="text" name="job_title" placeholder="Ej.: Instructor Técnico" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Departamento / Área</label>
                        <input type="text" name="department" placeholder="Ej.: Formación Profesional" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>

                <!-- Direct Password Field (Visible only in Direct Mode) -->
                <div x-show="creationTab === 'direct'" class="space-y-3 pt-2 border-t border-surface-container-high/60">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Contraseña Temporal <span class="text-error">*</span></label>
                        <input type="password" name="password" :required="creationTab === 'direct'" placeholder="••••••••" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                        <p class="text-[11px] text-on-surface-variant mt-1">El usuario será forzado a cambiar esta contraseña obligatoriamente en su primer inicio de sesión.</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-surface-container-high flex justify-end gap-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container shadow-xs">
                        <span x-text="creationTab === 'invitation' ? 'Enviar Invitación' : 'Crear con Contraseña'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div x-show="editModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs" @click="editModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-surface-container-high z-10 max-h-[90vh] overflow-y-auto">
            <h3 class="font-headline font-bold text-lg text-on-surface mb-1">Editar Colaborador</h3>
            <p class="text-xs text-on-surface-variant mb-5" x-text="'Actualizar datos de ' + currentUser.name"></p>

            <form :action="'{{ url('/admin/usuarios') }}/' + currentUser.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre Completo <span class="text-error">*</span></label>
                        <input type="text" name="name" x-model="currentUser.name" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Correo Institucional <span class="text-error">*</span></label>
                        <input type="email" name="email" x-model="currentUser.email" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Rol de Acceso <span class="text-error">*</span></label>
                        <select name="role" x-model="currentUser.role" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high font-semibold">
                            <option value="user">Usuario Colaborador</option>
                            <option value="admin">Administrador General</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Dirección Regional</label>
                        <select name="regional_id" x-model="currentUser.regional_id" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                            <option value="">Seleccionar Regional</option>
                            @foreach($regionals as $r)
                            <option value="{{ $r->id }}">{{ $r->code }} - {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Cargo</label>
                        <input type="text" name="job_title" x-model="currentUser.job_title" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Departamento / Área</label>
                        <input type="text" name="department" x-model="currentUser.department" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>

                <div class="pt-2 border-t border-surface-container-high/60">
                    <p class="text-[11px] font-mono-tech text-outline uppercase font-bold mb-2">Cambiar Contraseña (Opcional)</p>
                    <input type="password" name="password" placeholder="Nueva contraseña (mínimo 8 caracteres)" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-on-surface">
                        <input type="checkbox" name="is_active" value="1" :checked="currentUser.is_active == 1" class="rounded text-primary focus:ring-primary">
                        <span>Usuario Activo en la plataforma</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-surface-container-high flex justify-end gap-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-outline">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container shadow-xs">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
