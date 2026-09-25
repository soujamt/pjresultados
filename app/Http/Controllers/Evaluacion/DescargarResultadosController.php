<?php

namespace App\Http\Controllers\Evaluacion;

use App\Enums\Permiso;
use App\Exports\ResultadosTecnicaExport;
use App\Http\Controllers\Controller;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Evaluacion\ResultadoService;
use App\Services\Reportes\PdfDeResultados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DescargarResultadosController extends Controller
{
    /**
     * Descarga los resultados de la evaluación técnica en el formato del
     * Anexo 07, en PDF o en Excel. Sin puesto trae todos los del proceso (o de
     * la unidad), uno tras otro bajo una sola cabecera. En el PDF,
     * repetir_titulos=0 deja la fila de títulos de cada tabla solo al inicio,
     * sin repetirla en las páginas siguientes.
     */
    public function __invoke(Request $request, Proceso $proceso, string $formato, ResultadoService $resultados, PdfDeResultados $pdf): Response|BinaryFileResponse
    {
        Gate::authorize(Permiso::ResultadosExportar->value);

        $request->validate([
            'unidad' => ['nullable', 'integer'],
            'puesto' => ['nullable', 'integer'],
            'repetir_titulos' => ['nullable', 'boolean'],
        ]);

        abort_unless(
            $proceso->publicacionCompleta(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Completa los datos de la publicación del proceso antes de descargar los resultados.',
        );

        $unidad = $request->filled('unidad') ? Unidad::findOrFail($request->integer('unidad')) : null;
        $puesto = $request->filled('puesto')
            ? Puesto::query()->delProceso($proceso->id_pro)->findOrFail($request->integer('puesto'))
            : null;

        $porPuesto = $resultados->porPuesto($proceso, $unidad?->id_uni, $puesto?->id_pue);

        abort_if($porPuesto === [], Response::HTTP_NOT_FOUND, 'No hay postulantes inscritos con esos filtros.');

        $nombre = $resultados->nombreArchivo($proceso, $puesto);

        return $formato === 'excel'
            ? Excel::download(new ResultadosTecnicaExport($proceso, $porPuesto), "{$nombre}.xlsx")
            : $pdf->generar($proceso, $porPuesto, $request->boolean('repetir_titulos', true))->download("{$nombre}.pdf");
    }
}
