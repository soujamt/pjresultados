<?php

namespace App\Services\Seleccion;

use App\Models\Puesto;
use App\Services\ServicioDeCatalogo;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Puesto>
 */
class PuestoService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Puesto::class;
    }

    protected function eliminadoEquivalente(array $datos): ?Model
    {
        return Puesto::onlyTrashed()
            ->where('id_pro', $datos['id_pro'])
            ->where('codigo_pue', $datos['codigo_pue'])
            ->first();
    }

    /**
     * @param  Puesto  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $inscritos = $registro->inscripciones()->count();

        return $inscritos > 0
            ? "El puesto {$registro->codigo_pue} tiene {$inscritos} postulante(s) inscrito(s)."
            : null;
    }
}
