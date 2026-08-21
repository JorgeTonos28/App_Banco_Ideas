@extends('layouts.app')

@section('title', 'Perfil de ' . $targetUser->name . ' - INNOVATEP')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    <!-- Profile Header Card -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary-fixed/20 blur-3xl rounded-full pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <img src="{{ $targetUser->avatar_url }}" alt="{{ $targetUser->name }}" class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl object-cover ring-4 ring-white shadow-md">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">{{ $targetUser->name }}</h1>
                        @if($targetUser->isAdmin())
                        <span class="px-2.5 py-0.5 rounded-full bg-primary text-white text-[10px] font-mono-tech font-bold uppercase">Admin</span>
                        @endif
                    </div>
                    <p class="text-sm font-semibold text-primary mt-1">{{ $targetUser->job_title ?: 'Colaborador INFOTEP' }}</p>
                    <p class="text-xs text-on-surface-variant font-mono-tech mt-0.5">
                        {{ $targetUser->department ?: 'Departamento General' }} • {{ $targetUser->regional ?: 'Oficina Nacional' }}
                    </p>
                </div>
            </div>

            @if(auth()->check() && auth()->id() === $targetUser->id)
            <a href="{{ route('profile.edit') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-xs font-semibold transition-colors">
                <span class="material-symbols-outlined text-base">settings</span>
                <span>Editar Perfil</span>
            </a>
            @endif
        </div>

        @if($targetUser->bio)
        <div class="relative z-10 mt-6 pt-6 border-t border-surface-container-high/60 text-xs sm:text-sm text-on-surface-variant leading-relaxed">
            {{ $targetUser->bio }}
        </div>
        @endif
    </div>

    <!-- Impact & Recognition Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-surface-container-high/80 shadow-2xs text-center">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-outline block">Ideas Propuestas</span>
            <span class="font-headline font-extrabold text-2xl sm:text-3xl text-primary mt-1 block">{{ $ideasCount }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-surface-container-high/80 shadow-2xs text-center">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-emerald-700 block">Implementadas</span>
            <span class="font-headline font-extrabold text-2xl sm:text-3xl text-emerald-700 mt-1 block">{{ $implementedCount }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-surface-container-high/80 shadow-2xs text-center">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-secondary block">Votos Recibidos</span>
            <span class="font-headline font-extrabold text-2xl sm:text-3xl text-secondary mt-1 block">{{ $totalVotesReceived }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-5 border border-surface-container-high/80 shadow-2xs text-center">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-primary block">Score Participación</span>
            <span class="font-headline font-extrabold text-2xl sm:text-3xl text-primary mt-1 block">{{ $participationScore }}</span>
        </div>
    </div>

    <!-- Recognition Badges Section -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-headline font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-2xl" style="font-variation-settings: 'FILL' 1;">military_tech</span>
                <span>Insignias de Reconocimiento</span>
            </h2>
            <span class="text-xs font-mono-tech text-outline font-bold">{{ count($badges) }} obtenidas</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
            @forelse($badges as $badge)
            <div class="p-4 rounded-2xl bg-surface-container-low border border-surface-container-high flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $badge['color'] }} flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-xl">{{ $badge['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-bold text-on-surface truncate">{{ $badge['name'] }}</h4>
                    <p class="text-[10px] text-on-surface-variant line-clamp-1">{{ $badge['description'] }}</p>
                </div>
            </div>
            @empty
            <p class="text-xs text-on-surface-variant col-span-full py-4 text-center">
                Aún no ha desbloqueado insignias de innovación.
            </p>
            @endforelse
        </div>
    </div>

    <!-- Public Contributions Section -->
    <div class="space-y-4">
        <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-2xl">folder_special</span>
            <span>Contribuciones e Ideas Publicadas</span>
        </h2>

        @if($contributions->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($contributions as $idea)
                <x-idea-card :idea="$idea" />
            @endforeach
        </div>
        @else
        <div class="bg-surface-container-lowest rounded-3xl p-8 text-center border border-surface-container-high">
            <p class="text-xs text-on-surface-variant">Este colaborador aún no ha publicado ideas públicas.</p>
        </div>
        @endif
    </div>

</div>
@endsection
