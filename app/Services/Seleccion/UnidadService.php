<?php

namespace App\Services\Seleccion;

use App\Enums\EstadoRegistro;
use App\Models\Unidad;
use App\Services\ServicioDeCatalogo;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Unidad>
 */
class UnidadService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Unidad::class;
    }

    protected function eliminadoEquivalente(array $datos): ?Model
    {
        return Unidad::onlyTrashed()->where('nombre_uni', $datos['nombre_uni'])->first();
    }

    /**
     * @param  Unidad  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $puestos = $registro->puestos()->count();

        return $puestos > 0
            ? "La unidad tiene {$puestos} puesto(s) asignado(s); cámbialos de unidad antes de eliminarla."
            : null;
    }

    /**
     * Identificadores de las unidades nombradas en un archivo, creando las que
     * no existen y restaurando las que se habian eliminado. Se reconocen sin
     * importar tildes ni espacios, para no duplicar «MÓDULO» con «MODULO».
     *
     * Se llama dentro de la transaccion de la importacion.
     *
     * @param  list<string>  $nombres
     * @return array<string, int> clave del nombre => id_uni
     */
    public function idsPorNombre(array $nombres): array
    {
        if ($nombres === []) {
            return [];
        }

        $existentes = Unidad::withTrashed()
            ->get()
            ->keyBy(fn (Unidad $unidad): string => Unidad::claveDeNombre($unidad->nombre_uni));

        $ids = [];

        foreach ($nombres as $nombre) {
            $clave = Unidad::claveDeNombre($nombre);

            if (isset($ids[$clave])) {
                continue;
            }

            $unidad = $existentes->get($clave);

            if ($unidad === null) {
                $unidad = Unidad::create([
                    'nombre_uni' => Unidad::normalizarNombre($nombre),
                    'estado_uni' => EstadoRegistro::Habilitado,
                ]);
            } elseif ($unidad->trashed()) {
                $unidad->restore();
            }

            $ids[$clave] = $unidad->id_uni;
        }

        return $ids;
    }
}
