{{-- Marca del sistema: la balanza sobre el guinda institucional. --}}
@props([
    'icono' => 'size-5',
])

<div {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-lg bg-pj-700 text-white shadow-sm dark:bg-pj-600']) }}>
    <flux:icon.scale variant="outline" :class="$icono" />
</div>
