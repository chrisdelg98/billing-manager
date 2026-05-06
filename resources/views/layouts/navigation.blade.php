<nav class="relative flex h-full flex-col">
    <div class="flex h-16 items-center border-b border-slate-200 px-3">
        <div class="flex w-full items-center justify-between gap-2">
            <a href="{{ route('dashboard') }}" class="inline-flex min-w-0 items-center gap-2 text-slate-900 md:w-full" :class="sidebarExpanded ? 'md:justify-start' : 'md:justify-center'">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-white">
                    <x-application-logo class="h-5 w-5 fill-current" />
                </span>
                <span x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity class="truncate text-sm font-semibold tracking-wide">{{ config('app.name', 'BillingManager') }}</span>
            </a>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200 md:hidden"
                @click="mobileSidebarOpen = false"
                aria-label="Cerrar menu"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="border-b border-slate-200 px-3 py-4">
        <div class="flex items-center gap-3" :class="sidebarExpanded ? '' : 'justify-center'">
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                <x-heroicon-o-user-circle class="h-6 w-6" />
            </span>

            <div x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
            </div>
        </div>
    </div>

    <div class="flex-1 px-2 py-4">
        <p x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity class="px-2 text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Secciones</p>

        <div class="mt-3 space-y-1">
            <a
                href="{{ route('dashboard') }}"
                title="Dashboard"
                class="ui-btn flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                :class="sidebarExpanded || mobileSidebarOpen ? 'justify-start' : 'justify-center'"
            >
                <x-heroicon-o-home class="h-5 w-5 shrink-0" />
                <span x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity>Dashboard</span>
            </a>
        </div>
    </div>

    <div class="mt-auto border-t border-slate-200 px-2 py-4">
        <p x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity class="px-2 text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Cuenta</p>

        <div class="mt-3 space-y-2">
            <a
                href="{{ route('user.password.edit') }}"
                title="Mi cuenta"
                class="ui-btn flex items-center gap-3 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                :class="sidebarExpanded || mobileSidebarOpen ? 'justify-start' : 'justify-center'"
            >
                <x-heroicon-o-user class="h-5 w-5 shrink-0" />
                <span x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity>Mi cuenta</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    title="Cerrar sesion"
                    class="ui-btn flex w-full items-center gap-3 rounded-lg border border-slate-300 px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    :class="sidebarExpanded || mobileSidebarOpen ? 'justify-start' : 'justify-center'"
                >
                    <x-heroicon-o-arrow-left-on-rectangle class="h-5 w-5 shrink-0" />
                    <span x-show="sidebarExpanded || mobileSidebarOpen" x-transition.opacity>Cerrar sesion</span>
                </button>
            </form>
        </div>
    </div>
</nav>
