@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «user-list» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M18 21.0001C17.713 17.269 14.7289 14.3151 10.995 14.0662L10 13.9999C9.64458 14.0096 9.31335 14.0225 9.00082 14.0378C5.3 14.2192 2.28417 17.3057 2 21.0001" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M18 6.49988H22" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M18 9.99988H22" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M20 13.4999H22" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <circle cx="10" cy="6.99988" r="4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
