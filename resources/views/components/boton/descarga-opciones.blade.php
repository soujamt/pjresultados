{{--
    Botón de descarga con varias formas del mismo archivo: al pulsarlo abre un
    menú y cada opción descarga su versión. Mientras se genera, el ícono gira,
    igual que en <x-boton.descarga>. Usa el Alpine.data «descarga» de app.js.

    opciones: lista de ['etiqueta' => ..., 'descripcion' => ..., 'href' => ...].
    Sin texto queda como botón de ícono; en ese caso pasa un aria-label.
--}}
@props([
    'icon',
    'opciones',
    'variant' => 'outline',
    'color' => null,
    'size' => 'base',
])

@php
    $soloIcono = $slot->isEmpty();
    $variante = $soloIcono ? 'mini' : 'micro';
@endphp

<div x-data="descarga()" class="inline-flex">
    <flux:dropdown position="bottom" align="end">
        <flux:button
            :variant="$variant"
            :color="$color"
            :size="$size"
            :square="$soloIcono"
            x-bind:aria-busy="descargando"
            x-bind:class="descargando && 'pointer-events-none'"
            {{ $attributes->class(['ps-3! pe-2.5!' => ! $soloIcono]) }}
        >
            <flux:icon :icon="$icon" :variant="$variante" x-show="! descargando" />
            <flux:icon.loading :variant="$variante" x-show="descargando" x-cloak />
            @unless ($soloIcono)
                {{ $slot }}
                <flux:icon.arrow-down-01 variant="micro" class="opacity-80" />
            @endunless
        </flux:button>

        <flux:menu class="min-w-72">
            @foreach ($opciones as $opcion)
                <flux:menu.item x-on:click="descargar({{ Illuminate\Support\Js::from($opcion['href']) }})">
                    <div class="py-0.5">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $opcion['etiqueta'] }}</div>
                        @if (! empty($opcion['descripcion']))
                            <div class="mt-0.5 text-xs text-pretty text-zinc-500 dark:text-zinc-400">{{ $opcion['descripcion'] }}</div>
                        @endif
                    </div>
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>
