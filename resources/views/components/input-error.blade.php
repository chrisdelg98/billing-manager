@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm text-red-600']) }}>
        @foreach ((array) $messages as $message)
            <li class="rounded-lg bg-red-50 px-3 py-2">{{ $message }}</li>
        @endforeach
    </ul>
@endif
