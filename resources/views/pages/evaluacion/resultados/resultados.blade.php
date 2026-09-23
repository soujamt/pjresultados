@php
    $puestoElegido = $filtroPuesto !== '' ? ($resultados[0] ?? null) : null;
    $puedeDescargar = $proceso && $proceso->publicacionCompleta() && $resultados !== [];
    $parametros = $proceso ? array_filter([
        'proceso' => $proceso->codigo_pro,
        'unidad' => $filtroUnidad,
        'puesto' => $filtroPuesto,
    ], fn ($valor) => $valor !== '') : [];
@endphp

<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Resultados"
        bajada="Resultados de la evaluación técnica por puesto, en orden de mérito y con el formato del Anexo 07."
    >
        <x-slot:acciones>
            @if ($proceso)
                @can(App\Enums\Permiso::ResultadosGenerar->value)
                    <flux:button wire:click="abrirPublicacion" icon="file-edit">Datos de la publicación</flux:button>
                @endcan

                @can(App\Enums\Permiso::ResultadosExportar->value)
                    @if ($puedeDescargar)
                        {{-- Descargas normales, sin wire:navigate: la respuesta es un archivo. --}}
                        <flux:button :href="route('evaluacion.resultados.descargar', $parametros + ['formato' => 'excel'])" icon="xls-02">Excel</flux:button>
                        <flux:button :href="route('evaluacion.resultados.descargar', $parametros + ['formato' => 'pdf'])" variant="primary" icon="pdf-02">
                            {{ $puestoElegido ? 'PDF del puesto' : 'PDF de '.count($resultados).' puesto(s)' }}
                        </flux:button>
                    @else
                        <flux:tooltip :content="$resultados === [] ? 'No hay postulantes con estos filtros' : 'Completa antes los datos de la publicación'">
                            <div class="flex gap-2">
                                <flux:button icon="xls-02" disabled>Excel</flux:button>
                                <flux:button variant="primary" icon="pdf-02" disabled>PDF</flux:button>
                            </div>
                        </flux:tooltip>
                    @endif
                @endcan
            @endif
        </x-slot:acciones>
    </x-pagina.encabezado>

    <x-panel>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[14rem_1fr_1fr]">
            <flux:select wire:model.live="codigoProceso" label="Proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                @endforeach
            </flux:select>

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
        </div>
    </x-panel>

    @if (! $proceso)
        <x-panel>
            <div class="flex items-start gap-3">
                <flux:icon.information-square class="size-5 shrink-0 text-zinc-400" />
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Registra o elige un proceso de selección para ver sus resultados.</p>
            </div>
        </x-panel>
    @else
        @unless ($proceso->publicacionCompleta())
            <flux:callout icon="alert-02" variant="warning">
                <flux:callout.heading>Faltan los datos del pie del Anexo 07</flux:callout.heading>
                <flux:callout.text>
                    La fecha límite y el correo para enviar documentos, la fecha de publicación y el comité. Sin ellos no se
                    puede descargar el PDF ni el Excel.
                </flux:callout.text>
                @can(App\Enums\Permiso::ResultadosGenerar->value)
                    <x-slot name="actions">
                        <flux:button wire:click="abrirPublicacion" size="sm">Completar datos</flux:button>
                    </x-slot>
                @endcan
            </flux:callout>
        @endunless

        @unless ($hayExamenes)
            <flux:callout icon="information-square">
                <flux:callout.text>
                    Aún no hay exámenes cargados: mientras tanto todos los inscritos figuran como «no se presentó».
                </flux:callout.text>
            </flux:callout>
        @endunless

        <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
            @foreach ([
                ['Inscritos', $totales['inscritos'], 'user-list', null],
                ['Aptos', $totales['aptos'], 'user-check-01', 'text-green-700 dark:text-green-400'],
                ['No alcanzaron el mínimo', $totales['desaprobados'], 'ranking', null],
                ['No se presentaron', $totales['ausentes'], 'user-remove-01', null],
                ['Descalificados', $totales['descalificados'], 'user-block-01', null],
            ] as [$etiqueta, $valor, $icono, $color])
                <x-panel class="flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $etiqueta }}</span>
                        <flux:icon :icon="$icono" class="size-5 text-zinc-400" />
                    </div>
                    <div @class([
                        'mt-2.5 text-2xl leading-none font-semibold tracking-tight tabular-nums',
                        $color ?? 'text-zinc-900 dark:text-white',
                    ])>{{ number_format($valor) }}</div>
                </x-panel>
            @endforeach
        </div>

        @if ($puestoElegido)
            {{-- Un puesto: el orden de mérito tal como se publicará. --}}
            <x-panel>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <dl class="grid gap-1 text-sm">
                        <div><dt class="inline font-semibold text-zinc-900 dark:text-white">Puesto:</dt> <dd class="inline text-zinc-700 dark:text-zinc-300">{{ $puestoElegido->puesto->nombre_pue }}</dd></div>
                        <div><dt class="inline font-semibold text-zinc-900 dark:text-white">Código del puesto:</dt> <dd class="inline tabular-nums text-zinc-700 dark:text-zinc-300">{{ $puestoElegido->puesto->codigo_pue }}</dd></div>
                        <div><dt class="inline font-semibold text-zinc-900 dark:text-white">Unidad de organización:</dt> <dd class="inline text-zinc-700 dark:text-zinc-300">{{ $puestoElegido->puesto->unidad?->nombre_uni ?? '—' }}</dd></div>
                    </dl>

                    <flux:button wire:click="verTodos" variant="ghost" size="sm" icon="arrow-left-01">Todos los puestos</flux:button>
                </div>
            </x-panel>

            <x-tabla.marco>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-12" align="end">N.º</flux:table.column>
                        <flux:table.column class="w-24">DNI</flux:table.column>
                        <flux:table.column>Apellidos y nombres</flux:table.column>
                        <flux:table.column align="end">Nota</flux:table.column>
                        <flux:table.column align="end">Nota parcial</flux:table.column>
                        <flux:table.column align="end">Puntaje</flux:table.column>
                        <flux:table.column>Condición</flux:table.column>
                        <flux:table.column>Observaciones</flux:table.column>
                        @can(App\Enums\Permiso::ResultadosGenerar->value)
                            <flux:table.column align="end"><span class="sr-only">Acciones</span></flux:table.column>
                        @endcan
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($puestoElegido->filas as $fila)
                            <flux:table.row :key="$fila->inscripcion->id_ins">
                                <flux:table.cell align="end" class="text-zinc-400 tabular-nums">{{ $fila->numero }}</flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $fila->inscripcion->documento_ins }}</flux:table.cell>
                                <flux:table.cell class="text-sm font-medium whitespace-normal text-zinc-900 dark:text-white">{{ $fila->inscripcion->apellidos_nombres_ins }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $fila->nota }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $fila->notaParcialTexto() }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm font-semibold text-zinc-900 tabular-nums dark:text-white">{{ $fila->puntajeTexto() }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$fila->condicion->color()" inset="top bottom">{{ $fila->condicion->etiqueta() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="min-w-64 text-xs whitespace-normal text-zinc-500">{{ $fila->observacion }}</flux:table.cell>
                                @can(App\Enums\Permiso::ResultadosGenerar->value)
                                    <flux:table.cell align="end">
                                        @if ($fila->descalificado)
                                            <x-tabla.accion
                                                wire:click="quitarDescalificacion({{ $fila->inscripcion->id_ins }})"
                                                wire:confirm="¿Quitar la descalificación de {{ $fila->inscripcion->apellidos_nombres_ins }}? Volverá a calificarse con su hoja de examen."
                                                icon="undo-02"
                                                tooltip="Quitar descalificación"
                                            />
                                        @else
                                            <x-tabla.accion
                                                wire:click="abrirDescalificacion({{ $fila->inscripcion->id_ins }})"
                                                icon="user-block-01"
                                                tooltip="Descalificar"
                                            />
                                        @endif
                                    </flux:table.cell>
                                @endcan
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-tabla.marco>
        @else
            {{-- Todos los puestos: un resumen por puesto; cada uno se abre con su orden de mérito. --}}
            <x-tabla.marco>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-24">Código</flux:table.column>
                        <flux:table.column>Puesto y unidad de organización</flux:table.column>
                        <flux:table.column align="end">Inscritos</flux:table.column>
                        <flux:table.column align="end">Aptos</flux:table.column>
                        <flux:table.column align="end">No aptos</flux:table.column>
                        <flux:table.column align="end">No se presentaron</flux:table.column>
                        <flux:table.column align="end"><span class="sr-only">Acciones</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($resultados as $delPuesto)
                            <flux:table.row :key="$delPuesto->puesto->id_pue">
                                <flux:table.cell class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $delPuesto->puesto->codigo_pue }}</flux:table.cell>
                                <flux:table.cell class="text-sm whitespace-normal">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $delPuesto->puesto->nombre_pue }}</div>
                                    @if ($delPuesto->puesto->unidad)
                                        <div class="mt-0.5 text-xs text-zinc-500">{{ $delPuesto->puesto->unidad->nombre_uni }}</div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $delPuesto->inscritos() }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm font-semibold text-green-700 tabular-nums dark:text-green-400">{{ $delPuesto->aptos() }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $delPuesto->inscritos() - $delPuesto->aptos() }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500 tabular-nums">{{ $delPuesto->ausentes() }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <x-tabla.accion wire:click="verPuesto({{ $delPuesto->puesto->id_pue }})" icon="view" tooltip="Ver orden de mérito" />
                                        @can(App\Enums\Permiso::ResultadosExportar->value)
                                            @if ($proceso->publicacionCompleta())
                                                <flux:tooltip content="PDF del puesto">
                                                    <flux:button
                                                        :href="route('evaluacion.resultados.descargar', ['proceso' => $proceso->codigo_pro, 'formato' => 'pdf', 'puesto' => $delPuesto->puesto->id_pue])"
                                                        size="sm"
                                                        variant="subtle"
                                                        icon="pdf-02"
                                                        aria-label="PDF del puesto {{ $delPuesto->puesto->codigo_pue }}"
                                                    />
                                                </flux:tooltip>
                                            @endif
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-tabla.vacia :columnas="7" mensaje="No hay postulantes inscritos con estos filtros." icono="ranking">
                                Los resultados se calculan con las inscripciones y los exámenes cargados del proceso.
                            </x-tabla.vacia>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </x-tabla.marco>
        @endif
    @endif

    <flux:modal name="publicacion" class="w-full md:max-w-xl">
        <form wire:submit="guardarPublicacion" class="space-y-6">
            <div>
                <flux:heading size="lg">Datos de la publicación</flux:heading>
                <flux:subheading>Van al pie del Anexo 07 de todos los puestos del proceso {{ $proceso?->codigo_pro }}.</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="publicacion.puntajeMinimo"
                    label="Puntaje mínimo para ser apto"
                    type="number"
                    step="0.01"
                    min="0"
                    max="6"
                    description:trailing="Con 3,9 hacen falta 20 aciertos."
                />

                <flux:select wire:model="publicacion.comite" label="Comité que publica">
                    <flux:select.option value="">Elige el comité</flux:select.option>
                    @foreach (App\Enums\ComiteSeleccion::cases() as $comite)
                        <flux:select.option :value="$comite->value">{{ $comite->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="publicacion.fechaLimite" label="Fecha límite para enviar documentos" type="date" description:trailing="Hasta las 23:59 horas." />

                <flux:input wire:model="publicacion.correo" label="Correo para los documentos" type="email" placeholder="convocatorias728_ucayali@pj.gob.pe" />

                <flux:input wire:model="publicacion.fechaResultados" label="Fecha de publicación" type="date" />

                <flux:input wire:model="publicacion.ciudad" label="Ciudad" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="descalificar" class="w-full md:max-w-xl">
        <form wire:submit="descalificar" class="space-y-6">
            <div>
                <flux:heading size="lg">Descalificar postulante</flux:heading>
                @if ($aDescalificar)
                    <flux:subheading>
                        {{ $aDescalificar->apellidos_nombres_ins }} · DNI {{ $aDescalificar->documento_ins }} · {{ $aDescalificar->puesto->codigo_pue }}
                    </flux:subheading>
                @endif
            </div>

            <flux:radio.group wire:model.live="motivo" label="Motivo que se publicará en observaciones">
                @foreach ($motivos as $opcion)
                    <flux:radio :value="$opcion" :label="$opcion" />
                @endforeach
                <flux:radio :value="$opcionOtroMotivo" label="Otro motivo" />
            </flux:radio.group>

            @if ($motivo === $opcionOtroMotivo)
                <flux:textarea wire:model="otroMotivo" label="Motivo" rows="2" placeholder="DESCALIFICADO/A - …" />
            @endif

            <p class="rounded-lg bg-zinc-50 px-4 py-3 text-sm text-pretty text-zinc-600 dark:bg-white/[0.03] dark:text-zinc-400">
                Quedará <strong class="font-semibold text-zinc-800 dark:text-zinc-200">NO APTO</strong> con nota y puntaje 0, aunque tenga
                hoja de examen. La descalificación se puede quitar después.
            </p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger" icon="user-block-01">Descalificar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
