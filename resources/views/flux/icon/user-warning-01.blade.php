@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «user-warning-01» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M20 21.0002C19.713 17.2691 16.7289 14.3153 12.995 14.0663L12 14C11.6446 14.0097 11.3133 14.0226 11.0008 14.0379C7.29998 14.2193 4.28416 17.3058 3.99998 21.0002" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M20 6V10M20 13V13.01" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
