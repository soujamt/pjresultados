<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Puestos"
        bajada="Puestos convocados en el proceso, agrupados por su unidad de organización."
    >
        <x-slot:acciones>
            @if ($proceso)
                @can(App\Enums\Permiso::PuestosImportar->value)
                    <flux:button wire:click="abrirImportacion" icon="upload-04">Importar desde Excel</flux:button>
                @endcan

                @can(App\Enums\Permiso::PuestosCrear->value)
                    <flux:button wire:click="nuevo" variant="primary" icon="plus-sign">Nuevo puesto</flux:button>
                @endcan
            @endif
        </x-slot:acciones>
    </x-pagina.encabezado>

    <x-panel>
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-60">
                <flux:select wire:model.live="codigoProceso" label="Proceso">
                    @foreach ($procesos as $opcion)
                        <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-full sm:w-96">
                <flux:select wire:model.live="filtroUnidad" label="Unidad de organización">
                    <flux:select.option value="">Todas las unidades</flux:select.option>
                    @foreach ($unidades as $unidad)
                        <flux:select.option :value="$unidad->id_uni">{{ $unidad->nombre_uni }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="busqueda" icon="search-01" placeholder="Buscar por código o puesto" clearable />
            </div>

            @if ($filtroUnidad !== '' || $busqueda !== '')
                <flux:button wire:click="limpiarFiltros" variant="ghost" icon="cancel-01">Limpiar filtros</flux:button>
            @endif
        </div>

        @if ($ultimaCarga)
            <p class="mt-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-white/10">
                Última carga: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ultimaCarga->archivo_imp }}</span>
                · {{ $ultimaCarga->created_at->format('d/m/Y H:i') }}
                @if ($ultimaCarga->usuario)
                    · {{ $ultimaCarga->usuario->nombre_usu }}
                @endif
            </p>
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
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Registra o elige un proceso de selección para ver sus puestos.</p>
            </div>
        </x-panel>
    @else
        <x-tabla.marco>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-32">Código</flux:table.column>
                    <flux:table.column>Puesto</flux:table.column>
                    <flux:table.column align="end">Inscritos</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column align="end"><span class="sr-only">Acciones</span></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($porUnidad as $nombreUnidad => $grupo)
                        {{-- Cabecera del grupo: la unidad y cuántos puestos e inscritos reúne. --}}
                        <flux:table.row :key="'unidad-'.$loop->index" data-grupo>
                            <flux:table.cell colspan="5" class="bg-zinc-50/70 py-2! dark:bg-white/[0.02]">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <flux:icon.building-03 class="size-4 shrink-0 text-pj-700 dark:text-pj-400" />
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $nombreUnidad !== '' ? $nombreUnidad : 'Sin unidad de organización' }}
                                    </span>
                                    <span class="text-xs text-zinc-500 tabular-nums">
                                        {{ $grupo->count() }} puesto(s) · {{ $grupo->sum('inscripciones_count') }} inscrito(s)
                                    </span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>

                        @foreach ($grupo as $puesto)
                            <flux:table.row :key="$puesto->id_pue">
                                <flux:table.cell class="ps-10!">
                                    <span class="tabular-nums text-sm text-zinc-700 dark:text-zinc-300">{{ $puesto->codigo_pue }}</span>
                                </flux:table.cell>

                                <flux:table.cell class="text-sm whitespace-normal text-zinc-900 dark:text-white">
                                    {{ $puesto->nombre_pue }}
                                </flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">
                                    @can(App\Enums\Permiso::InscripcionesVer->value)
                                        <flux:link
                                            :href="route('seleccion.inscripciones', ['proceso' => $proceso->codigo_pro, 'puesto' => $puesto->id_pue])"
                                            wire:navigate
                                        >
                                            {{ $puesto->inscripciones_count }}
                                        </flux:link>
                                    @else
                                        {{ $puesto->inscripciones_count }}
                                    @endcan
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-estado.badge :estado="$puesto->estado_pue" />
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-0.5">
                                        @can(App\Enums\Permiso::PuestosEditar->value)
                                            <x-tabla.accion wire:click="editar({{ $puesto->id_pue }})" icon="pencil-edit-02" tooltip="Editar" />

                                            <x-tabla.accion
                                                wire:click="alternarEstado({{ $puesto->id_pue }})"
                                                :icon="$puesto->estaHabilitado() ? 'view-off-slash' : 'view'"
                                                :tooltip="$puesto->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                            />
                                        @endcan

                                        @can(App\Enums\Permiso::PuestosEliminar->value)
                                            <x-tabla.accion
                                                wire:click="eliminar({{ $puesto->id_pue }})"
                                                wire:confirm="¿Eliminar el puesto {{ $puesto->codigo_pue }}?"
                                                icon="delete-02"
                                                tooltip="Eliminar"
                                            />
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    @empty
                        <x-tabla.vacia
                            :columnas="5"
                            :mensaje="$busqueda === '' && $filtroUnidad === ''
                                ? 'El proceso todavía no tiene puestos.'
                                : 'Ningún puesto coincide con los filtros.'"
                            icono="briefcase-01"
                        >
                            @if ($busqueda === '' && $filtroUnidad === '')
                                Impórtalos desde el Anexo 06-A con el botón «Importar desde Excel».
                            @endif
                        </x-tabla.vacia>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </x-tabla.marco>

        @if ($puestos->isNotEmpty())
            <p class="text-sm text-zinc-500 tabular-nums">
                {{ $puestos->count() }} puesto(s) en {{ $porUnidad->count() }} unidad(es) · {{ $puestos->sum('inscripciones_count') }} inscrito(s)
            </p>
        @endif
    @endif

    <flux:modal name="puesto" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar puesto' : 'Nuevo puesto' }}</flux:heading>
                <flux:subheading>Proceso {{ $proceso?->codigo_pro }}</flux:subheading>
            </div>

            <flux:input
                wire:model="form.codigo"
                label="Código"
                placeholder="00340-1"
                description="Con sufijo cuando el mismo cargo se convoca para varias dependencias."
            />

            <flux:input wire:model="form.nombre" label="Puesto" placeholder="ASISTENTE JUDICIAL" />

            <flux:select wire:model="form.unidad" label="Unidad de organización">
                <flux:select.option value="">Sin unidad</flux:select.option>
                @foreach ($unidades as $unidad)
                    <flux:select.option :value="$unidad->id_uni">{{ $unidad->nombre_uni }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="form.estado" label="Estado">
                @foreach ($estados as $estado)
                    <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:error name="form.proceso" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="importar" class="w-full md:max-w-lg">
        <form wire:submit="importar" class="space-y-6">
            <div>
                <flux:heading size="lg">Importar puestos</flux:heading>
                <flux:subheading>Proceso {{ $proceso?->codigo_pro }}</flux:subheading>
            </div>

            <flux:field>
                <x-form.upload-dropzone
                    model="archivo"
                    accept=".xlsx"
                    titulo="Elige el Excel del Anexo 06-A"
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

            <p class="text-sm leading-relaxed text-pretty text-zinc-500">
                Se lee el listado de postulantes y se agrupan las columnas «CÓDIGO DE PUESTO», «PUESTO» y, si viene,
                «UNIDAD DE ORGANIZACIÓN». Volver a subir el archivo no duplica nada: actualiza el nombre y la unidad de
                los puestos que ya existen.
            </p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="upload-04">Importar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
