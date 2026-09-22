<?php

namespace App\Services\Seleccion;

use App\Enums\TipoImportacion;
use App\Models\Importacion;
use App\Models\Proceso;
use App\Models\Usuario;

/**
 * Deja constancia de cada archivo cargado, se haya aplicado o no.
 */
class BitacoraDeImportaciones
{
    /**
     * Errores que se guardan por carga. El resto solo se cuenta en pantalla.
     */
    private const MAXIMO_ERRORES = 100;

    public function registrar(
        Proceso $proceso,
        TipoImportacion $tipo,
        string $archivo,
        string $nombreArchivo,
        ResultadoImportacion $resultado,
        ?Usuario $usuario,
    ): Importacion {
        return Importacion::create([
            'id_pro' => $proceso->id_pro,
            'id_usu' => $usuario?->id_usu,
            'tipo_imp' => $tipo,
            'archivo_imp' => mb_substr($nombreArchivo, 0, 255),
            'hash_imp' => (string) hash_file('sha256', $archivo),
            'filas_imp' => $resultado->filas,
            'creados_imp' => $resultado->creados,
            'actualizados_imp' => $resultado->actualizados,
            'errores_imp' => $resultado->tieneErrores()
                ? array_slice($resultado->errores, 0, self::MAXIMO_ERRORES)
                : null,
            'aplicada_imp' => $resultado->aplicada,
        ]);
    }
}
