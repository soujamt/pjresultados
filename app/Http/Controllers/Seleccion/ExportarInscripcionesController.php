<?php

namespace App\Http\Controllers\Seleccion;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Seleccion\ExportadorInscripciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportarInscripcionesController extends Controller
{
    /**
     * Descarga el listado de postulantes del proceso (DNI y apellidos y
     * nombres) para la lectora de fichas ópticas. Recibe los mismos filtros
     * que la pantalla de inscripciones.
     */
    public function __invoke(Request $request, Proceso $proceso, ExportadorInscripciones $exportador): BinaryFileResponse
    {
        Gate::authorize(Permiso::InscripcionesExportar->value);

        $request->validate([
            'unidad' => ['nullable', 'integer'],
            'puesto' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $unidad = $request->filled('unidad') ? Unidad::findOrFail($request->integer('unidad')) : null;
        $puesto = $request->filled('puesto')
            ? Puesto::query()->delProceso($proceso->id_pro)->findOrFail($request->integer('puesto'))
            : null;

        $archivo = $exportador->paraLectora($proceso, $unidad, $puesto, $request->string('q')->trim()->value());

        return response()
            ->download($archivo['ruta'], $archivo['nombre'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
