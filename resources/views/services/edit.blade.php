<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Editar servicio</h2>
    </x-slot>

    <div class="mx-auto w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-6">
        <form method="POST" action="{{ route('servicios.update', $service) }}">
            @csrf
            @method('PUT')
            @include('services._form', ['submitLabel' => 'Guardar cambios'])
        </form>
    </div>
</x-app-layout>
