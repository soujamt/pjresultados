<?php

namespace App\Services\Reportes;

use App\Models\Proceso;
use App\Services\Evaluacion\ResultadoDePuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;

class PdfDeResultados
{
    public function __construct(private readonly FuenteArial $arial) {}

    /**
     * @param  list<ResultadoDePuesto>  $resultados
     */
    public function generar(Proceso $proceso, array $resultados): DocumentoPdf
    {
        $pdf = Pdf::loadView('reportes.resultados-tecnica', ['proceso' => $proceso, 'resultados' => $resultados])
            ->setPaper('a4', 'landscape');

        $this->arial->registrar($pdf->getDomPDF());

        return $pdf;
    }
}
