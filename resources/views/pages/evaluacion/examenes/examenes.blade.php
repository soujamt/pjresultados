<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Exámenes"
        bajada="Hojas de respuestas calificadas por la lectora óptica y cruzadas por DNI con los postulantes inscritos."
    >
        <x-slot:acciones>
            @if ($proceso)
                @can(App\Enums\Permiso::ExamenesEliminar->value)
                    @if ($examenesDelProceso > 0)
                        <flux:button
                            wire:click="vaciar"
                            wire:confirm="¿Borrar los {{ number_format($examenesDelProceso) }} exámenes cargados del proceso {{ $proceso->codigo_pro }}, de todas sus unidades y puestos? Los inscritos quedarán sin examen hasta que vuelvas a importar."
                            variant="ghost"
                            icon="delete-02"
                        >
                            Vaciar exámenes
                        </flux:button>
                    @endif
                @endcan

                @can(App\Enums\Permiso::ExamenesImportar->value)
                    <flux:button wire:click="abrirImportacion" variant="primary" icon="upload-04">Importar desde la lectora</flux:button>
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
                    postulante(s) con los filtros actuales
                </p>

                @if ($filtroUnidad !== '' || $filtroPuesto !== '' || $filtroEstado !== '' || $busqueda !== '')
                    <flux:button wire:click="limpiarFiltros" variant="ghost" size="sm" icon="cancel-01">Limpiar filtros</flux:button>
                @endif
            </div>
        @endif
    </x-panel>

    @if ($proceso)
        {{--
            Verificación de la carga. Cada cifra es también un filtro del listado:
            un clic muestra solo esos postulantes y otro clic lo quita.
        --}}
        @php
            $avance = $resumen['inscritos'] === 0 ? 0 : round($resumen['con_examen'] * 100 / $resumen['inscritos'], 1);
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['', 'Inscritos', $resumen['inscritos'], 'user-list', 'Todos los postulantes'],
                [App\Services\Evaluacion\ExamenService::CON_EXAMEN, 'Con examen', $resumen['con_examen'], 'user-check-01', str_replace('.', ',', (string) $avance).' % de los inscritos'],
                [App\Services\Evaluacion\ExamenService::SIN_EXAMEN, 'Sin examen', $resumen['sin_examen'], 'user-remove-01', 'Sin hoja en la lectora'],
                [App\Services\Evaluacion\ExamenService::NOMBRE_DISTINTO, 'Nombre distinto', $resumen['nombre_distinto'], 'user-warning-01', 'La hoja trae otro nombre'],
            ] as [$estado, $etiqueta, $valor, $icono, $detalle])
                @php
                    $activo = $filtroEstado === $estado;
                    $alerta = $estado === App\Services\Evaluacion\ExamenService::NOMBRE_DISTINTO && $valor > 0;
                @endphp

                <button
                    type="button"
                    wire:click="filtrarPorEstado('{{ $estado }}')"
                    aria-pressed="{{ $activo ? 'true' : 'false' }}"
                    @class([
                        'group flex flex-col rounded-lg bg-white p-4 text-start sombra-borde transition-[box-shadow,outline-color] duration-150 hover:sombra-borde-hover dark:bg-zinc-900',
                        'outline-2 -outline-offset-2 outline-pj-700 dark:outline-pj-400' => $activo,
                    ])
                >
                    <span class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $etiqueta }}</span>
                        <flux:icon :icon="$icono" :class="$alerta ? 'size-5 text-amber-500' : 'size-5 text-zinc-400'" />
                    </span>

                    <span
                        @class([
                            'mt-2.5 text-2xl leading-none font-semibold tracking-tight tabular-nums',
                            'text-amber-600 dark:text-amber-400' => $alerta,
                            'text-zinc-900 dark:text-white' => ! $alerta,
                        ])
                    >
                        {{ number_format($valor) }}
                    </span>

                    @if ($estado === App\Services\Evaluacion\ExamenService::CON_EXAMEN)
                        <span class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10" aria-hidden="true">
                            <span class="block h-full rounded-full bg-pj-700 dark:bg-pj-400" style="width: {{ min(100, $avance) }}%"></span>
                        </span>
                    @endif

                    <span class="mt-2 text-xs text-zinc-500 tabular-nums">{{ $detalle }}</span>
                </button>
            @endforeach
        </div>
    @endif

    @if ($ultimaImportacion)
        @if ($ultimaImportacion['errores'] !== [])
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
        @elseif ($ultimaImportacion['aplicada'])
            <flux:callout icon="checkmark-circle-02" variant="success">
                <flux:callout.heading>Se cargó {{ $ultimaImportacion['archivo'] }}</flux:callout.heading>
                <flux:callout.text>{{ $ultimaImportacion['mensaje'] }}</flux:callout.text>
            </flux:callout>
        @endif
    @endif

    @if (! $proceso)
        <x-panel>
            <div class="flex items-start gap-3">
                <flux:icon.information-square class="size-5 shrink-0 text-zinc-400" />
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Registra o elige un proceso de selección para cargar sus exámenes.</p>
            </div>
        </x-panel>
    @else
        <x-tabla.marco>
            <flux:table :paginate="$inscripciones">
                <flux:table.columns>
                    <flux:table.column class="w-28">DNI</flux:table.column>
                    <flux:table.column>Apellidos y nombres</flux:table.column>
                    <flux:table.column>Puesto</flux:table.column>
                    <flux:table.column align="end">Puntaje</flux:table.column>
                    <flux:table.column align="end">Aciertos</flux:table.column>
                    <flux:table.column align="end">Errores</flux:table.column>
                    <flux:table.column align="end">Blancos</flux:table.column>
                    <flux:table.column align="end">Dobles</flux:table.column>
                    <flux:table.column align="end"><span class="sr-only">Acciones</span></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($inscripciones as $inscripcion)
                        @php
                            $examen = $inscripcion->examen;
                        @endphp

                        <flux:table.row :key="$inscripcion->id_ins">
                            <flux:table.cell class="tabular-nums text-sm text-zinc-700 dark:text-zinc-300">{{ $inscripcion->documento_ins }}</flux:table.cell>
                            <flux:table.cell class="text-sm whitespace-normal">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $inscripcion->apellidos_nombres_ins }}</div>
                                @if ($examen && ! $examen->nombre_coincide_exa)
                                    <div class="mt-0.5 flex items-center gap-1 text-xs text-amber-700 dark:text-amber-400">
                                        <flux:icon.alert-02 class="size-3.5 shrink-0" />
                                        <span>En la hoja: {{ $examen->apellidos_nombres_exa }}</span>
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-sm whitespace-normal">
                                <div class="text-zinc-800 dark:text-zinc-200">
                                    <span class="me-1 tabular-nums text-xs text-zinc-500">{{ $inscripcion->puesto->codigo_pue }}</span>
                                    {{ $inscripcion->puesto->nombre_pue }}
                                </div>
                                @if ($inscripcion->puesto->unidad)
                                    <div class="mt-0.5 text-xs text-zinc-500">{{ $inscripcion->puesto->unidad->nombre_uni }}</div>
                                @endif
                            </flux:table.cell>

                            @if ($examen)
                                <flux:table.cell align="end" class="text-sm font-semibold text-zinc-900 tabular-nums dark:text-white">{{ $examen->puntaje() }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-600 tabular-nums dark:text-zinc-300">{{ $examen->aciertos_exa }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500 tabular-nums">{{ $examen->errores_exa }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500 tabular-nums">{{ $examen->blancos_exa }}</flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500 tabular-nums">{{ $examen->dobles_exa }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <x-tabla.accion wire:click="verHoja({{ $examen->id_exa }})" icon="view" tooltip="Ver hoja de respuestas" />
                                </flux:table.cell>
                            @else
                                <flux:table.cell colspan="5" align="end">
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Sin examen</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell />
                            @endif
                        </flux:table.row>
                    @empty
                        <x-tabla.vacia
                            :columnas="9"
                            :mensaje="$filtroUnidad === '' && $filtroPuesto === '' && $filtroEstado === '' && $busqueda === ''
                                ? 'Todavía no hay postulantes inscritos en este proceso.'
                                : 'Ningún postulante coincide con los filtros.'"
                            icono="user-list"
                        >
                            @if ($filtroUnidad === '' && $filtroPuesto === '' && $filtroEstado === '' && $busqueda === '')
                                Las hojas de la lectora se cruzan por DNI: importa primero las inscripciones del proceso.
                            @endif
                        </x-tabla.vacia>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </x-tabla.marco>

        <x-panel titulo="Archivos cargados" descripcion="Últimas cargas de la lectora en este proceso, incluidas las que se rechazaron.">
            @if ($cargas->isEmpty())
                <p class="text-sm text-zinc-500">Aún no se ha subido ningún archivo de la lectora.</p>
            @else
                <ul class="-my-3 divide-y divide-zinc-100 dark:divide-white/10">
                    @foreach ($cargas as $carga)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1.5 py-3">
                            <flux:icon.txt-01 class="size-5 shrink-0 text-zinc-400" />

                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $carga->archivo_imp }}</div>
                                <div class="mt-0.5 text-xs text-zinc-500 tabular-nums">
                                    {{ $carga->created_at->format('d/m/Y H:i') }}
                                    · {{ $carga->usuario?->nombre_usu ?? 'Consola' }}
                                    · {{ number_format($carga->filas_imp) }} hoja(s)
                                </div>
                            </div>

                            @if ($carga->aplicada_imp)
                                <flux:badge size="sm" color="green" inset="top bottom">
                                    {{ $carga->creados_imp }} nueva(s) · {{ $carga->actualizados_imp }} actualizada(s)
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="red" inset="top bottom">
                                    Rechazado · {{ count($carga->errores_imp ?? []) }} observación(es)
                                </flux:badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    @endif

    {{--
        Importación en dos pasos: al elegir el archivo se revisa completo sin
        guardar nada y se muestra la vista previa; solo al confirmar se escribe.
    --}}
    <flux:modal name="importar" class="w-full md:max-w-3xl">
        <form wire:submit="importar" class="space-y-6">
            <div>
                <flux:heading size="lg">Importar exámenes</flux:heading>
                <flux:subheading>Proceso {{ $proceso?->codigo_pro }}</flux:subheading>
            </div>

            @if (! $vistaPrevia)
                <flux:field>
                    <x-form.upload-dropzone
                        model="archivo"
                        accept=".txt,.csv"
                        titulo="Elige el archivo de la lectora"
                        subtitulo="Formato .txt · máximo 5 MB · se revisa antes de guardar"
                    />

                    <div wire:loading.flex wire:target="archivo" class="items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <flux:icon.loading class="size-4" />
                        Revisando las hojas…
                    </div>

                    <flux:error name="archivo" />
                </flux:field>

                <div class="space-y-2 rounded-lg bg-zinc-50 px-4 py-3 text-sm leading-relaxed text-pretty text-zinc-600 dark:bg-white/[0.03] dark:text-zinc-400">
                    <p>
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">Columnas que se leen:</span>
                        <strong class="font-semibold text-zinc-800 dark:text-zinc-200">«NRO DE DNI»</strong>, «APELLIDOS Y NOMBRES»,
                        «Nota», «Aciertos», «Errores», «Blancos», «Dobles» y las «RESPUESTAS». El puntaje es el número de aciertos.
                    </p>
                    <p>
                        Antes de guardar verás cuántas hojas trae el archivo, quiénes quedarán sin examen y si hay alguna
                        observación. Con una sola observación no se puede importar.
                    </p>
                </div>
            @else
                <div class="flex items-center gap-3 rounded-lg bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                    <flux:icon.txt-01 class="size-6 shrink-0 text-zinc-400" />

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $vistaPrevia['archivo'] }}</div>
                        <div class="text-xs text-zinc-500 tabular-nums">
                            {{ number_format($vistaPrevia['filas']) }} hoja(s)
                            @if ($vistaPrevia['preguntas'])
                                · {{ $vistaPrevia['preguntas'] }} preguntas por hoja
                            @endif
                        </div>
                    </div>

                    <flux:button wire:click="quitarArchivo" variant="ghost" size="sm" icon="cancel-01">Elegir otro</flux:button>
                </div>

                @if ($vistaPrevia['total_errores'] > 0)
                    <flux:callout icon="alert-02" variant="danger">
                        <flux:callout.heading>No se puede importar: {{ $vistaPrevia['total_errores'] }} observación(es)</flux:callout.heading>
                        <flux:callout.text>Corrige el archivo y vuelve a elegirlo. Mientras tanto no se guarda nada.</flux:callout.text>
                    </flux:callout>
                @elseif ($vistaPrevia['cargado_antes'])
                    <flux:callout icon="information-square" variant="warning">
                        <flux:callout.text>
                            Este mismo archivo ya se cargó el {{ $vistaPrevia['cargado_antes'] }}. Importarlo otra vez no cambia ninguna hoja.
                        </flux:callout.text>
                    </flux:callout>
                @endif

                @php
                    $conObservaciones = $vistaPrevia['total_errores'] > 0;
                    $detalleDeLaCarga = $conObservaciones
                        ? 'Nada, hasta corregir el archivo'
                        : collect([
                            $vistaPrevia['nuevas'] > 0 ? $vistaPrevia['nuevas'].' nueva(s)' : null,
                            $vistaPrevia['actualizadas'] > 0 ? $vistaPrevia['actualizadas'].' actualizada(s)' : null,
                            $vistaPrevia['sin_cambios'] > 0 ? $vistaPrevia['sin_cambios'].' sin cambios' : null,
                        ])->filter()->implode(' · ');
                @endphp

                <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg bg-zinc-200/70 sombra-borde sm:grid-cols-4 dark:bg-white/10">
                    @foreach ([
                        ['Hojas en el archivo', $vistaPrevia['filas'], $conObservaciones ? $vistaPrevia['total_errores'].' con observaciones' : 'Todas son de inscritos', $conObservaciones ? 'danger' : null],
                        ['Se cargarán', $conObservaciones ? 0 : $vistaPrevia['validas'], $detalleDeLaCarga, null],
                        ['Quedarán sin examen', $vistaPrevia['total_faltantes'], 'de '.number_format($vistaPrevia['inscritos']).' inscritos', $vistaPrevia['total_faltantes'] > 0 ? 'aviso' : null],
                        ['Nombre distinto', $vistaPrevia['total_nombres_distintos'], 'Se cargan marcadas', $vistaPrevia['total_nombres_distintos'] > 0 ? 'aviso' : null],
                    ] as [$etiqueta, $valor, $detalle, $tono])
                        <div class="bg-white px-4 py-3 dark:bg-zinc-900">
                            <dt class="text-xs font-medium text-zinc-500">{{ $etiqueta }}</dt>
                            <dd @class([
                                'mt-1 text-2xl leading-none font-semibold tracking-tight tabular-nums',
                                'text-red-600 dark:text-red-400' => $tono === 'danger',
                                'text-amber-600 dark:text-amber-400' => $tono === 'aviso',
                                'text-zinc-900 dark:text-white' => $tono === null,
                            ])>{{ number_format($valor) }}</dd>
                            <dd class="mt-1.5 text-xs text-zinc-500 tabular-nums">{{ $detalle }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($vistaPrevia['puntajes'])
                    <p class="-mt-2 text-sm text-zinc-600 tabular-nums dark:text-zinc-400">
                        Puntaje de las hojas válidas: máximo
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $vistaPrevia['puntajes']['maximo'] }}</span>,
                        promedio <span class="font-semibold text-zinc-900 dark:text-white">{{ $vistaPrevia['puntajes']['promedio'] }}</span>
                        y mínimo <span class="font-semibold text-zinc-900 dark:text-white">{{ $vistaPrevia['puntajes']['minimo'] }}</span>.
                    </p>
                @endif

                @if ($vistaPrevia['total_errores'] > 0)
                    <section>
                        <h3 class="mb-2 text-sm font-semibold text-zinc-900 dark:text-white">Observaciones</h3>
                        <ul class="max-h-52 space-y-1 overflow-y-auto rounded-lg bg-red-50/60 px-4 py-3 text-sm text-red-800 dark:bg-red-400/10 dark:text-red-300">
                            @foreach ($vistaPrevia['errores'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if ($vistaPrevia['total_errores'] > count($vistaPrevia['errores']))
                            <p class="mt-1.5 text-xs text-zinc-500">…y {{ $vistaPrevia['total_errores'] - count($vistaPrevia['errores']) }} más.</p>
                        @endif
                    </section>
                @endif

                @if ($vistaPrevia['total_faltantes'] > 0)
                    <section>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
                            Quedarán sin examen <span class="font-normal text-zinc-500 tabular-nums">({{ number_format($vistaPrevia['total_faltantes']) }})</span>
                        </h3>
                        <p class="mt-0.5 mb-2 text-xs text-pretty text-zinc-500">
                            Inscritos que no están en este archivo ni en una carga anterior. Si es el archivo completo del examen,
                            son quienes no se presentaron.
                        </p>
                        <div class="max-h-60 overflow-y-auto rounded-lg sombra-borde">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-zinc-50 text-start text-xs font-semibold tracking-[0.04em] text-zinc-500 uppercase dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-3 py-2 text-start font-semibold">DNI</th>
                                        <th class="px-3 py-2 text-start font-semibold">Apellidos y nombres</th>
                                        <th class="px-3 py-2 text-start font-semibold">Puesto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-white/10">
                                    @foreach ($vistaPrevia['faltantes'] as $faltante)
                                        <tr>
                                            <td class="px-3 py-2 text-zinc-600 tabular-nums dark:text-zinc-300">{{ $faltante['documento'] }}</td>
                                            <td class="px-3 py-2 font-medium text-zinc-900 dark:text-white">{{ $faltante['nombres'] }}</td>
                                            <td class="px-3 py-2 text-xs text-zinc-500">{{ $faltante['puesto'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($vistaPrevia['total_faltantes'] > count($vistaPrevia['faltantes']))
                            <p class="mt-1.5 text-xs text-zinc-500">
                                …y {{ number_format($vistaPrevia['total_faltantes'] - count($vistaPrevia['faltantes'])) }} más; después de
                                importar los verás todos con la cifra «Sin examen».
                            </p>
                        @endif
                    </section>
                @endif

                @if ($vistaPrevia['total_nombres_distintos'] > 0)
                    <section>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
                            Nombre distinto al del padrón <span class="font-normal text-zinc-500 tabular-nums">({{ number_format($vistaPrevia['total_nombres_distintos']) }})</span>
                        </h3>
                        <p class="mt-0.5 mb-2 text-xs text-pretty text-zinc-500">
                            Se cargan por su DNI, pero conviene confirmar con la hoja física que el DNI marcado sea el correcto.
                        </p>
                        <div class="max-h-60 overflow-y-auto rounded-lg sombra-borde">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-zinc-50 text-xs tracking-[0.04em] text-zinc-500 uppercase dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-3 py-2 text-start font-semibold">DNI</th>
                                        <th class="px-3 py-2 text-start font-semibold">En el padrón</th>
                                        <th class="px-3 py-2 text-start font-semibold">En la hoja</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-white/10">
                                    @foreach ($vistaPrevia['nombres_distintos'] as $distinto)
                                        <tr>
                                            <td class="px-3 py-2 text-zinc-600 tabular-nums dark:text-zinc-300">{{ $distinto['documento'] }}</td>
                                            <td class="px-3 py-2 font-medium text-zinc-900 dark:text-white">{{ $distinto['padron'] }}</td>
                                            <td class="px-3 py-2 text-amber-700 dark:text-amber-400">{{ $distinto['hoja'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            @endif

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button
                    type="submit"
                    variant="primary"
                    icon="upload-04"
                    :disabled="! ($vistaPrevia['importable'] ?? false)"
                >
                    @if ($vistaPrevia && $vistaPrevia['importable'])
                        Importar {{ number_format($vistaPrevia['validas']) }} hoja(s)
                    @else
                        Importar
                    @endif
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hoja" class="w-full md:max-w-2xl">
        @if ($hoja)
            <div class="space-y-5">
                <div class="pe-8">
                    <flux:heading size="lg">{{ $hoja->inscripcion->apellidos_nombres_ins }}</flux:heading>
                    <flux:subheading class="tabular-nums">
                        DNI {{ $hoja->inscripcion->documento_ins }} · {{ $hoja->inscripcion->puesto->denominacion() }}
                    </flux:subheading>
                </div>

                @unless ($hoja->nombre_coincide_exa)
                    <flux:callout icon="alert-02" variant="warning">
                        <flux:callout.text>
                            La lectora imprimió otro nombre en esta hoja: <strong class="font-semibold">{{ $hoja->apellidos_nombres_exa }}</strong>.
                            Confirma con la hoja física que el DNI marcado sea el correcto.
                        </flux:callout.text>
                    </flux:callout>
                @endunless

                <dl class="grid grid-cols-5 divide-x divide-zinc-100 rounded-lg bg-zinc-50 py-3 text-center dark:divide-white/10 dark:bg-white/[0.03]">
                    @foreach ([
                        ['Puntaje', $hoja->puntaje()],
                        ['Aciertos', $hoja->aciertos_exa],
                        ['Errores', $hoja->errores_exa],
                        ['Blancos', $hoja->blancos_exa],
                        ['Dobles', $hoja->dobles_exa],
                    ] as [$etiqueta, $valor])
                        <div class="flex flex-col-reverse gap-1 px-2">
                            <dt class="text-xs text-zinc-500">{{ $etiqueta }}</dt>
                            <dd @class([
                                'text-lg leading-none font-semibold tabular-nums',
                                'text-pj-700 dark:text-pj-400' => $loop->first,
                                'text-zinc-900 dark:text-white' => ! $loop->first,
                            ])>{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($hoja->respuestas() !== [])
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-zinc-900 dark:text-white">Respuestas marcadas</h3>

                        <ol class="grid grid-cols-5 gap-1.5 sm:grid-cols-10">
                            @foreach ($hoja->respuestas() as $pregunta => $respuesta)
                                <li
                                    @class([
                                        'flex flex-col items-center rounded-md py-1.5 sombra-borde',
                                        'bg-amber-50 dark:bg-amber-400/10' => $respuesta === App\Models\Examen::DOBLE,
                                        'bg-white dark:bg-zinc-800' => $respuesta !== App\Models\Examen::DOBLE,
                                    ])
                                >
                                    <span class="text-[0.6875rem] leading-none text-zinc-400 tabular-nums">{{ $pregunta }}</span>
                                    <span @class([
                                        'mt-1 text-sm leading-none font-semibold',
                                        'text-zinc-300 dark:text-zinc-600' => $respuesta === App\Models\Examen::BLANCO,
                                        'text-amber-700 dark:text-amber-400' => $respuesta === App\Models\Examen::DOBLE,
                                        'text-zinc-900 dark:text-white' => ! in_array($respuesta, [App\Models\Examen::BLANCO, App\Models\Examen::DOBLE], true),
                                    ])>{{ $respuesta === App\Models\Examen::BLANCO ? '–' : $respuesta }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <p class="mt-2 text-xs text-zinc-500">«–» pregunta en blanco · «*» más de una marca.</p>
                    </div>
                @endif

                @if ($hoja->importacion)
                    <p class="border-t border-zinc-100 pt-3 text-xs text-zinc-500 tabular-nums dark:border-white/10">
                        Cargada desde {{ $hoja->importacion->archivo_imp }} el {{ $hoja->importacion->created_at->format('d/m/Y \a \l\a\s H:i') }}
                        @if ($hoja->importacion->usuario)
                            por {{ $hoja->importacion->usuario->nombre_usu }}
                        @endif
                    </p>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
