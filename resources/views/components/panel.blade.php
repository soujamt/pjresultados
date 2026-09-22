{{-- Superficie blanca para agrupar filtros, fichas o bloques de contenido. --}}
@props([
    'titulo' => null,
    'descripcion' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-lg bg-white p-4 sombra-borde sm:p-5 dark:bg-zinc-900']) }}>
    @if ($titulo)
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</h2>

            @if ($descripcion)
                <p class="mt-0.5 text-sm text-pretty text-zinc-500 dark:text-zinc-400">{{ $descripcion }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
