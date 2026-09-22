{{--
    Superficie de las tablas del sistema. La cabecera en versalitas, el margen
    interior de la primera y la última celda y la paginación los pone la
    clase .tabla-marco en app.css, así cada pantalla solo envuelve su tabla.
--}}
<div {{ $attributes->merge(['class' => 'tabla-marco overflow-hidden rounded-lg bg-white sombra-borde dark:bg-zinc-900']) }}>
    {{ $slot }}
</div>
