<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Resultados"
        bajada="Listado de resultados de la evaluación técnica por puesto, en orden de mérito."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::ResultadosExportar->value)
                <flux:button icon="document-text" disabled>Exportar PDF</flux:button>
            @endcan

            @can(App\Enums\Permiso::ResultadosGenerar->value)
                <flux:tooltip content="Disponible cuando se carguen los exámenes">
                    <div>
                        <flux:button variant="primary" icon="chart-bar-square" disabled>Generar resultados</flux:button>
                    </div>
                </flux:tooltip>
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

        <div class="w-full sm:w-96" wire:key="puestos-{{ $proceso?->id_pro }}">
            <flux:select wire:model.live="filtroPuesto" label="Puesto">
                <flux:select.option value="">Todos los puestos</flux:select.option>
                @foreach ($puestos as $puesto)
                    <flux:select.option :value="$puesto->id_pue">{{ $puesto->denominacion() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20">Orden</flux:table.column>
            <flux:table.column>DNI</flux:table.column>
            <flux:table.column>Apellidos y nombres</flux:table.column>
            <flux:table.column>Puesto</flux:table.column>
            <flux:table.column align="end">Puntaje</flux:table.column>
            <flux:table.column>Condición</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <x-tabla.vacia :columnas="6" mensaje="Todavía no se han generado resultados." icono="chart-bar-square">
                <span class="max-w-md text-xs">
                    Este módulo está en construcción. Los resultados se calcularán a partir de los exámenes cargados.
                </span>
            </x-tabla.vacia>
        </flux:table.rows>
    </flux:table>
</div>
