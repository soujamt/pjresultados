<?php

namespace App\Services\Evaluacion;

use App\Enums\CondicionResultado;
use App\Models\Puesto;

/**
 * Resultados de un puesto en orden de merito: lo que va en una hoja del
 * Anexo 07.
 */
final readonly class ResultadoDePuesto
{
    /**
     * @param  list<FilaDeResultado>  $filas
     */
    public function __construct(
        public Puesto $puesto,
        public array $filas,
    ) {}

    public function inscritos(): int
    {
        return count($this->filas);
    }

    public function aptos(): int
    {
        return $this->contar(fn (FilaDeResultado $fila): bool => $fila->condicion === CondicionResultado::Apto);
    }

    /**
     * Rindieron el examen pero no alcanzaron el puntaje minimo.
     */
    public function desaprobados(): int
    {
        return $this->contar(fn (FilaDeResultado $fila): bool => $fila->rindio
            && ! $fila->descalificado
            && $fila->condicion === CondicionResultado::NoApto);
    }

    public function ausentes(): int
    {
        return $this->contar(fn (FilaDeResultado $fila): bool => $fila->noSePresento());
    }

    public function descalificados(): int
    {
        return $this->contar(fn (FilaDeResultado $fila): bool => $fila->descalificado);
    }

    /**
     * @param  callable(FilaDeResultado): bool  $condicion
     */
    private function contar(callable $condicion): int
    {
        return count(array_filter($this->filas, $condicion));
    }
}
