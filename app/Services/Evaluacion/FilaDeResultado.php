<?php

namespace App\Services\Evaluacion;

use App\Enums\CondicionResultado;
use App\Models\Inscripcion;

/**
 * Una fila del Anexo 07: el postulante con su nota, su nota parcial, su
 * puntaje de evaluacion tecnica, la condicion y la observacion.
 */
final readonly class FilaDeResultado
{
    public function __construct(
        public int $numero,
        public Inscripcion $inscripcion,
        public int $nota,
        public float $notaParcial,
        public float $puntaje,
        public CondicionResultado $condicion,
        public ?string $observacion,
        public bool $rindio,
        public bool $descalificado,
    ) {}

    public function noSePresento(): bool
    {
        return ! $this->rindio && ! $this->descalificado;
    }

    /**
     * Nota parcial con dos decimales: 26 aciertos dan «17.33».
     */
    public function notaParcialTexto(): string
    {
        return number_format($this->notaParcial, 2, '.', '');
    }

    public function puntajeTexto(): string
    {
        return number_format($this->puntaje, 2, '.', '');
    }
}
