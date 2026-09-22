<?php

namespace App\Services\Seleccion;

use App\Models\Proceso;
use App\Services\ServicioDeCatalogo;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Proceso>
 */
class ProcesoService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Proceso::class;
    }

    protected function eliminadoEquivalente(array $datos): ?Model
    {
        return Proceso::onlyTrashed()->where('codigo_pro', $datos['codigo_pro'])->first();
    }

    /**
     * @param  Proceso  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        if ($registro->inscripciones()->exists()) {
            return 'El proceso tiene inscripciones registradas; elimínalas antes de borrar el proceso.';
        }

        if ($registro->puestos()->exists()) {
            return 'El proceso tiene puestos registrados; elimínalos antes de borrar el proceso.';
        }

        return null;
    }

    /**
     * Proceso con el que abren las pantallas cuando no se eligio ninguno: el
     * habilitado mas reciente.
     */
    public function vigente(): ?Proceso
    {
        return Proceso::query()->habilitado()->latest('id_pro')->first();
    }
}
