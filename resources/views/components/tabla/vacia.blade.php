{{-- Fila de «no hay nada que mostrar». El slot suma una explicación o una acción. --}}
@props([
    'columnas',
    'mensaje' => 'No hay registros que mostrar.',
    'icono' => 'folder-01',
])

<flux:table.row>
    <flux:table.cell :colspan="$columnas" class="py-14! text-center whitespace-normal">
        <div class="mx-auto flex max-w-md flex-col items-center gap-3">
            <div class="flex size-11 items-center justify-center rounded-full bg-zinc-100 dark:bg-white/5">
                <flux:icon :icon="$icono" class="size-5 text-zinc-500 dark:text-zinc-400" />
            </div>

            <p class="text-sm font-medium text-pretty text-zinc-700 dark:text-zinc-200">{{ $mensaje }}</p>

            @if ($slot->isNotEmpty())
                <div class="text-sm leading-relaxed text-pretty text-zinc-500 dark:text-zinc-400">{{ $slot }}</div>
            @endif
        </div>
    </flux:table.cell>
</flux:table.row>
