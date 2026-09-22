<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Unidades de organización"
        bajada="Juzgados, salas y módulos de la Corte a los que pertenecen los puestos convocados."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::UnidadesCrear->value)
                <flux:button wire:click="nuevo" variant="primary" icon="plus">Nueva unidad</flux:button>
            @endcan
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

        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="busqueda" icon="magnifying-glass" placeholder="Buscar unidad" clearable />
        </div>

        @if ($unidades->isNotEmpty())
            <flux:text class="ms-auto text-xs">
                {{ $unidades->count() }} unidad(es) · {{ $totalInscritos }} inscrito(s) en {{ $proceso?->codigo_pro }}
            </flux:text>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Unidad de organización</flux:table.column>
            <flux:table.column>Puestos en el proceso</flux:table.column>
            <flux:table.column align="center">Inscritos</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($unidades as $unidad)
                <flux:table.row :key="$unidad->id_uni">
                    <flux:table.cell class="max-w-xs text-sm font-medium whitespace-normal text-zinc-800 dark:text-zinc-200">
                        {{ $unidad->nombre_uni }}
                    </flux:table.cell>

                    <flux:table.cell class="max-w-md whitespace-normal">
                        @if ($unidad->puestos->isEmpty())
                            <span class="text-xs text-zinc-400">Sin puestos en este proceso</span>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($unidad->puestos as $puesto)
                                    <flux:tooltip :content="$puesto->nombre_pue.' · '.$puesto->inscripciones_count.' inscrito(s)'">
                                        <flux:badge size="sm" class="font-mono">{{ $puesto->codigo_pue }}</flux:badge>
                                    </flux:tooltip>
                                @endforeach
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="center" class="tabular-nums">
                        @if ($unidad->inscripciones_count > 0 && auth()->user()->can(App\Enums\Permiso::InscripcionesVer->value))
                            <flux:link
                                :href="route('seleccion.inscripciones', ['proceso' => $proceso?->codigo_pro, 'unidad' => $unidad->id_uni])"
                                wire:navigate
                            >
                                {{ $unidad->inscripciones_count }}
                            </flux:link>
                        @else
                            {{ $unidad->inscripciones_count }}
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$unidad->estado_uni" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::PuestosVer->value)
                                @if ($unidad->puestos_count > 0)
                                    <x-tabla.accion
                                        :href="route('seleccion.puestos', ['proceso' => $proceso?->codigo_pro, 'unidad' => $unidad->id_uni])"
                                        wire:navigate
                                        icon="briefcase"
                                        tooltip="Ver sus puestos"
                                    />
                                @endif
                            @endcan

                            @can(App\Enums\Permiso::UnidadesEditar->value)
                                <x-tabla.accion wire:click="editar({{ $unidad->id_uni }})" icon="pencil-square" tooltip="Editar" />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $unidad->id_uni }})"
                                    :icon="$unidad->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$unidad->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::UnidadesEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $unidad->id_uni }})"
                                    wire:confirm="¿Eliminar la unidad {{ $unidad->nombre_uni }}?"
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
                    :mensaje="$busqueda === ''
                        ? 'Todavía no hay unidades. Se registran solas al importar el listado de postulantes con la columna «UNIDAD DE ORGANIZACIÓN».'
                        : 'Ninguna unidad coincide con la búsqueda.'"
                    icono="building-office-2"
                />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="unidad" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar unidad' : 'Nueva unidad' }}</flux:heading>
                <flux:subheading>Tal como figura en el cuadro de puestos de la convocatoria.</flux:subheading>
            </div>

            <flux:input wire:model="form.nombre" label="Nombre" placeholder="MÓDULO PENAL CENTRAL" />

            <flux:select wire:model="form.estado" label="Estado">
                @foreach ($estados as $estado)
                    <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
