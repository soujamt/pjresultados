@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «arrow-left-01» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M15 6C15 6 9.00001 10.4189 9 12C8.99999 13.5812 15 18 15 18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
