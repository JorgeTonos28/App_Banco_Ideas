<!DOCTYPE html>
<html lang="es" class="h-full bg-surface">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'INNOVATEP Ideas - Banco Institucional de Innovación INFOTEP')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@500;600;700;800&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface flex flex-col" x-data="{ mobileMenuOpen: false, searchOpen: false }">

    <!-- Global Toast Alerts -->
    @if(session('success') || session('error') || session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="fixed bottom-20 md:bottom-6 right-6 z-50 max-w-md transition-all duration-300 transform" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95">
            <div class="flex items-center gap-3 p-4 rounded-2xl shadow-xl backdrop-blur-md {{ session('error') ? 'bg-error text-white' : 'bg-primary text-white border border-primary-container' }}">
                <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">
                    {{ session('error') ? 'error' : 'check_circle' }}
                </span>
                <div class="flex-1 text-sm font-medium">
                    {{ session('success') ?? session('error') ?? session('status') }}
                </div>
                <button @click="show = false" class="text-white/80 hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>
    @endif

    <div class="flex min-h-screen">
        <!-- Desktop Sidebar (Navigation) -->
        @include('layouts.sidebar')

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 md:pl-72">
            <!-- Top Header Navbar -->
            @include('layouts.navbar')

            <!-- Main Page Content -->
            <main class="flex-1 pb-24 md:pb-12 pt-4 px-4 sm:px-6 lg:px-8 max-w-7xl w-full mx-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Bar -->
    @include('layouts.mobile-nav')

    <!-- Global Search Modal -->
    @include('components.global-search-modal')

    @auth
        <div class="hidden" x-data="taskBrowserReminders({ endpoint: @js(route('api.tasks.reminders.browser')) })"></div>
    @endauth

    @stack('scripts')
</body>
</html>
