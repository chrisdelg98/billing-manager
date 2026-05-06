<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">
            Usuario
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h3 class="text-base font-semibold text-slate-900">Cambiar contrasena</h3>
                    <p class="mt-1 text-sm text-slate-600">Actualiza la contrasena de tu cuenta para mantener acceso seguro.</p>
                </div>

                <div class="px-6 py-6">
                    <x-auth-session-status class="mb-4" :status="session('status') === 'password-updated' ? 'Contrasena actualizada correctamente.' : session('status')" />

                    <form method="POST" action="{{ route('user.password.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="current_password" value="Contrasena actual" />
                            <x-text-input id="current_password" type="password" name="current_password" autocomplete="current-password" required />
                            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" value="Nueva contrasena" />
                            <x-text-input id="password" type="password" name="password" autocomplete="new-password" required />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" value="Confirmar nueva contrasena" />
                            <x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required />
                        </div>

                        <div class="pt-1">
                            <x-primary-button>
                                Guardar contrasena
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
