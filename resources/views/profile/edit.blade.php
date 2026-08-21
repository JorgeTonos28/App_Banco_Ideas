@extends('layouts.app')

@section('title', 'Editar Mi Perfil - INNOVATEP')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Editar Perfil</h1>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Actualiza tus datos profesionales y biografía institucional</p>
    </div>

    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs">
        
        @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium space-y-1">
            @foreach($errors->all() as $err)
                <p>• {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Avatar -->
            <div class="flex items-center gap-5">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl object-cover ring-2 ring-primary/20">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                        Foto de Perfil
                    </label>
                    <input type="file" 
                           name="avatar" 
                           accept="image/*"
                           class="block w-full text-xs text-outline file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface-container file:text-on-surface hover:file:bg-surface-container-high cursor-pointer">
                </div>
            </div>

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                    Nombre Completo <span class="text-error">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $user->name) }}" 
                       required 
                       class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Job Title -->
            <div>
                <label for="job_title" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                    Cargo Institucional
                </label>
                <input type="text" 
                       id="job_title" 
                       name="job_title" 
                       value="{{ old('job_title', $user->job_title) }}" 
                       placeholder="Ej.: Instructor Técnico en Mecánica"
                       class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Department & Regional -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="department" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                        Área / Departamento
                    </label>
                    <input type="text" 
                           id="department" 
                           name="department" 
                           value="{{ old('department', $user->department) }}" 
                           placeholder="Ej.: Formación Profesional"
                           class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label for="regional" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                        Regional
                    </label>
                    <input type="text" 
                           id="regional" 
                           name="regional" 
                           value="{{ old('regional', $user->regional) }}" 
                           placeholder="Ej.: Regional Central"
                           class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>

            <!-- Bio -->
            <div>
                <label for="bio" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                    Biografía / Presentación Breve
                </label>
                <textarea id="bio" 
                          name="bio" 
                          rows="3" 
                          placeholder="Cuéntanos sobre tus áreas de interés y enfoque pedagógico o técnico..."
                          class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y">{{ old('bio', $user->bio) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-surface-container-high flex items-center justify-between">
                <a href="{{ route('profile.show') }}" class="px-4 py-2 text-xs font-semibold text-outline hover:text-on-surface">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary-container">
                    Guardar Perfil
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
