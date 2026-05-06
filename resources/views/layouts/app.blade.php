<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @endif

        @include('layouts.partials.design-tokens')
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900"
          x-data="{ sidebarExpanded: JSON.parse(localStorage.getItem('sidebar-expanded') ?? 'true'), mobileSidebarOpen: false }"
          x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', JSON.stringify(value)))">
        <div class="min-h-screen md:flex">
            <div x-cloak x-show="mobileSidebarOpen" class="fixed inset-0 z-30 bg-slate-900/40 md:hidden" @click="mobileSidebarOpen = false"></div>

            <aside
                class="fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200 bg-white transition-all duration-200 ease-out md:static md:translate-x-0"
                :class="[
                    mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
                    sidebarExpanded ? 'md:w-72' : 'md:w-20'
                ]"
            >
                @include('layouts.navigation')
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:hidden">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        @click="mobileSidebarOpen = true"
                        aria-label="Abrir menu"
                    >
                        <x-heroicon-o-bars-3 class="h-5 w-5" />
                    </button>
                    <p class="text-sm font-semibold text-slate-900">{{ config('app.name', 'BillingManager') }}</p>
                </div>

                @isset($header)
                    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
                        <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                            <button
                                type="button"
                                class="hidden items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200 md:inline-flex"
                                :aria-label="sidebarExpanded ? 'Colapsar menu' : 'Expandir menu'"
                                @click="sidebarExpanded = !sidebarExpanded"
                            >
                                <x-heroicon-o-chevron-double-left x-show="sidebarExpanded" class="h-4 w-4" />
                                <x-heroicon-o-chevron-double-right x-show="! sidebarExpanded" x-cloak class="h-4 w-4" />
                            </button>

                            <div class="min-w-0">
                                {{ $header }}
                            </div>
                        </div>
                    </header>
                @endisset

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
