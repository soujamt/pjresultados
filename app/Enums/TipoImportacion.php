<?php

namespace App\Enums;

/**
 * Archivos que se cargan al sistema. Cada carga queda registrada en
 * `tbl_importacion` con su tipo, para auditar quien subio que y cuando.
 */
enum TipoImportacion: string
{
    case Puestos = 'puestos';
    case Inscripciones = 'inscripciones';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Puestos => 'Puestos',
            self::Inscripciones => 'Inscripciones',
        };
    }
}
