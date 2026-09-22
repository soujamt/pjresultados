<?php

namespace App\Console\Commands;

use App\Models\Proceso;
use App\Services\Seleccion\ProcesoService;
use Illuminate\Console\Command;

/**
 * @phpstan-require-extends Command
 */
trait ResuelveProceso
{
    /**
     * Proceso indicado con --proceso, o el habilitado mas reciente. Escribe el
     * error en consola y devuelve null si no hay ninguno.
     */
    protected function resolverProceso(mixed $codigo): ?Proceso
    {
        $proceso = blank($codigo)
            ? app(ProcesoService::class)->vigente()
            : Proceso::where('codigo_pro', mb_strtoupper(trim((string) $codigo)))->first();

        if ($proceso === null) {
            $this->components->error(blank($codigo)
                ? 'No hay ningún proceso habilitado. Crea uno o indica --proceso.'
                : "No existe el proceso {$codigo}.");
        }

        return $proceso;
    }
}
