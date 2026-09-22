<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Exámenes"
        bajada="Carga de las hojas de respuestas de la evaluación técnica, leídas por la lectora óptica."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::ExamenesImportar->value)
                <flux:tooltip content="Disponible próximamente">
                    <div>
                        <flux:button variant="primary" icon="upload-04" disabled>Importar examen</flux:button>
                    </div>
                </flux:tooltip>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <x-panel>
        <div class="w-full sm:w-60">
            <flux:select wire:model.live="codigoProceso" label="Proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </x-panel>

    <x-tabla.marco>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Archivo</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column align="end">Filas</flux:table.column>
                <flux:table.column align="end">Observaciones</flux:table.column>
                <flux:table.column>Cargado por</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                <x-tabla.vacia :columnas="6" mensaje="Aún no se ha cargado ningún examen." icono="file-upload">
                    Este módulo está en construcción. Aquí se importarán el padrón y las respuestas de la evaluación
                    técnica{{ $proceso ? ' de los '.number_format($inscritos).' postulantes inscritos' : '' }}.
                </x-tabla.vacia>
            </flux:table.rows>
        </flux:table>
    </x-tabla.marco>
</div>
