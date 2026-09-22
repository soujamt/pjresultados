<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Inicio"
        :bajada="collect(['Sesión de '.auth()->user()->nombre_usu, auth()->user()->rol?->nombre_rol])->filter()->implode(' · ')"
    />

    @if ($proceso)
        <x-panel>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="text-xs font-semibold tracking-[0.06em] text-pj-700 uppercase dark:text-pj-400">
                        Proceso vigente · {{ $proceso->codigo_pro }}
                    </div>
                    <h2 class="mt-1.5 text-base leading-snug font-semibold text-balance text-zinc-900 dark:text-white">
                        {{ $proceso->nombre_pro }}
                    </h2>
                    <p class="mt-1 text-sm text-pretty text-zinc-500 dark:text-zinc-400">
                        {{ $proceso->entidad_pro }}
                        @if ($proceso->regimen_pro)
                            · {{ $proceso->regimen_pro }}
                        @endif
                    </p>
                </div>

                @can(App\Enums\Permiso::ProcesosVer->value)
                    <flux:button :href="route('seleccion.procesos')" wire:navigate size="sm" icon:trailing="arrow-right-01">
                        Ver procesos
                    </flux:button>
                @endcan
            </div>
        </x-panel>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['Unidades de organización', $unidades, 'building-03', 'seleccion.unidades', App\Enums\Permiso::UnidadesVer],
                ['Puestos convocados', $puestos, 'briefcase-01', 'seleccion.puestos', App\Enums\Permiso::PuestosVer],
                ['Postulantes inscritos', $inscritos, 'user-list', 'seleccion.inscripciones', App\Enums\Permiso::InscripcionesVer],
            ] as [$etiqueta, $valor, $icono, $ruta, $permiso])
                <x-panel class="flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $etiqueta }}</span>
                        <flux:icon :icon="$icono" class="size-5 text-zinc-400" />
                    </div>

                    <div class="mt-3 text-3xl leading-none font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">
                        {{ number_format($valor) }}
                    </div>

                    @can($permiso->value)
                        <a
                            href="{{ route($ruta, ['proceso' => $proceso->codigo_pro]) }}"
                            wire:navigate
                            class="mt-4 inline-flex items-center gap-1 self-start text-sm font-medium text-pj-700 hover:text-pj-800 hover:underline hover:underline-offset-4 dark:text-pj-400"
                        >
                            Ver detalle
                            <flux:icon.arrow-right-01 class="size-4" />
                        </a>
                    @endcan
                </x-panel>
            @endforeach
        </div>

        <x-panel titulo="Evaluación técnica" descripcion="Datos publicados en el Anexo 06-A de la convocatoria.">
            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-3">
                <div class="flex gap-3">
                    <flux:icon.calendar-03 class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                    <div>
                        <dt class="text-xs font-medium tracking-[0.04em] text-zinc-500 uppercase">Fecha</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 first-letter:uppercase dark:text-white">
                            {{ $proceso->fecha_evaluacion_pro?->translatedFormat('l j \d\e F \d\e Y') ?? 'Por definir' }}
                        </dd>
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:icon.clock-01 class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                    <div>
                        <dt class="text-xs font-medium tracking-[0.04em] text-zinc-500 uppercase">Hora</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $proceso->hora_evaluacion_pro ?? 'Por definir' }}
                        </dd>
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:icon.location-01 class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                    <div class="min-w-0">
                        <dt class="text-xs font-medium tracking-[0.04em] text-zinc-500 uppercase">Lugar</dt>
                        <dd class="mt-1 text-sm font-medium text-pretty text-zinc-900 dark:text-white">
                            {{ $proceso->lugar_evaluacion_pro ?? 'Por definir' }}
                        </dd>
                    </div>
                </div>
            </dl>
        </x-panel>
    @else
        <x-panel>
            <div class="flex items-start gap-3">
                <flux:icon.information-square class="size-5 shrink-0 text-zinc-400" />
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">No hay procesos habilitados</p>
                    <p class="mt-1 text-sm text-zinc-500">Registra un proceso de selección para empezar a cargar puestos e inscripciones.</p>
                </div>
            </div>
        </x-panel>
    @endif

    <div class="flex items-start gap-3 rounded-lg border border-dashed border-zinc-300 px-4 py-3 dark:border-white/15">
        <flux:icon.wrench-01 class="mt-0.5 size-5 shrink-0 text-zinc-400" />
        <p class="text-sm leading-relaxed text-pretty text-zinc-600 dark:text-zinc-400">
            <span class="font-medium text-zinc-800 dark:text-zinc-200">Módulos en construcción.</span>
            Los procesos, unidades, puestos e inscripciones ya están operativos; exámenes y resultados se habilitarán
            por etapas.
        </p>
    </div>
</div>
