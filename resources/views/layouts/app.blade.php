<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $sectionHelp = null;

        if (request()->routeIs('dashboard')) {
            $sectionHelp = [
                'title' => 'Guia rapida del Dashboard',
                'summary' => 'Este panel resume estado operativo y financiero del periodo actual.',
                'points' => [
                    'Servicios: cantidad total de sistemas/clientes registrados.',
                    'Suscripciones activas: contratos recurrentes en curso.',
                    'Ingresos del mes: pagos cobrados en el mes actual.',
                    'Margen proyectado: ingreso estimado menos costos estimados.',
                ],
            ];
        } elseif (request()->routeIs('servicios.*')) {
            $sectionHelp = [
                'title' => 'Como registrar un Servicio',
                'summary' => 'Un Servicio representa tu sistema o cuenta final. Ejemplo: CLINEXUS.',
                'points' => [
                    'Nombre: nombre del sistema o cliente (ejemplo: CLINEXUS).',
                    'Tipo: categoria del servicio (SaaS, VPS, Dominio, etc.).',
                    'Proveedor: empresa/plataforma donde corre o se contrata (Hostinger, AWS, Cloudflare, etc.).',
                    'Responsable: persona interna a cargo de la operacion.',
                    'Luego de crear el servicio, agrega suscripciones y pagos asociados.',
                ],
            ];
        } elseif (request()->routeIs('suscripciones.*')) {
            $sectionHelp = [
                'title' => 'Como usar Suscripciones',
                'summary' => 'Aqui registras contratos recurrentes de cada servicio.',
                'points' => [
                    'Cada suscripcion pertenece a un servicio.',
                    'Define ciclo, monto y proxima renovacion.',
                    'Periodo de prueba opcional: durante la prueba no cuenta como recurrente y al vencer pasa a normal automaticamente.',
                    'Activa/Inactiva para controlar proyeccion de ingresos.',
                ],
            ];
        } elseif (request()->routeIs('pagos.*')) {
            $sectionHelp = [
                'title' => 'Como usar Pagos',
                'summary' => 'Aqui registras cobros realmente recibidos.',
                'points' => [
                    'Selecciona servicio y, opcionalmente, la suscripcion asociada.',
                    'Registra fecha, monto, metodo y referencia.',
                    'Estos datos impactan ingresos reales y reportes financieros.',
                ],
            ];
        } elseif (request()->routeIs('costos.*')) {
            $sectionHelp = [
                'title' => 'Como usar Costos',
                'summary' => 'Aqui registras gastos operativos propios del negocio.',
                'points' => [
                    'Costo directo: imputado de forma puntual.',
                    'Costo compartido: se reparte entre servicios con asignaciones.',
                    'Define ciclo y renovacion para proyecciones mas precisas.',
                ],
            ];
        } elseif (request()->routeIs('finanzas.*')) {
            $sectionHelp = [
                'title' => 'Como leer Finanzas',
                'summary' => 'Consolida ingresos, costos y margen para analisis de rendimiento.',
                'points' => [
                    'Ingreso real: pagos efectivamente cobrados.',
                    'Ingreso recurrente: proyeccion basada en suscripciones activas.',
                    'Generar snapshot: congela historico por periodo y servicio.',
                ],
            ];
        } elseif (request()->routeIs('catalogos.*')) {
            $sectionHelp = [
                'title' => 'Como usar Catalogos',
                'summary' => 'Centraliza listas reutilizables para mantener datos consistentes.',
                'points' => [
                    'Agrega tipos, proveedores y monedas que uses frecuentemente.',
                    'Reordena con drag and drop para priorizar opciones.',
                    'Activa/Inactiva para controlar que aparece en formularios.',
                ],
            ];
        }
    @endphp
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
        <body class="font-sans antialiased bg-slate-100 text-slate-900"
            x-data="{ sidebarExpanded: JSON.parse(localStorage.getItem('sidebar-expanded') ?? 'true'), mobileSidebarOpen: false, sectionHelpOpen: false }"
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

                            <div class="min-w-0 flex-1">
                                {{ $header }}
                            </div>

                            @if ($sectionHelp)
                                <button
                                    type="button"
                                    class="ui-btn inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    @click="sectionHelpOpen = true"
                                >
                                    <x-heroicon-o-information-circle class="h-4 w-4" />
                                    <span>Info</span>
                                </button>
                            @endif
                        </div>
                    </header>
                @endisset

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @if ($sectionHelp)
            <div
                x-cloak
                x-show="sectionHelpOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4"
                @click.self="sectionHelpOpen = false"
                @keydown.escape.window="sectionHelpOpen = false"
            >
                <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">{{ $sectionHelp['title'] }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $sectionHelp['summary'] }}</p>
                        </div>
                        <button type="button" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50" @click="sectionHelpOpen = false" aria-label="Cerrar">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="px-5 py-4">
                        <ul class="space-y-2 text-sm text-slate-700">
                            @foreach ($sectionHelp['points'] as $point)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                        <button type="button" class="ui-btn rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" @click="sectionHelpOpen = false">
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </body>
</html>
