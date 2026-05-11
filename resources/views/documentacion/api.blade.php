<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="text-xl font-semibold leading-tight text-slate-900">
                Documentacion API
            </h2>
            <p class="text-sm text-slate-600">Guia de consumo para validar licencias desde sistemas externos.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Endpoint oficial</h3>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p><span class="font-semibold text-slate-900">Metodo:</span> GET</p>
                <p><span class="font-semibold text-slate-900">Ruta:</span> /api/v1/license/status</p>
                <p><span class="font-semibold text-slate-900">URL sugerida:</span> {{ url('/api/v1/license/status') }}</p>
                <p><span class="font-semibold text-slate-900">Rate limit:</span> throttle:license-api</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Headers requeridos</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    <li><span class="font-semibold text-slate-900">X-License-Code</span>: codigo de licencia (max 64)</li>
                    <li><span class="font-semibold text-slate-900">X-License-Secret</span>: secreto de licencia (max 255)</li>
                    <li><span class="font-semibold text-slate-900">Accept</span>: application/json (recomendado)</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Ejemplo cURL</h3>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100"><code>curl -X GET "{{ url('/api/v1/license/status') }}" \
  -H "Accept: application/json" \
  -H "X-License-Code: LIC-XXXXXXXXXXXX" \
  -H "X-License-Secret: TU_SECRETO"</code></pre>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Campos que puedes consumir (respuesta 200)</h3>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-900">Nivel raiz</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <li>ok (boolean)</li>
                        <li>license_code (string)</li>
                        <li>status (string)</li>
                        <li>can_access (boolean)</li>
                        <li>reason_code (string)</li>
                        <li>days_remaining (number|null)</li>
                        <li>expires_on (date|null)</li>
                        <li>checked_at (ISO8601)</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-900">service</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <li>service.name (string|null)</li>
                    </ul>

                    <p class="mt-4 text-sm font-semibold text-slate-900">coverage</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <li>coverage.last_covered_period (YYYY-MM|null)</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-slate-200 p-4 md:col-span-2">
                    <p class="text-sm font-semibold text-slate-900">subscription</p>
                    <ul class="mt-2 grid gap-1 text-sm text-slate-700 sm:grid-cols-2 lg:grid-cols-3">
                        <li>subscription.id (number)</li>
                        <li>subscription.name (string)</li>
                        <li>subscription.billing_cycle (string)</li>
                        <li>subscription.amount (decimal string)</li>
                        <li>subscription.currency (string)</li>
                        <li>subscription.is_active (boolean)</li>
                        <li>subscription.has_trial (boolean)</li>
                        <li>subscription.trial_ends_at (date|null)</li>
                        <li>subscription.trial_days_remaining (number|null)</li>
                        <li>subscription.next_renewal_at (date|null)</li>
                        <li>subscription.renewal_days_remaining (number|null)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Respuesta ejemplo (200)</h3>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100"><code>{
  "ok": true,
  "license_code": "LIC-XXXXXXXXXXXX",
  "status": "active",
  "can_access": true,
  "reason_code": "paid_current",
    "days_remaining": 30,
    "expires_on": "2026-06-10",
  "checked_at": "2026-05-11T14:20:00Z",
  "service": {
    "name": "Mi Servicio"
  },
  "subscription": {
    "id": 15,
    "name": "Plan Pro",
    "billing_cycle": "monthly",
    "amount": "29.00",
    "currency": "USD",
    "is_active": true,
    "has_trial": false,
    "trial_ends_at": null,
    "trial_days_remaining": null,
    "next_renewal_at": "2026-06-10",
    "renewal_days_remaining": 30
  },
  "coverage": {
    "last_covered_period": "2026-05"
  }
}</code></pre>
        </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Regla de periodo de prueba</h3>
                <p class="mt-3 text-sm text-slate-700">Si una suscripcion tiene periodo de prueba y no se define una fecha manual de renovacion, la fecha de renovacion se fija en la misma fecha fin de prueba. Esto evita dias extra de acceso y reduce confusion con el cliente final.</p>
            </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Codigos de error esperados</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">HTTP</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">error.code</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Descripcion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-3 py-2 text-slate-700">401</td>
                            <td class="px-3 py-2 text-slate-700">invalid_credentials</td>
                            <td class="px-3 py-2 text-slate-700">Secreto de licencia invalido.</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-slate-700">403</td>
                            <td class="px-3 py-2 text-slate-700">revoked</td>
                            <td class="px-3 py-2 text-slate-700">Licencia revocada.</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-slate-700">404</td>
                            <td class="px-3 py-2 text-slate-700">not_found</td>
                            <td class="px-3 py-2 text-slate-700">Licencia inexistente o API deshabilitada.</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-slate-700">422</td>
                            <td class="px-3 py-2 text-slate-700">invalid_request</td>
                            <td class="px-3 py-2 text-slate-700">Headers requeridos faltantes.</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-slate-700">429</td>
                            <td class="px-3 py-2 text-slate-700">too_many_requests</td>
                            <td class="px-3 py-2 text-slate-700">Limite de peticiones excedido.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-semibold">Buenas practicas de consumo</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Permite acceso solo cuando can_access sea true.</li>
                <li>No almacenes ni imprimas X-License-Secret en logs.</li>
                <li>Usa timeout entre 2 y 5 segundos y cache de 30 a 120 segundos.</li>
            </ul>
        </div>
    </div>
</x-app-layout>
