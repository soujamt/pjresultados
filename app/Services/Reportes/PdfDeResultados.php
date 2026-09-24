<?php

namespace App\Services\Reportes;

use App\Models\Proceso;
use App\Services\Evaluacion\ResultadoDePuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Dompdf\Dompdf;

/**
 * PDF del Anexo 07 listo para publicar: la vista, la fuente Arial y el
 * número de página («Página 3 de 12») abajo a la derecha, para no confundir
 * el orden de las hojas al imprimir.
 */
class PdfDeResultados
{
    /** Margen derecho de la página (2,5 cm), para alinear el número con la tabla. */
    private const MARGEN_DERECHO = 70.87;

    /** Distancia del número al borde inferior (1,2 cm), dentro del margen de 1,9 cm. */
    private const DESDE_ABAJO = 34.0;

    private const TAMANO = 7;

    /** Gris oscuro, como el texto de las tablas. */
    private const COLOR = [0.25, 0.25, 0.25];

    public function __construct(private readonly FuenteArial $arial) {}

    /**
     * @param  list<ResultadoDePuesto>  $resultados
     */
    public function generar(Proceso $proceso, array $resultados): DocumentoPdf
    {
        $pdf = Pdf::loadView('reportes.resultados-tecnica', ['proceso' => $proceso, 'resultados' => $resultados])
            ->setPaper('a4', 'landscape');

        $conArial = $this->arial->registrar($pdf->getDomPDF());

        /* El total de páginas recién se conoce al terminar de armar el documento. */
        $pdf->render();
        $this->numerarPaginas($pdf->getDomPDF(), $conArial ? 'Arial' : 'Helvetica');

        return $pdf;
    }

    private function numerarPaginas(Dompdf $dompdf, string $familia): void
    {
        $canvas = $dompdf->getCanvas();
        $metricas = $dompdf->getFontMetrics();
        $fuente = $metricas->getFont($familia);
        $ancho = $metricas->getTextWidth('Página 00 de 00', (string) $fuente, self::TAMANO);

        $canvas->page_text(
            $canvas->get_width() - self::MARGEN_DERECHO - $ancho,
            $canvas->get_height() - self::DESDE_ABAJO,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente,
            self::TAMANO,
            self::COLOR,
        );
    }
}
