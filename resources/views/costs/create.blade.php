<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Nuevo costo</h2>
    </x-slot>

    <div class="mx-auto w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-6">
        <form method="POST" action="{{ route('costos.store') }}">
            @include('costs._form', ['submitLabel' => 'Crear costo'])
        </form>
    </div>
</x-app-layout>
