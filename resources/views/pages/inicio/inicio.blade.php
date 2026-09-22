<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Hola, {{ auth()->user()->nombre_usu }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ auth()->user()->rol?->nombre_rol }} · Corte Superior de Justicia de Ucayali
        </flux:subheading>
    </div>

    <flux:separator />

    @if ($proceso)
        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:badge color="red" size="sm">{{ $proceso->codigo_pro }}</flux:badge>
                    <flux:heading size="lg" class="mt-2">{{ $proceso->nombre_pro }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ $proceso->entidad_pro }}
                        @if ($proceso->regimen_pro)
                            · {{ $proceso->regimen_pro }}
                        @endif
                    </flux:text>
                </div>

                @can(App\Enums\Permiso::ProcesosVer->value)
                    <flux:button :href="route('seleccion.procesos')" wire:navigate size="sm" icon="pencil-square">
                        Ver proceso
                    </flux:button>
                @endcan
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                    <div class="text-xs font-medium tracking-wide text-zinc-500 uppercase">Puestos</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $puestos }}</div>
                    @if ($unidades > 0)
                        <div class="text-xs text-zinc-500">en {{ $unidades }} unidad(es) de organización</div>
                    @endif
                </div>

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                    <div class="text-xs font-medium tracking-wide text-zinc-500 uppercase">Inscritos</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $inscritos }}</div>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                    <div class="text-xs font-medium tracking-wide text-zinc-500 uppercase">Evaluación técnica</div>
                    <div class="mt-1 text-sm font-medium">
                        {{ $proceso->fecha_evaluacion_pro?->translatedFormat('l j \d\e F \d\e Y') ?? 'Sin fecha' }}
                    </div>
                    <div class="text-xs text-zinc-500">{{ $proceso->hora_evaluacion_pro }}</div>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                    <div class="text-xs font-medium tracking-wide text-zinc-500 uppercase">Lugar</div>
                    <div class="mt-1 text-sm">{{ $proceso->lugar_evaluacion_pro ?? '—' }}</div>
                </div>
            </div>
        </div>
    @else
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.heading>No hay procesos habilitados</flux:callout.heading>
            <flux:callout.text>Registra un proceso de selección para empezar a cargar puestos e inscripciones.</flux:callout.text>
        </flux:callout>
    @endif

    <flux:callout icon="wrench-screwdriver" variant="secondary">
        <flux:callout.heading>Sistema en construcción</flux:callout.heading>
        <flux:callout.text>
            Ya están operativos el acceso, los roles y permisos, los procesos, los puestos y las inscripciones.
            Los módulos de exámenes y resultados se irán habilitando por etapas.
        </flux:callout.text>
    </flux:callout>
</div>
