@extends('layouts.app')

@section('title', 'Notificaciones - INNOVATEP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Notificaciones</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Novedades sobre tus propuestas, valoraciones y comentarios</p>
        </div>

        @if(auth()->user()->unreadNotifications->isNotEmpty())
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-primary rounded-xl transition-colors">
                Marcar todas como leídas
            </button>
        </form>
        @endif
    </div>

    <!-- Notifications Groups -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-6">
        
        @if($notifications->isNotEmpty())
            <!-- Group: Hoy -->
            @if($today->isNotEmpty())
            <div class="space-y-3">
                <h3 class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider">Hoy</h3>
                <div class="space-y-2">
                    @foreach($today as $notification)
                        @include('notifications.item', ['notification' => $notification])
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Group: Esta Semana -->
            @if($thisWeek->isNotEmpty())
            <div class="space-y-3">
                <h3 class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider">Esta Semana</h3>
                <div class="space-y-2">
                    @foreach($thisWeek as $notification)
                        @include('notifications.item', ['notification' => $notification])
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Group: Anteriores -->
            @if($earlier->isNotEmpty())
            <div class="space-y-3">
                <h3 class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider">Anteriores</h3>
                <div class="space-y-2">
                    @foreach($earlier as $notification)
                        @include('notifications.item', ['notification' => $notification])
                    @endforeach
                </div>
            </div>
            @endif
        @else
        <!-- Empty State -->
        <div class="py-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-surface-container flex items-center justify-center mx-auto mb-3 text-outline">
                <span class="material-symbols-outlined text-3xl">notifications_off</span>
            </div>
            <h3 class="font-headline font-bold text-base text-on-surface">No tienes notificaciones pendientes</h3>
            <p class="text-xs text-on-surface-variant mt-1">Te avisaremos cuando alguien vote o comente tus propuestas.</p>
        </div>
        @endif

    </div>

</div>
@endsection
