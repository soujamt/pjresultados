<?php

namespace App\Services\Seleccion;

use App\Enums\EstadoRegistro;
use App\Enums\TipoImportacion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Carga los puestos convocados a partir del listado del Anexo 06-A.
 *
 * El anexo trae una fila por postulante con el codigo y el nombre del puesto,
 * asi que los puestos salen de agrupar esas dos columnas. Un codigo que se
 * repite con otro nombre es un error de digitacion y bloquea la carga entera.
 *
 * Volver a subir el archivo no duplica nada: los puestos se identifican por su
 * codigo dentro del proceso, se actualiza el nombre si cambio y se restaura el
 * que se hubiera eliminado. Los puestos que no vienen en el archivo se dejan
 * como estan.
 */
class ImportadorPuestos
{
    public function __construct(private readonly BitacoraDeImportaciones $bitacora) {}

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

        [$puestos, $errores, $leidas] = $this->agrupar($anexo);

        $resultado = $puestos === [] || $errores !== []
            ? ResultadoImportacion::rechazada($leidas, $errores)
            : $this->guardar($proceso, $puestos, $leidas);

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
     * @return array{0: array<string, array{nombre: string, fila: int}>, 1: list<string>, 2: int}
     */
    private function agrupar(AnexoPostulantes $anexo): array
    {
        $puestos = [];
        $errores = [];
        $leidas = 0;

        foreach ($anexo->filas as $fila => $valores) {
            $codigo = AnexoPostulantes::normalizarCodigo($valores[AnexoPostulantes::CODIGO_PUESTO]);
            $nombre = AnexoPostulantes::normalizarNombreDePuesto($valores[AnexoPostulantes::PUESTO]);

            /*
             * Filas de pie como «Pucallpa, 11 de agosto…» o la firma del
             * comite: no traen ni codigo ni puesto y no son un error.
             */
            if ($codigo === '' && $nombre === '') {
                continue;
            }

            $leidas++;

            if ($codigo === '') {
                $errores[] = "Fila {$fila}: falta el código del puesto «{$nombre}».";

                continue;
            }

            if ($nombre === '') {
                $errores[] = "Fila {$fila}: falta el nombre del puesto {$codigo}.";

                continue;
            }

            if (mb_strlen($codigo) > 20 || mb_strlen($nombre) > 150) {
                $errores[] = "Fila {$fila}: el código o el nombre del puesto {$codigo} es demasiado largo.";

                continue;
            }

            if (isset($puestos[$codigo]) && $puestos[$codigo]['nombre'] !== $nombre) {
                $errores[] = "Fila {$fila}: el código {$codigo} aparece como «{$nombre}», pero en la fila "
                    ."{$puestos[$codigo]['fila']} figura como «{$puestos[$codigo]['nombre']}».";

                continue;
            }

            $puestos[$codigo] ??= ['nombre' => $nombre, 'fila' => $fila];
        }

        return [$puestos, $errores, $leidas];
    }

    /**
     * @param  array<string, array{nombre: string, fila: int}>  $puestos
     */
    private function guardar(Proceso $proceso, array $puestos, int $filas): ResultadoImportacion
    {
        try {
            return DB::transaction(function () use ($proceso, $puestos, $filas): ResultadoImportacion {
                $existentes = Puesto::withTrashed()->delProceso($proceso->id_pro)->get()->keyBy('codigo_pue');
                $creados = $actualizados = $sinCambios = 0;

                foreach ($puestos as $codigo => ['nombre' => $nombre]) {
                    $puesto = $existentes->get($codigo);

                    if ($puesto === null) {
                        Puesto::create([
                            'id_pro' => $proceso->id_pro,
                            'codigo_pue' => $codigo,
                            'nombre_pue' => $nombre,
                            'estado_pue' => EstadoRegistro::Habilitado,
                        ]);
                        $creados++;

                        continue;
                    }

                    if (! $puesto->trashed() && $puesto->nombre_pue === $nombre) {
                        $sinCambios++;

                        continue;
                    }

                    $puesto->deleted_at = null;
                    $puesto->nombre_pue = $nombre;
                    $puesto->save();
                    $actualizados++;
                }

                return new ResultadoImportacion($filas, $creados, $actualizados, $sinCambios, aplicada: true);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron guardar los puestos. No se modificó la información anterior.');
        }
    }
}
