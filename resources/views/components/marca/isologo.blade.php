{{-- Isologo del Poder Judicial: solo el símbolo, sin el nombre. --}}
@props([
    'alt' => 'Poder Judicial del Perú',
])

<img
    src="{{ asset('img/pj-isologo.png') }}"
    alt="{{ $alt }}"
    width="160"
    height="148"
    {{ $attributes->merge(['class' => 'object-contain']) }}
/>
