<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
        <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">

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
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div class="relative min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -top-16 -left-16 h-72 w-72 rounded-full bg-sky-200/50 blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 h-80 w-80 rounded-full bg-blue-200/40 blur-3xl"></div>
            </div>

            <div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6">
                <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white/95 p-7 shadow-xl backdrop-blur-sm sm:p-8">
                    <div class="mb-6 flex items-center gap-3">
                        <a href="{{ route('login') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-white">
                            <x-application-logo class="h-6 w-6 fill-current" />
                        </a>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">{{ config('app.name', 'BillingManager') }}</p>
                            <p class="text-sm text-slate-600">Panel interno</p>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
