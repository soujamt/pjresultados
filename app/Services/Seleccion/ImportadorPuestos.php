<?php

namespace App\Services\Seleccion;

use App\Enums\TipoImportacion;
use App\Models\Proceso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Carga los puestos convocados a partir del listado del Anexo 06-A.
 *
 * El anexo trae una fila por postulante con el codigo y el nombre del puesto,
 * y en las versiones mas completas tambien su unidad de organizacion; los
 * puestos salen de agrupar esas columnas. Un codigo que se repite con otro
 * nombre u otra unidad es un error de digitacion y bloquea la carga entera.
 *
 * Volver a subir el archivo no duplica nada: los puestos se identifican por su
 * codigo dentro del proceso, se actualiza el nombre y la unidad si cambiaron y
 * se restaura el que se hubiera eliminado. Los puestos que no vienen en el
 * archivo se dejan como estan.
 */
class ImportadorPuestos
{
    public function __construct(
        private readonly BitacoraDeImportaciones $bitacora,
        private readonly PuestoService $puestos,
    ) {}

    /**
     * @throws RuntimeException con un mensaje para el usuario cuando el archivo
     *                          no se puede leer o no se pudo guardar.
     */
    public function importar(Proceso $proceso, string $archivo, ?string $nombreArchivo = null, ?Usuario $usuario = null): ResultadoImportacion
    {
        $anexo = AnexoPostulantes::leer($archivo);

        if (! $anexo->tiene(AnexoPostulantes::PUESTO)) {
            throw new RuntimeException('El archivo no tiene la columna «PUESTO» con el nombre de cada puesto.');
        }

        [$delArchivo, $errores, $leidas] = $this->agrupar($anexo);

        $resultado = $delArchivo->vacio() || $errores !== []
            ? ResultadoImportacion::rechazada($leidas, $errores)
            : $this->guardar($proceso, $delArchivo, $leidas);

        $this->bitacora->registrar(
            $proceso,
            TipoImportacion::Puestos,
            $archivo,
            $nombreArchivo ?? basename($archivo),
            $resultado,
            $usuario,
        );

        return $resultado;
    }

    /**
     * @return array{0: PuestosDelArchivo, 1: list<string>, 2: int}
     */
    private function agrupar(AnexoPostulantes $anexo): array
    {
        $delArchivo = new PuestosDelArchivo;
        $errores = [];
        $leidas = 0;

        foreach ($anexo->filas as $fila => $valores) {
            $codigo = AnexoPostulantes::normalizarCodigo($valores[AnexoPostulantes::CODIGO_PUESTO]);
            $nombre = AnexoPostulantes::normalizarNombreDePuesto($valores[AnexoPostulantes::PUESTO]);
            $unidad = AnexoPostulantes::unidadDe($valores);

            /*
             * Filas de pie como «Pucallpa, 11 de agosto…» o la firma del
             * comite: no traen ni codigo ni puesto y no son un error.
             */
            if ($codigo === '' && $nombre === '') {
                continue;
            }

            $leidas++;

            $observacion = match (true) {
                $codigo === '' => "falta el código del puesto «{$nombre}»",
                $nombre === '' => "falta el nombre del puesto {$codigo}",
                mb_strlen($codigo) > 20 || mb_strlen($nombre) > 150 => "el código o el nombre del puesto {$codigo} es demasiado largo",
                $unidad !== null && mb_strlen($unidad) > 150 => 'el nombre de la unidad de organización pasa de 150 caracteres',
                default => $delArchivo->anotar($fila, $codigo, $nombre, $unidad),
            };

            if ($observacion !== null) {
                $errores[] = "Fila {$fila}: {$observacion}.";
            }
        }

        return [$delArchivo, $errores, $leidas];
    }

    private function guardar(Proceso $proceso, PuestosDelArchivo $delArchivo, int $filas): ResultadoImportacion
    {
        try {
            return DB::transaction(function () use ($proceso, $delArchivo, $filas): ResultadoImportacion {
                $conteo = $this->puestos->sincronizar($proceso, $delArchivo->todos());

                return new ResultadoImportacion(
                    $filas,
                    $conteo['creados'],
                    $conteo['actualizados'],
                    $conteo['sin_cambios'],
                    aplicada: true,
                );
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron guardar los puestos. No se modificó la información anterior.');
        }
    }
}
