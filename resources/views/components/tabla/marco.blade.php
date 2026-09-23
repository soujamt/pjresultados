{{--
    Superficie de las tablas del sistema. La cabecera en versalitas, el margen
    interior de la primera y la última celda y la paginación los pone la
    clase .tabla-marco en app.css, así cada pantalla solo envuelve su tabla.

    «compacta» baja un punto la letra, para tablas largas con muchas columnas.
--}}
@props([
    'compacta' => false,
])

<div {{ $attributes->class(['tabla-compacta' => $compacta])->merge(['class' => 'tabla-marco overflow-hidden rounded-lg bg-white sombra-borde dark:bg-zinc-900']) }}>
    {{ $slot }}
</div>
