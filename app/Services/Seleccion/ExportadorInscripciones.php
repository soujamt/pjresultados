<?php

namespace App\Services\Seleccion;

use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Excel\EscritorXlsx;
use Illuminate\Support\Str;

/**
 * Listado de postulantes para la lectora de fichas ópticas.
 *
 * El programa de la lectora corre en otra maquina y solo necesita reconocer a
 * cada postulante por su DNI, asi que el archivo es deliberadamente simple:
 * una hoja, la cabecera en la fila 1 y dos columnas de texto.
 */
class ExportadorInscripciones
{
    public function __construct(private readonly InscripcionService $inscripciones) {}

    /**
     * Escribe el Excel en un archivo temporal y devuelve su ruta junto con el
     * nombre con el que se descarga. Respeta los mismos filtros del listado.
     *
     * @return array{ruta: string, nombre: string}
     */
    public function paraLectora(Proceso $proceso, ?Unidad $unidad = null, ?Puesto $puesto = null, string $busqueda = ''): array
    {
        $postulantes = $this->inscripciones
            ->consulta([
                'proceso' => $proceso->id_pro,
                'unidad' => $unidad?->id_uni,
                'puesto' => $puesto?->id_pue,
                'busqueda' => $busqueda,
            ])
            ->setEagerLoads([])
            ->get(['documento_ins', 'apellidos_nombres_ins']);

        $escritor = (new EscritorXlsx('Postulantes'))->cabecera(['DNI', 'APELLIDOS Y NOMBRES'], [14, 55]);

        foreach ($postulantes as $postulante) {
            $escritor->fila([$postulante->documento_ins, $postulante->apellidos_nombres_ins]);
        }

        $ruta = (string) tempnam(sys_get_temp_dir(), 'pj');
        $escritor->escribir($ruta);

        return [
            'ruta' => $ruta,
            'nombre' => $this->nombreDeArchivo($proceso, $unidad, $puesto),
        ];
    }

    /**
     * «postulantes-002-2026-ue-ucayali.xlsx», con la unidad o el puesto al
     * final cuando el listado va filtrado, para no confundir los archivos.
     */
    private function nombreDeArchivo(Proceso $proceso, ?Unidad $unidad, ?Puesto $puesto): string
    {
        $partes = ['postulantes', $proceso->codigo_pro];

        if ($unidad !== null) {
            $partes[] = $unidad->nombre_uni;
        }

        if ($puesto !== null) {
            $partes[] = $puesto->codigo_pue;
        }

        return Str::slug(implode(' ', $partes)).'.xlsx';
    }
}
