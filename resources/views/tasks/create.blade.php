@extends('layouts.app')

@section('title', 'Nueva tarea - Centro de Innovación')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary"><span class="material-symbols-outlined text-base">arrow_back</span>Volver a tareas</a>
        <h1 class="mt-3 font-headline text-3xl font-extrabold">Nueva tarea</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Una tarea es una acción concreta que puede completarse. Si lo que describes plantea una oportunidad o solución con valor propio, probablemente corresponde al Banco de Ideas.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-primary/20 bg-primary-fixed/30 p-4">
            <div class="flex items-center gap-2 font-bold text-primary"><span class="material-symbols-outlined">lightbulb</span>Idea</div>
            <p class="mt-1 text-xs text-on-surface-variant">Propone una oportunidad, solución o cambio que necesita valoración y puede originar varias acciones.</p>
        </div>
        <div class="rounded-2xl border border-tertiary/20 bg-tertiary-fixed/25 p-4">
            <div class="flex items-center gap-2 font-bold text-tertiary"><span class="material-symbols-outlined">checklist</span>Tarea</div>
            <p class="mt-1 text-xs text-on-surface-variant">Describe algo ejecutable, con una condición clara de terminado y, si aplica, responsable y fecha.</p>
        </div>
    </div>

    <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data"
          x-data="taskForm(@js([
              'title' => old('title', ''),
              'description' => old('description', ''),
              'idea_id' => (string) old('idea_id', $selectedIdeaId),
              'parent_task_id' => (string) old('parent_task_id', $selectedParentId),
          ]))"
          class="space-y-6 rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6 shadow-xs sm:p-8">
        @csrf

        <div x-show="loadedFromAi" x-cloak class="flex items-start justify-between gap-3 rounded-2xl border border-tertiary/25 bg-tertiary-fixed/25 p-4 text-xs">
            <div><strong class="text-tertiary">Borrador trasladado por la IA.</strong><p class="mt-1 text-on-surface-variant">Revísalo antes de crear la tarea; ninguna sugerencia se guarda automáticamente.</p></div>
            <button type="button" @click="clearAiDraft" class="font-bold text-outline">Descartar</button>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-error/20 bg-error-container/50 p-4 text-xs text-error">{{ $errors->first() }}</div>
        @endif

        <div>
            <label for="title" class="mb-2 block text-xs font-mono-tech font-bold uppercase">Qué hay que hacer</label>
            <input id="title" name="title" x-model="title" required maxlength="255" class="w-full rounded-2xl border border-surface-container-high bg-surface-container-low p-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Preparar prototipo para validar el flujo">
        </div>

        <div>
            <label for="description" class="mb-2 block text-xs font-mono-tech font-bold uppercase">Detalles o criterio de terminado</label>
            <textarea id="description" name="description" x-model="description" rows="4" maxlength="5000" class="w-full rounded-2xl border border-surface-container-high bg-surface-container-low p-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Describe el resultado esperado, enlaces o contexto necesario."></textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="idea_id" class="mb-2 block text-xs font-bold">Idea vinculada (opcional)</label>
                <select id="idea_id" name="idea_id" x-model="ideaId" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-sm">
                    <option value="">Tarea suelta, sin idea</option>
                    @foreach($ideaCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->title }} · {{ $candidate->user->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="parent_task_id" class="mb-2 block text-xs font-bold">Tarea superior (opcional)</label>
                <select id="parent_task_id" name="parent_task_id" x-model="parentTaskId" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-sm">
                    <option value="">Será una tarea principal</option>
                    @foreach($parentCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->title }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="mb-2 block text-xs font-bold">Responsable</label><select name="assigned_to_user_id" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-sm"><option value="">Yo mismo</option>@foreach($assignableUsers as $person)<option value="{{ $person->id }}" @selected((int) old('assigned_to_user_id') === $person->id)>{{ $person->name }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-bold">Prioridad</label><select name="priority" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-sm">@foreach(['baja'=>'Baja','normal'=>'Normal','alta'=>'Alta'] as $value=>$label)<option value="{{ $value }}" @selected(old('priority','normal')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-bold">Vencimiento</label><input type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-sm"></div>
        </div>

        <div class="rounded-2xl border border-surface-container-high bg-surface-container-low p-4">
            <h2 class="text-xs font-bold">Recordatorio</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <input type="datetime-local" name="remind_at" value="{{ old('remind_at') }}" class="rounded-xl border border-surface-container-high bg-white p-3 text-sm">
                <div class="flex flex-wrap items-center gap-4 text-xs">
                    <label class="flex items-center gap-2"><input type="checkbox" name="reminder_channels[]" value="email" @checked(in_array('email', old('reminder_channels', [])))>Correo</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="reminder_channels[]" value="browser" @checked(in_array('browser', old('reminder_channels', [])))>Navegador</label>
                    <button type="button" onclick="window.enableTaskBrowserNotifications?.()" class="font-bold text-primary">Activar permiso</button>
                </div>
            </div>
            <p class="mt-2 text-[10px] text-outline">Los avisos del navegador aparecen mientras INNOVATEP esté abierto; el correo funciona mediante el programador del servidor.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-2 block text-xs font-bold">Colaboración</label><select name="participation_mode" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-sm"><option value="private">Privada o por asignación</option><option value="open" @selected(old('participation_mode')==='open')>Aceptar voluntarios de la comunidad</option></select><p class="mt-1 text-[10px] text-outline">La opción abierta sólo está disponible si la idea vinculada lo permite.</p></div>
            <div><label class="mb-2 block text-xs font-bold">Archivos (máx. 5, 10 MB c/u)</label><input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"></div>
        </div>

        <div class="flex justify-end gap-3 border-t border-surface-container-high pt-5">
            <a href="{{ route('tasks.index') }}" class="rounded-xl px-4 py-2.5 text-xs font-bold text-on-surface-variant">Cancelar</a>
            <button type="submit" class="rounded-xl bg-primary px-6 py-2.5 text-xs font-bold text-white">Crear tarea</button>
        </div>
    </form>
</div>
@endsection
