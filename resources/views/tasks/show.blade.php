@extends('layouts.app')

@section('title', $task->title.' - Tareas')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-2 text-xs text-on-surface-variant">
        <a href="{{ route('tasks.index') }}" class="font-bold text-primary">Tareas</a><span>/</span>
        @if($task->parentTask)<a href="{{ route('tasks.show', $task->parentTask) }}" class="truncate">{{ $task->parentTask->title }}</a><span>/</span>@endif
        <span class="truncate">{{ $task->title }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">
            <section class="rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6 shadow-xs sm:p-8">
                <div class="flex items-start gap-4">
                    @can('changeStatus', $task)<form action="{{ route('tasks.status.update', $task) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $task->status === 'completada' ? 'pendiente' : 'completada' }}"><button type="submit" class="flex h-11 w-11 items-center justify-center rounded-full border-2 {{ $task->status === 'completada' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-outline-variant text-outline hover:border-primary hover:text-primary' }}"><span class="material-symbols-outlined">check</span></button></form>@else<span class="flex h-11 w-11 items-center justify-center rounded-full border-2 {{ $task->status === 'completada' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-outline-variant text-outline' }}"><span class="material-symbols-outlined">check</span></span>@endcan
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 text-[10px] font-mono-tech font-bold uppercase">
                            <span class="rounded-lg bg-surface-container px-2 py-1 text-on-surface-variant">{{ $task->status_label }}</span>
                            <span class="{{ $task->priority === 'alta' ? 'text-error' : 'text-outline' }}">Prioridad {{ $task->priority_label }}</span>
                            @if($task->participation_mode === 'open')<span class="text-tertiary">Colaboración abierta</span>@endif
                        </div>
                        <h1 class="mt-2 font-headline text-2xl font-extrabold sm:text-3xl {{ $task->status === 'completada' ? 'line-through opacity-65' : '' }}">{{ $task->title }}</h1>
                        @if($task->description)<p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-on-surface-variant">{{ $task->description }}</p>@endif
                    </div>
                </div>

                <div class="mt-6 grid gap-3 border-t border-surface-container-high pt-5 sm:grid-cols-3">
                    <div><span class="block text-[10px] font-mono-tech font-bold uppercase text-outline">Responsable</span><span class="mt-1 block text-xs font-bold">{{ $task->assignee?->name ?: 'Sin asignar' }}</span></div>
                    <div><span class="block text-[10px] font-mono-tech font-bold uppercase text-outline">Vencimiento</span><span class="mt-1 block text-xs font-bold {{ $task->is_overdue ? 'text-error' : '' }}">{{ $task->due_at?->translatedFormat('d M Y, h:i A') ?: 'Sin fecha' }}</span></div>
                    <div><span class="block text-[10px] font-mono-tech font-bold uppercase text-outline">Origen</span>@if($task->idea)<a href="{{ route('ideas.show', $task->idea->slug) }}" class="mt-1 block truncate text-xs font-bold text-primary">{{ $task->idea->title }}</a>@else<span class="mt-1 block text-xs font-bold">Tarea suelta</span>@endif</div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    @can('update', $task)<a href="{{ route('tasks.create', ['parent' => $task->id, 'idea' => $task->idea_id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-secondary-fixed px-4 py-2.5 text-xs font-bold text-on-secondary-fixed"><span class="material-symbols-outlined text-base">add</span>Agregar subtarea</a>@endcan
                    @can('volunteer', $task)<form action="{{ route('tasks.volunteers.store', $task) }}" method="POST">@csrf<button class="inline-flex items-center gap-2 rounded-xl bg-tertiary px-4 py-2.5 text-xs font-bold text-white"><span class="material-symbols-outlined text-base">volunteer_activism</span>Quiero colaborar</button></form>@endcan
                </div>
            </section>

            <section class="rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6">
                <div class="flex items-center justify-between"><div><h2 class="font-headline text-lg font-bold">Árbol de trabajo</h2><p class="mt-1 text-xs text-on-surface-variant">Tarea principal y subtareas asociadas.</p></div></div>
                <div class="mt-5 space-y-3">@foreach($taskTree['roots'] as $root)<x-task-tree-node :task="$root" :tree-by-parent="$taskTree['byParent']" :current-task-id="$task->id" />@endforeach</div>
            </section>

            <section class="rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6">
                <div class="flex items-center justify-between"><h2 class="font-headline text-lg font-bold">Archivos</h2><span class="text-[10px] font-mono-tech text-outline">Privados · acceso controlado</span></div>
                <div class="mt-4 space-y-2">
                    @forelse($task->attachments as $attachment)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low p-3">
                            <a href="{{ route('tasks.attachments.download', $attachment) }}" class="min-w-0 flex-1 truncate text-xs font-bold text-primary"><span class="material-symbols-outlined mr-2 align-middle text-base">attach_file</span>{{ $attachment->file_name }} <span class="font-normal text-outline">· {{ $attachment->formatted_size }}</span></a>
                            @can('update', $task)<form action="{{ route('tasks.attachments.destroy', $attachment) }}" method="POST">@csrf @method('DELETE')<button class="text-error" title="Eliminar"><span class="material-symbols-outlined text-base">delete</span></button></form>@endcan
                        </div>
                    @empty<p class="text-xs text-outline">Aún no hay archivos en esta tarea.</p>@endforelse
                </div>
                @can('uploadAttachment', $task)
                    <form action="{{ route('tasks.attachments.store', $task) }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-col gap-2 rounded-2xl border border-dashed border-surface-container-high p-4 sm:flex-row sm:items-center">@csrf<input type="file" name="attachments[]" multiple required class="min-w-0 flex-1 text-xs"><button class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white">Agregar archivos</button></form>
                @endcan
            </section>
        </div>

        <aside class="space-y-6 lg:col-span-4">
            @can('update', $task)
            <form action="{{ route('tasks.update', $task) }}" method="POST" class="space-y-4 rounded-3xl border border-surface-container-high bg-surface-container-lowest p-5">@csrf @method('PUT')
                <h2 class="font-headline text-base font-bold">Editar tarea</h2>
                <div><label class="mb-1 block text-[11px] font-bold">Título</label><input name="title" value="{{ old('title', $task->title) }}" required maxlength="255" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"></div>
                <div><label class="mb-1 block text-[11px] font-bold">Detalles</label><textarea name="description" rows="4" maxlength="5000" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">{{ old('description', $task->description) }}</textarea></div>
                <div class="grid grid-cols-2 gap-3"><div><label class="mb-1 block text-[11px] font-bold">Estado</label><select name="status" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">@foreach(['pendiente'=>'Pendiente','en_progreso'=>'En progreso','completada'=>'Completada','cancelada'=>'Cancelada'] as $value=>$label)<option value="{{ $value }}" @selected($task->status===$value)>{{ $label }}</option>@endforeach</select></div><div><label class="mb-1 block text-[11px] font-bold">Prioridad</label><select name="priority" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">@foreach(['baja'=>'Baja','normal'=>'Normal','alta'=>'Alta'] as $value=>$label)<option value="{{ $value }}" @selected($task->priority===$value)>{{ $label }}</option>@endforeach</select></div></div>
                <div><label class="mb-1 block text-[11px] font-bold">Responsable</label><select name="assigned_to_user_id" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"><option value="">Sin asignar</option>@foreach($assignableUsers as $person)<option value="{{ $person->id }}" @selected($task->assigned_to_user_id===$person->id)>{{ $person->name }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-[11px] font-bold">Vencimiento</label><input type="datetime-local" name="due_at" value="{{ old('due_at', $task->due_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"></div>
                <div><label class="mb-1 block text-[11px] font-bold">Colaboración</label><select name="participation_mode" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"><option value="private" @selected($task->participation_mode==='private')>Privada o asignada</option><option value="open" @selected($task->participation_mode==='open')>Abierta a voluntarios</option></select></div>
                <div class="rounded-xl bg-surface-container-low p-3"><label class="mb-2 block text-[11px] font-bold">Recordatorio</label><input type="datetime-local" name="remind_at" value="{{ old('remind_at', $reminder?->remind_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-surface-container-high bg-white p-2.5 text-xs"><div class="mt-2 flex gap-3 text-[10px]"><label><input type="checkbox" name="reminder_channels[]" value="email" @checked($reminderChannels->contains('email'))> Correo</label><label><input type="checkbox" name="reminder_channels[]" value="browser" @checked($reminderChannels->contains('browser'))> Navegador</label></div></div>
                <button class="w-full rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white">Guardar cambios</button>
            </form>
            @endcan

            @can('reviewVolunteers', $task)
                @if($task->volunteers->isNotEmpty())
                <section class="rounded-3xl border border-tertiary/20 bg-tertiary-fixed/20 p-5"><h2 class="font-headline text-base font-bold">Solicitudes de colaboración</h2><div class="mt-3 space-y-3">@foreach($task->volunteers as $volunteer)<div class="rounded-xl bg-surface-container-lowest p-3"><div class="flex items-center justify-between gap-2"><span class="text-xs font-bold">{{ $volunteer->user->name }}</span><span class="text-[9px] font-bold uppercase text-outline">{{ $volunteer->status }}</span></div>@if($volunteer->status==='pending')<div class="mt-2 flex gap-2"><form action="{{ route('tasks.volunteers.update', [$task, $volunteer]) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-bold text-white">Aceptar</button></form><form action="{{ route('tasks.volunteers.update', [$task, $volunteer]) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="rounded-lg bg-error-container px-3 py-1.5 text-[10px] font-bold text-error">Rechazar</button></form></div>@endif</div>@endforeach</div></section>
                @endif
            @endcan

            @can('delete', $task)<form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea y todas sus subtareas?')">@csrf @method('DELETE')<button class="w-full rounded-xl border border-error/25 px-4 py-2.5 text-xs font-bold text-error">Eliminar tarea</button></form>@endcan
        </aside>
    </div>
</div>
@endsection
