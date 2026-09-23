@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «user-remove-01» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M12.495 14.0662L11.5 13.9999C11.1446 14.0096 10.8134 14.0225 10.5008 14.0378C6.8 14.2192 3.78417 17.3057 3.5 21.0001" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M20.5 15.9999L18 18.4999M18 18.4999L15.5 20.9999M18 18.4999L20.5 20.9999M18 18.4999L15.5 15.9999" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <circle cx="11.5" cy="6.99988" r="4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
