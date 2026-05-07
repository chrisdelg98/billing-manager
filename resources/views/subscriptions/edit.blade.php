<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Editar suscripcion</h2>
    </x-slot>

    <div class="mx-auto w-full max-w-4xl space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <form method="POST" action="{{ route('suscripciones.update', $subscription) }}">
                @csrf
                @method('PUT')
                @include('subscriptions._form', ['submitLabel' => 'Guardar cambios'])
            </form>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Gestion de API de licencia</h3>
                    <p class="mt-1 text-sm text-slate-600">Genera, rota y valida credenciales para consultar el estado de esta suscripcion desde otros sistemas.</p>
                </div>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $subscription->license_api_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ $subscription->license_api_enabled ? 'API habilitada' : 'API deshabilitada' }}
                </span>
            </div>

            @if (session('license_plain_secret'))
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <p class="font-semibold">Secreto generado (mostrar una sola vez):</p>
                    <p class="mt-1 break-all font-mono">{{ session('license_plain_secret') }}</p>
                    <p class="mt-1 text-xs">Guardalo de inmediato en el sistema externo. No podras volver a verlo despues.</p>
                </div>
            @endif

            @if ($errors->has('license_api'))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first('license_api') }}
                </div>
            @endif

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Codigo licencia</p>
                    <p class="mt-1 break-all font-mono text-sm text-slate-900">{{ $subscription->license_code ?: 'No generado' }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Secreto actual</p>
                    <p class="mt-1 text-sm text-slate-900">
                        {{ $subscription->license_secret_hint ? 'Configurado (termina en '.$subscription->license_secret_hint.')' : 'No generado' }}
                    </p>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Ultima rotacion</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $subscription->license_key_rotated_at?->format('Y-m-d H:i') ?: '-' }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Ultimo uso API</p>
                    <p class="mt-1 text-sm text-slate-900">{{ $subscription->license_last_used_at?->format('Y-m-d H:i') ?: '-' }}</p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('suscripciones.licencia.rotate', $subscription) }}">
                    @csrf
                    <button type="submit" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" @disabled(! $subscription->license_api_enabled)>
                        {{ $subscription->license_secret_hash ? 'Rotar secreto' : 'Generar secreto' }}
                    </button>
                </form>

                @if ($subscription->license_key_revoked_at)
                    <form method="POST" action="{{ route('suscripciones.licencia.reactivate', $subscription) }}">
                        @csrf
                        <button type="submit" class="ui-btn inline-flex items-center rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50" @disabled(! $subscription->license_api_enabled)>
                            Reactivar acceso API
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('suscripciones.licencia.revoke', $subscription) }}" onsubmit="return confirm('Se revocara el acceso de API para esta suscripcion. Continuar?')">
                        @csrf
                        <button type="submit" class="ui-btn inline-flex items-center rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50" @disabled(! $subscription->license_api_enabled)>
                            Revocar acceso API
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
