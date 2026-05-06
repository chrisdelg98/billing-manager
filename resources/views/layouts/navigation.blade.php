<nav class="flex h-full flex-col">
    <div class="border-b border-slate-200 px-4 py-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-900">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white">
                <x-application-logo class="h-5 w-5 fill-current" />
            </span>
            <span class="text-sm font-semibold tracking-wide">{{ config('app.name', 'BillingManager') }}</span>
        </a>
    </div>

    <div class="border-b border-slate-200 px-4 py-4">
        <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Usuario activo</p>
        <p class="mt-2 text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
        <p class="text-xs text-slate-600">{{ Auth::user()->email }}</p>
    </div>

    <div class="flex-1 px-3 py-4">
        <p class="px-2 text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Secciones</p>

        <div class="mt-3 space-y-1">
            <a href="{{ route('dashboard') }}"
               class="ui-btn flex items-center rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                Dashboard
            </a>
        </div>
    </div>

    <div class="mt-auto border-t border-slate-200 px-3 py-4">
        <p class="px-2 text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Cuenta</p>

        <div class="mt-3 space-y-2">
            <a href="{{ route('user.password.edit') }}"
               class="ui-btn flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Mi cuenta
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    Cerrar sesion
                </button>
            </form>
        </div>
    </div>
</nav>
