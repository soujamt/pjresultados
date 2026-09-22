@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «checkmark-circle-02» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z" stroke="currentColor" />
    <path d="M8 12.5L10.5 15L16 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
