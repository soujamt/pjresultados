<?php

namespace App\Enums;

/**
 * Comite que firma la publicacion de resultados, al pie del Anexo 07.
 */
enum ComiteSeleccion: string
{
    case Permanente = 'permanente';
    case AdHoc = 'ad_hoc';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Permanente => 'Comité de Selección de Personal Permanente',
            self::AdHoc => 'Comité de Selección de Personal Ad Hoc',
        };
    }
}
