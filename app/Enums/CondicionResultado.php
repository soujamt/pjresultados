<?php

namespace App\Enums;

/**
 * Columna «CONDICIÓN (APTO - NO APTO)» del Anexo 07.
 */
enum CondicionResultado: string
{
    case Apto = 'apto';
    case NoApto = 'no_apto';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Apto => 'APTO',
            self::NoApto => 'NO APTO',
        };
    }

    /**
     * Color de Flux con el que se pinta el badge de la condicion.
     */
    public function color(): string
    {
        return match ($this) {
            self::Apto => 'green',
            self::NoApto => 'red',
        };
    }
}
