@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «arrow-down-01» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M18 9.00005C18 9.00005 13.5811 15 12 15C10.4188 15 6 9 6 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
