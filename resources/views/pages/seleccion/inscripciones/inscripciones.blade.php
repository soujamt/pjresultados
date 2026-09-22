<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Inscripciones"
        bajada="Postulantes que participan de la evaluación técnica, según el listado del Anexo 06-A."
    >
        <x-slot:acciones>
            @if ($proceso)
                @can(App\Enums\Permiso::InscripcionesExportar->value)
                    {{-- Descarga normal, sin wire:navigate: la respuesta es un archivo. Lleva los filtros activos. --}}
                    <flux:tooltip content="DNI y apellidos y nombres, para la lectora de fichas ópticas">
                        <flux:button
                            :href="route('seleccion.inscripciones.excel', array_filter([
                                'proceso' => $proceso->codigo_pro,
                                'unidad' => $filtroUnidad,
                                'puesto' => $filtroPuesto,
                                'q' => trim($busqueda),
                            ], fn ($valor) => $valor !== ''))"
                            icon="xls-02"
                        >
                            Exportar Excel
                        </flux:button>
                    </flux:tooltip>
                @endcan

                @can(App\Enums\Permiso::InscripcionesImportar->value)
                    <flux:button wire:click="abrirImportacion" variant="primary" icon="upload-04">Importar desde Excel</flux:button>
                @endcan
            @endif
        </x-slot:acciones>
    </x-pagina.encabezado>

    <x-panel>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[14rem_1fr_1fr_16rem]">
            <flux:select wire:model.live="codigoProceso" label="Proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                @endforeach
            </flux:select>

            {{--
                Las listas cambian con el proceso y la unidad: el wire:key evita que
                el navegador conserve una opción que ya no existe.
            --}}
            <div wire:key="unidades-{{ $proceso?->id_pro }}">
                <flux:select wire:model.live="filtroUnidad" label="Unidad de organización">
                    <flux:select.option value="">Todas las unidades</flux:select.option>
                    @foreach ($unidades as $unidad)
                        <flux:select.option :value="$unidad->id_uni">{{ $unidad->nombre_uni }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div wire:key="puestos-{{ $proceso?->id_pro }}-{{ $filtroUnidad }}">
                <flux:select wire:model.live="filtroPuesto" label="Puesto">
                    <flux:select.option value="">Todos los puestos</flux:select.option>
                    @foreach ($puestos as $puesto)
                        <flux:select.option :value="$puesto->id_pue">{{ $puesto->denominacion() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model.live.debounce.300ms="busqueda" icon="search-01" label="Buscar" placeholder="DNI o apellidos" clearable />
        </div>

        @if ($proceso)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-3 dark:border-white/10">
                <p class="text-sm text-zinc-500 tabular-nums">
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($inscripciones->total()) }}</span>
                    inscrito(s) con los filtros actuales
                </p>

                @if ($filtroUnidad !== '' || $filtroPuesto !== '' || $busqueda !== '')
                    <flux:button wire:click="limpiarFiltros" variant="ghost" size="sm" icon="cancel-01">Limpiar filtros</flux:button>
                @endif
            </div>
        @endif
    </x-panel>

    @if ($ultimaImportacion && $ultimaImportacion['errores'] !== [])
        <flux:callout icon="alert-02" variant="danger">
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
        <x-panel>
            <div class="flex items-start gap-3">
                <flux:icon.information-square class="size-5 shrink-0 text-zinc-400" />
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Registra o elige un proceso de selección para ver sus inscripciones.</p>
            </div>
        </x-panel>
    @else
        <x-tabla.marco>
            <flux:table :paginate="$inscripciones">
                <flux:table.columns>
                    <flux:table.column class="w-16" align="end">N°</flux:table.column>
                    <flux:table.column class="w-28">DNI</flux:table.column>
                    <flux:table.column>Apellidos y nombres</flux:table.column>
                    <flux:table.column>Puesto y unidad de organización</flux:table.column>
                    @can(App\Enums\Permiso::InscripcionesEliminar->value)
                        <flux:table.column align="end"><span class="sr-only">Acciones</span></flux:table.column>
                    @endcan
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($inscripciones as $inscripcion)
                        <flux:table.row :key="$inscripcion->id_ins">
                            <flux:table.cell align="end" class="text-zinc-400 tabular-nums">{{ $inscripcion->numero_ins ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="tabular-nums text-sm text-zinc-700 dark:text-zinc-300">{{ $inscripcion->documento_ins }}</flux:table.cell>
                            <flux:table.cell class="text-sm font-medium text-zinc-900 dark:text-white">{{ $inscripcion->apellidos_nombres_ins }}</flux:table.cell>
                            <flux:table.cell class="text-sm whitespace-normal">
                                <div class="text-zinc-800 dark:text-zinc-200">
                                    <span class="me-1 tabular-nums text-xs text-zinc-500">{{ $inscripcion->puesto->codigo_pue }}</span>
                                    {{ $inscripcion->puesto->nombre_pue }}
                                </div>
                                @if ($inscripcion->puesto->unidad)
                                    <div class="mt-0.5 text-xs text-zinc-500">{{ $inscripcion->puesto->unidad->nombre_uni }}</div>
                                @endif
                            </flux:table.cell>
                            @can(App\Enums\Permiso::InscripcionesEliminar->value)
                                <flux:table.cell align="end">
                                    <x-tabla.accion
                                        wire:click="eliminar({{ $inscripcion->id_ins }})"
                                        wire:confirm="¿Eliminar la inscripción de {{ $inscripcion->apellidos_nombres_ins }}?"
                                        icon="delete-02"
                                        tooltip="Eliminar"
                                    />
                                </flux:table.cell>
                            @endcan
                        </flux:table.row>
                    @empty
                        <x-tabla.vacia
                            :columnas="5"
                            :mensaje="$busqueda === '' && $filtroPuesto === '' && $filtroUnidad === ''
                                ? 'Todavía no hay postulantes inscritos en este proceso.'
                                : 'Ninguna inscripción coincide con los filtros.'"
                            icono="user-list"
                        >
                            @if ($busqueda === '' && $filtroPuesto === '' && $filtroUnidad === '')
                                Importa el listado del Anexo 06-A con la columna «DNI»: sin el documento no se podrá cruzar a
                                cada postulante con su hoja de examen.
                            @endif
                        </x-tabla.vacia>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </x-tabla.marco>
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
                    titulo="Elige el Excel de postulantes"
                    subtitulo="Formato .xlsx · máximo 10 MB"
                >
                    @if ($archivo)
                        <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <flux:icon.xls-02 class="size-4 shrink-0" />
                            <span class="truncate">{{ $archivo->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </x-form.upload-dropzone>

                <flux:error name="archivo" />
            </flux:field>

            <div class="space-y-2 rounded-lg bg-zinc-50 px-4 py-3 text-sm leading-relaxed text-pretty text-zinc-600 dark:bg-white/[0.03] dark:text-zinc-400">
                <p>
                    <span class="font-medium text-zinc-800 dark:text-zinc-200">Columnas que se leen:</span>
                    «Nº» (opcional), <strong class="font-semibold text-zinc-800 dark:text-zinc-200">«DNI»</strong>, «APELLIDOS Y
                    NOMBRES» y «CÓDIGO DE PUESTO». Si además trae «PUESTO» y «UNIDAD DE ORGANIZACIÓN», en la misma carga se
                    crean los puestos que falten y se les registra su unidad.
                </p>
                <p>
                    Si hay una sola observación no se guarda nada; volver a subir el archivo actualiza a los postulantes
                    por su DNI.
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="upload-04">Importar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
