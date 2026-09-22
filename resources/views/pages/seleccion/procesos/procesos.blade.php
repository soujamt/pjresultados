<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Procesos de selección"
        bajada="Cada convocatoria de la Corte es un proceso con sus propios puestos, postulantes y resultados."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::ProcesosCrear->value)
                <flux:button wire:click="nuevo" variant="primary" icon="plus">Nuevo proceso</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Proceso</flux:table.column>
            <flux:table.column>Evaluación técnica</flux:table.column>
            <flux:table.column align="center">Puestos</flux:table.column>
            <flux:table.column align="center">Inscritos</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($procesos as $proceso)
                <flux:table.row :key="$proceso->id_pro">
                    <flux:table.cell class="max-w-md">
                        <flux:badge color="red" size="sm">{{ $proceso->codigo_pro }}</flux:badge>
                        <div class="mt-1 text-sm whitespace-normal text-zinc-700 dark:text-zinc-300">{{ $proceso->nombre_pro }}</div>
                        <div class="text-xs whitespace-normal text-zinc-500">
                            {{ $proceso->entidad_pro }}
                            @if ($proceso->regimen_pro)
                                · {{ $proceso->regimen_pro }}
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm">
                        <div>{{ $proceso->fecha_evaluacion_pro?->format('d/m/Y') ?? '—' }}</div>
                        <div class="text-xs text-zinc-500">{{ $proceso->hora_evaluacion_pro }}</div>
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        @can(App\Enums\Permiso::PuestosVer->value)
                            <flux:link :href="route('seleccion.puestos', ['proceso' => $proceso->codigo_pro])" wire:navigate>
                                {{ $proceso->puestos_count }}
                            </flux:link>
                        @else
                            {{ $proceso->puestos_count }}
                        @endcan
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        @can(App\Enums\Permiso::InscripcionesVer->value)
                            <flux:link :href="route('seleccion.inscripciones', ['proceso' => $proceso->codigo_pro])" wire:navigate>
                                {{ $proceso->inscripciones_count }}
                            </flux:link>
                        @else
                            {{ $proceso->inscripciones_count }}
                        @endcan
                    </flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$proceso->estado_pro" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::ProcesosEditar->value)
                                <x-tabla.accion wire:click="editar({{ $proceso->id_pro }})" icon="pencil-square" tooltip="Editar" />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $proceso->id_pro }})"
                                    :icon="$proceso->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$proceso->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::ProcesosEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $proceso->id_pro }})"
                                    wire:confirm="¿Eliminar el proceso {{ $proceso->codigo_pro }}?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="6" mensaje="Todavía no hay procesos registrados." icono="calendar-days" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="proceso" class="w-full md:max-w-2xl">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar proceso' : 'Nuevo proceso' }}</flux:heading>
                <flux:subheading>Los datos de cabecera del Anexo 06-A de la convocatoria.</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.codigo" label="Código" placeholder="002-2026-UE-UCAYALI" class="sm:col-span-1" />

                <div class="sm:col-span-2">
                    <flux:input wire:model="form.entidad" label="Entidad" />
                </div>
            </div>

            <flux:input
                wire:model="form.nombre"
                label="Nombre"
                placeholder="Proceso de Selección de Personal Indeterminado N° 002-2026-UE-UCAYALI"
            />

            <flux:input
                wire:model="form.regimen"
                label="Régimen laboral"
                placeholder="Decreto Legislativo N° 728, a plazo indeterminado"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.fechaEvaluacion" type="date" label="Fecha de la evaluación técnica" />
                <flux:input wire:model="form.horaEvaluacion" label="Hora" placeholder="10:00 am. a 11:00 am." />
            </div>

            <flux:input wire:model="form.lugarEvaluacion" label="Lugar de la evaluación" />

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
