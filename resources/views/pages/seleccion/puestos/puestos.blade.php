<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Puestos"
        bajada="Puestos convocados en el proceso, con el código del cuadro de puestos del Poder Judicial."
    >
        <x-slot:acciones>
            @if ($proceso)
                @can(App\Enums\Permiso::PuestosImportar->value)
                    <flux:button wire:click="abrirImportacion" icon="arrow-up-tray">Importar desde Excel</flux:button>
                @endcan

                @can(App\Enums\Permiso::PuestosCrear->value)
                    <flux:button wire:click="nuevo" variant="primary" icon="plus">Nuevo puesto</flux:button>
                @endcan
            @endif
        </x-slot:acciones>
    </x-pagina.encabezado>

    <div class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-72">
            <flux:select wire:model.live="codigoProceso" label="Proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="busqueda" icon="magnifying-glass" placeholder="Buscar por código o puesto" clearable />
        </div>

        @if ($ultimaCarga)
            <flux:text class="ms-auto text-xs">
                Última carga: {{ $ultimaCarga->archivo_imp }} · {{ $ultimaCarga->created_at->format('d/m/Y H:i') }}
                @if ($ultimaCarga->usuario)
                    · {{ $ultimaCarga->usuario->nombre_usu }}
                @endif
            </flux:text>
        @endif
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
            <flux:callout.text>Registra o elige un proceso de selección para ver sus puestos.</flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Código</flux:table.column>
                <flux:table.column>Puesto</flux:table.column>
                <flux:table.column align="center">Inscritos</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($puestos as $puesto)
                    <flux:table.row :key="$puesto->id_pue">
                        <flux:table.cell>
                            <flux:badge size="sm" class="font-mono">{{ $puesto->codigo_pue }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-sm font-medium whitespace-normal text-zinc-800 dark:text-zinc-200">
                            {{ $puesto->nombre_pue }}
                        </flux:table.cell>

                        <flux:table.cell align="center" class="tabular-nums">
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
                            <div class="flex justify-end gap-1">
                                @can(App\Enums\Permiso::PuestosEditar->value)
                                    <x-tabla.accion wire:click="editar({{ $puesto->id_pue }})" icon="pencil-square" tooltip="Editar" />

                                    <x-tabla.accion
                                        wire:click="alternarEstado({{ $puesto->id_pue }})"
                                        :icon="$puesto->estaHabilitado() ? 'eye-slash' : 'eye'"
                                        :tooltip="$puesto->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                    />
                                @endcan

                                @can(App\Enums\Permiso::PuestosEliminar->value)
                                    <x-tabla.accion
                                        wire:click="eliminar({{ $puesto->id_pue }})"
                                        wire:confirm="¿Eliminar el puesto {{ $puesto->codigo_pue }}?"
                                        icon="trash"
                                        tooltip="Eliminar"
                                    />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <x-tabla.vacia
                        :columnas="5"
                        :mensaje="$busqueda === '' ? 'El proceso todavía no tiene puestos. Impórtalos desde el Anexo 06-A.' : 'Ningún puesto coincide con la búsqueda.'"
                        icono="briefcase"
                    />
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($puestos->isNotEmpty())
            <flux:text class="text-xs">
                {{ $puestos->count() }} puesto(s) · {{ $puestos->sum('inscripciones_count') }} inscrito(s)
            </flux:text>
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
                    titulo="Click para elegir el Excel del Anexo 06-A"
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

            <flux:text class="text-xs leading-relaxed">
                Se lee la hoja del listado de postulantes y se agrupan las columnas «CÓDIGO DE PUESTO» y «PUESTO».
                Volver a subir el archivo no duplica nada: actualiza el nombre de los puestos que ya existen.
            </flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="arrow-up-tray">Importar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
