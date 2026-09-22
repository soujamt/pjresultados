{{-- Dropzone de subida con barra de progreso (eventos nativos de Livewire). --}}
{{-- El slot opcional se renderiza debajo (previews, archivo seleccionado, errores). --}}
@props([
    'model',                 // propiedad wire del archivo (ej: 'form.archivo')
    'accept' => null,        // ej: 'application/pdf' | 'image/*'
    'multiple' => false,
    'titulo' => 'Click para subir archivo',
    'subtitulo' => null,
])

{{-- Sin wrapper: el dropzone y el slot quedan como hermanos directos del
     contenedor padre (flux:field), igual que el markup original. --}}
<div
    x-data="{ subiendo: false, progreso: 0 }"
    x-on:livewire-upload-start="subiendo = true; progreso = 0"
    x-on:livewire-upload-finish="subiendo = false"
    x-on:livewire-upload-error="subiendo = false"
    x-on:livewire-upload-progress="progreso = $event.detail.progress"
    class="relative flex w-full cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-4 py-7 transition-colors duration-150 hover:border-zinc-400 hover:bg-zinc-100/70 dark:border-white/15 dark:bg-white/[0.02] dark:hover:bg-white/[0.04]"
>
    <input
        type="file"
        wire:model="{{ $model }}"
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($multiple) multiple @endif
        x-bind:disabled="subiendo"
        class="absolute inset-0 cursor-pointer opacity-0"
    />

    {{-- Estado normal --}}
    <div x-show="!subiendo" class="flex flex-col items-center text-center">
        <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-white sombra-borde dark:bg-zinc-800">
            <flux:icon.upload-04 class="size-5 text-zinc-500 dark:text-zinc-400" />
        </div>
        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $titulo }}</p>
        @if ($subtitulo)
            <p class="mt-1 text-xs text-zinc-500">{{ $subtitulo }}</p>
        @endif
    </div>

    {{-- Estado subiendo: barra de progreso --}}
    <div x-show="subiendo" x-cloak class="flex w-full flex-col items-center">
        <div class="mb-2 flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            <flux:icon.loading class="size-4" />
            <span>Subiendo... <span x-text="progreso + '%'"></span></span>
        </div>
        <div class="h-2 w-full max-w-xs overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div
                class="h-full rounded-full bg-accent transition-[width] duration-150 ease-out"
                x-bind:style="`width: ${progreso}%`"
            ></div>
        </div>
    </div>
</div>

{{ $slot }}
