<?php

namespace App\Exports;

use App\Models\Proceso;
use App\Services\Evaluacion\ResultadoDePuesto;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Anexo 07 en Excel: una hoja por puesto, con el mismo formato del PDF, por si
 * el comite necesita retocar algo antes de publicar.
 */
class ResultadosTecnicaExport implements Export, WithMultipleSheets
{
    /**
     * @param  list<ResultadoDePuesto>  $resultados
     */
    public function __construct(
        private readonly Proceso $proceso,
        private readonly array $resultados,
    ) {}

    /**
     * @return list<ResultadosTecnicaHoja>
     */
    public function sheets(): array
    {
        return array_map(
            fn (ResultadoDePuesto $delPuesto): ResultadosTecnicaHoja => new ResultadosTecnicaHoja($this->proceso, $delPuesto),
            $this->resultados,
        );
    }
}
