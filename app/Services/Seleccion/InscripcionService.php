<?php

namespace App\Services\Seleccion;

use App\Models\Inscripcion;
use App\Models\Puesto;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class InscripcionService
{
    /**
     * Consulta del listado con los filtros de la pantalla. La unidad de
     * organizacion es la del puesto, asi que se filtra por los puestos de esa
     * unidad.
     *
     * @param  array{proceso: ?int, unidad: ?int, puesto: ?int, busqueda: string}  $filtros
     * @return Builder<Inscripcion>
     */
    public function consulta(array $filtros): Builder
    {
        $busqueda = trim($filtros['busqueda']);

        return Inscripcion::query()
            ->with('puesto.unidad')
            ->when($filtros['proceso'] !== null, fn (Builder $consulta) => $consulta->delProceso($filtros['proceso']))
            ->when($filtros['proceso'] === null, fn (Builder $consulta) => $consulta->whereRaw('1 = 0'))
            ->when($filtros['unidad'] !== null, fn (Builder $consulta) => $consulta->whereIn(
                'id_pue',
                Puesto::query()->select('id_pue')->deLaUnidad($filtros['unidad']),
            ))
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

    /**
     * @throws RuntimeException si el postulante ya tiene su hoja de examen: se
     *                          perderia su calificacion.
     */
    public function eliminar(Inscripcion $inscripcion): void
    {
        if ($inscripcion->examen()->exists()) {
            throw new RuntimeException(
                "{$inscripcion->apellidos_nombres_ins} ya tiene su examen cargado. Vacía los exámenes del proceso antes de eliminar su inscripción."
            );
        }

        if ($inscripcion->descalificacion()->exists()) {
            throw new RuntimeException(
                "{$inscripcion->apellidos_nombres_ins} tiene una descalificación registrada. Quítala en Resultados antes de eliminar su inscripción."
            );
        }

        $inscripcion->delete();
    }
}
