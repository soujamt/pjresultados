<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Resultados"
        bajada="Listado de resultados de la evaluación técnica por puesto, en orden de mérito."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::ResultadosExportar->value)
                <flux:button icon="file-01" disabled>Exportar PDF</flux:button>
            @endcan

            @can(App\Enums\Permiso::ResultadosGenerar->value)
                <flux:tooltip content="Disponible cuando se carguen los exámenes">
                    <div>
                        <flux:button variant="primary" icon="ranking" disabled>Generar resultados</flux:button>
                    </div>
                </flux:tooltip>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <x-panel>
        <div class="grid gap-3 sm:grid-cols-[15rem_minmax(0,28rem)]">
            <flux:select wire:model.live="codigoProceso" label="Proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                @endforeach
            </flux:select>

            <div wire:key="puestos-{{ $proceso?->id_pro }}">
                <flux:select wire:model.live="filtroPuesto" label="Puesto">
                    <flux:select.option value="">Todos los puestos</flux:select.option>
                    @foreach ($puestos as $puesto)
                        <flux:select.option :value="$puesto->id_pue">{{ $puesto->denominacion() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </x-panel>

    <x-tabla.marco>
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-20" align="end">Orden</flux:table.column>
                <flux:table.column>DNI</flux:table.column>
                <flux:table.column>Apellidos y nombres</flux:table.column>
                <flux:table.column>Puesto</flux:table.column>
                <flux:table.column align="end">Puntaje</flux:table.column>
                <flux:table.column>Condición</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                <x-tabla.vacia :columnas="6" mensaje="Todavía no se han generado resultados." icono="ranking">
                    Este módulo está en construcción. Los resultados se calcularán a partir de los exámenes cargados.
                </x-tabla.vacia>
            </flux:table.rows>
        </flux:table>
    </x-tabla.marco>
</div>
