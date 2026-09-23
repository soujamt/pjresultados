@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «user-check-01» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

@props([
    'variant' => 'outline',
])

@php
$classes = Flux::classes('shrink-0')
    ->add(match ($variant) {
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
        default => '[:where(&)]:size-6',
    });

$strokeWidth = match ($variant) {
    'solid', 'micro' => 2,
    'mini' => 1.75,
    default => 1.5,
};
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    data-hugeicon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke-width="{{ $strokeWidth }}"
    aria-hidden="true"
    data-slot="icon"
>
    <path d="M3 21.0001C3.28417 17.3057 6.3 14.2192 10.0008 14.0378C10.3134 14.0225 10.6446 14.0096 11 13.9999L11.995 14.0662C13.0751 14.1382 14.0925 14.4366 15 14.9146" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <circle cx="11" cy="6.99988" r="4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M14 19.3332C14 19.3332 14.875 19.3332 15.75 20.9999C15.75 20.9999 18.5294 16.8332 21 15.9999" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
