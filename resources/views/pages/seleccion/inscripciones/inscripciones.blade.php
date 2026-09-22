<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Inscripciones"
        bajada="Postulantes que participan de la evaluación técnica, según el listado del Anexo 06-A."
    >
        <x-slot:acciones>
            @if ($proceso)
                @can(App\Enums\Permiso::InscripcionesImportar->value)
                    <flux:button wire:click="abrirImportacion" variant="primary" icon="arrow-up-tray">Importar desde Excel</flux:button>
                @endcan
            @endif
        </x-slot:acciones>
    </x-pagina.encabezado>

    <div class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-64">
            <flux:select wire:model.live="codigoProceso" label="Proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- La lista cambia con el proceso: el wire:key evita que el navegador conserve una opción que ya no existe. --}}
        <div class="w-full sm:w-96" wire:key="puestos-{{ $proceso?->id_pro }}">
            <flux:select wire:model.live="filtroPuesto" label="Puesto">
                <flux:select.option value="">Todos los puestos</flux:select.option>
                @foreach ($puestos as $puesto)
                    <flux:select.option :value="$puesto->id_pue">{{ $puesto->denominacion() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="busqueda" icon="magnifying-glass" placeholder="Buscar por DNI o nombre" clearable />
        </div>
    </div>

    @if ($ultimaImportacion && $ultimaImportacion['errores'] !== [])
        <flux:callout icon="exclamation-triangle" variant="danger">
            <flux:callout.heading>{{ $ultimaImportacion['mensaje'] }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="mt-1 list-disc space-y-0.5 ps-5">
                    @foreach (array_slice($ultimaImportacion['errores'], 0, 50) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                @if (count($ultimaImportacion['errores']) > 50)
                    <p class="mt-2">…y {{ count($ultimaImportacion['errores']) - 50 }} observación(es) más.</p>
                @endif
            </flux:callout.text>
        </flux:callout>
    @endif

    @if (! $proceso)
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>Registra o elige un proceso de selección para ver sus inscripciones.</flux:callout.text>
        </flux:callout>
    @else
        <flux:table :paginate="$inscripciones">
            <flux:table.columns>
                <flux:table.column class="w-16">N°</flux:table.column>
                <flux:table.column>DNI</flux:table.column>
                <flux:table.column>Apellidos y nombres</flux:table.column>
                <flux:table.column>Puesto</flux:table.column>
                @can(App\Enums\Permiso::InscripcionesEliminar->value)
                    <flux:table.column align="end">Acciones</flux:table.column>
                @endcan
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($inscripciones as $inscripcion)
                    <flux:table.row :key="$inscripcion->id_ins">
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $inscripcion->numero_ins ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-sm">{{ $inscripcion->documento_ins }}</flux:table.cell>
                        <flux:table.cell class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $inscripcion->apellidos_nombres_ins }}</flux:table.cell>
                        <flux:table.cell class="text-sm whitespace-normal">
                            <span class="font-mono text-xs text-zinc-500">{{ $inscripcion->puesto->codigo_pue }}</span>
                            {{ $inscripcion->puesto->nombre_pue }}
                        </flux:table.cell>
                        @can(App\Enums\Permiso::InscripcionesEliminar->value)
                            <flux:table.cell align="end">
                                <x-tabla.accion
                                    wire:click="eliminar({{ $inscripcion->id_ins }})"
                                    wire:confirm="¿Eliminar la inscripción de {{ $inscripcion->apellidos_nombres_ins }}?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            </flux:table.cell>
                        @endcan
                    </flux:table.row>
                @empty
                    <x-tabla.vacia
                        :columnas="5"
                        :mensaje="$busqueda === '' && $filtroPuesto === ''
                            ? 'Todavía no hay postulantes inscritos en este proceso.'
                            : 'Ninguna inscripción coincide con los filtros.'"
                        icono="clipboard-document-list"
                    >
                        @if ($busqueda === '' && $filtroPuesto === '')
                            <span class="max-w-md text-xs">
                                Importa el listado del Anexo 06-A agregándole una columna «DNI»: sin el documento no se
                                podrá cruzar a cada postulante con su hoja de examen.
                            </span>
                        @endif
                    </x-tabla.vacia>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="importar" class="w-full md:max-w-lg">
        <form wire:submit="importar" class="space-y-6">
            <div>
                <flux:heading size="lg">Importar inscripciones</flux:heading>
                <flux:subheading>Proceso {{ $proceso?->codigo_pro }}</flux:subheading>
            </div>

            <flux:field>
                <x-form.upload-dropzone
                    model="archivo"
                    accept=".xlsx"
                    titulo="Click para elegir el Excel de postulantes"
                    subtitulo="Formato .xlsx · máximo 10 MB"
                >
                    @if ($archivo)
                        <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <flux:icon.document-text class="size-4 shrink-0" />
                            <span class="truncate">{{ $archivo->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </x-form.upload-dropzone>

                <flux:error name="archivo" />
            </flux:field>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs leading-relaxed text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                <p class="font-medium text-zinc-700 dark:text-zinc-300">Columnas que se leen</p>
                <p class="mt-1">
                    «Nº» (opcional), <strong>«DNI»</strong>, «APELLIDOS Y NOMBRES» y «CÓDIGO DE PUESTO». Los puestos
                    deben estar cargados antes. Si hay una sola observación no se guarda nada; volver a subir el
                    archivo actualiza a los postulantes por su DNI.
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="arrow-up-tray">Importar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
