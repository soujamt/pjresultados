{{--
    Botón que descarga un archivo mostrando que se está generando: mientras el
    servidor arma el PDF o el Excel, el ícono gira y el botón no acepta otro
    clic. Usa el Alpine.data «descarga» de resources/js/app.js.

    Sin texto queda como botón de ícono; en ese caso pasa un aria-label.
--}}
@props([
    'href',
    'icon',
    'variant' => 'outline',
    'color' => null,
    'size' => 'base',
])

@php
    $soloIcono = $slot->isEmpty();
    $variante = $soloIcono ? 'mini' : 'micro';
@endphp

<flux:button
    :variant="$variant"
    :color="$color"
    :size="$size"
    :square="$soloIcono"
    :x-data="'descarga('.Illuminate\Support\Js::from($href).')'"
    x-on:click="descargar()"
    x-bind:aria-busy="descargando"
    x-bind:class="descargando && 'pointer-events-none'"
    {{ $attributes->class(['ps-3!' => ! $soloIcono]) }}
>
    <flux:icon :icon="$icon" :variant="$variante" x-show="! descargando" />
    <flux:icon.loading :variant="$variante" x-show="descargando" x-cloak />
    {{ $slot }}
</flux:button>
