@blaze(fold: true)

{{-- Hugeicons Free 4.3.5 · «user-multiple» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

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
    <path d="M16 21C15.7668 18.0685 13.3422 15.7477 10.3085 15.5521L9.49999 15.5C9.21121 15.5076 8.94209 15.5178 8.68816 15.5298C5.68124 15.6723 3.23089 18.0974 3 21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M12.75 9.75C12.75 11.5449 11.2949 13 9.5 13C7.70508 13 6.25 11.5449 6.25 9.75C6.25 7.95507 7.70508 6.5 9.5 6.5C11.2949 6.5 12.75 7.95507 12.75 9.75Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M21 17.501C20.7709 14.6314 18.4541 12.2748 15.5 11.9961" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M15.9877 9C16.8965 8.42434 17.5001 7.40788 17.5001 6.25C17.5001 4.45507 16.0496 3 14.2602 3C13.3811 3 12.5838 3.35121 12.0001 3.92139" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
</svg>
