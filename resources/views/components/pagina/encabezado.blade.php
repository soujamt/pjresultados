{{-- Encabezado de cada pantalla: título, una línea de contexto y las acciones a la derecha. --}}
@props([
    'titulo',
    'bajada' => null,
])

<header {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-x-6 gap-y-4']) }}>
    <div class="min-w-0 max-w-3xl">
        <h1 class="text-xl leading-tight font-semibold tracking-tight text-balance text-zinc-900 dark:text-white">
            {{ $titulo }}
        </h1>

        @if ($bajada)
            <p class="mt-1.5 text-sm leading-relaxed text-pretty text-zinc-600 dark:text-zinc-400">{{ $bajada }}</p>
        @endif
    </div>

    @isset($acciones)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $acciones }}
        </div>
    @endisset
</header>
