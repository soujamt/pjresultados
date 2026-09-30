<?php

namespace App\Exports;

use App\Models\Proceso;
use App\Services\Evaluacion\ResultadoDePuesto;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Anexo 07 en Excel con una hoja por puesto: cada hoja es un anexo completo
 * (cabecera, datos del puesto, su tabla y el cierre) y lleva por nombre el
 * codigo del puesto. Es la otra forma de ResultadosTecnicaExport, que pone
 * todos los puestos en una sola hoja.
 */
class ResultadosTecnicaPorPuestoExport implements Export, WithMultipleSheets
{
    /**
     * @param  list<ResultadoDePuesto>  $resultados
     */
    public function __construct(
        private readonly Proceso $proceso,
        private readonly array $resultados,
    ) {}

    /**
     * @return list<ResultadosTecnicaExport>
     */
    public function sheets(): array
    {
        return array_map(
            fn (ResultadoDePuesto $delPuesto): ResultadosTecnicaExport => new ResultadosTecnicaExport($this->proceso, [$delPuesto], hojaDelPuesto: true),
            $this->resultados,
        );
    }
}
