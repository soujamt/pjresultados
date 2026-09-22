<?php

namespace App\Services\Seleccion;

use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Builder;

class InscripcionService
{
    /**
     * Consulta del listado con los filtros de la pantalla.
     *
     * @param  array{proceso: ?int, puesto: ?int, busqueda: string}  $filtros
     * @return Builder<Inscripcion>
     */
    public function consulta(array $filtros): Builder
    {
        $busqueda = trim($filtros['busqueda']);

        return Inscripcion::query()
            ->with('puesto')
            ->when($filtros['proceso'] !== null, fn (Builder $consulta) => $consulta->delProceso($filtros['proceso']))
            ->when($filtros['proceso'] === null, fn (Builder $consulta) => $consulta->whereRaw('1 = 0'))
            ->when($filtros['puesto'] !== null, fn (Builder $consulta) => $consulta->where('id_pue', $filtros['puesto']))
            ->when($busqueda !== '', function (Builder $consulta) use ($busqueda): void {
                $consulta->where(function (Builder $consulta) use ($busqueda): void {
                    $consulta->where('documento_ins', 'like', "{$busqueda}%")
                        ->orWhere('apellidos_nombres_ins', 'like', '%'.mb_strtoupper($busqueda).'%');
                });
            })
            ->orderByRaw('numero_ins is null')
            ->orderBy('numero_ins')
            ->orderBy('apellidos_nombres_ins');
    }

    public function eliminar(Inscripcion $inscripcion): void
    {
        $inscripcion->delete();
    }
}
