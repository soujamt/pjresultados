<?php

namespace App\Services\Seleccion;

use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\ServicioDeCatalogo;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Puesto>
 */
class PuestoService extends ServicioDeCatalogo
{
    public function __construct(private readonly UnidadService $unidades) {}

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

    /**
     * Deja los puestos del proceso como dice el archivo: crea los que faltan,
     * restaura los eliminados y corrige el nombre y la unidad de los que
     * cambiaron. Un dato que el archivo no trae (nombre vacio, unidad null) no
     * borra el que ya estaba guardado, y los puestos ausentes no se tocan.
     *
     * No abre transaccion: se llama dentro de la de cada importacion. Quien
     * llama ya valido que los puestos nuevos traen nombre.
     *
     * @param  array<string, array{nombre: string, unidad: ?string, fila: int}>  $puestos
     * @return array{creados: int, actualizados: int, sin_cambios: int}
     */
    public function sincronizar(Proceso $proceso, array $puestos): array
    {
        $idsDeUnidad = $this->unidades->idsPorNombre(array_values(array_filter(array_column($puestos, 'unidad'))));
        $existentes = Puesto::withTrashed()->delProceso($proceso->id_pro)->get()->keyBy('codigo_pue');
        $conteo = ['creados' => 0, 'actualizados' => 0, 'sin_cambios' => 0];

        foreach ($puestos as $codigo => ['nombre' => $nombre, 'unidad' => $unidad]) {
            $idUnidad = $unidad === null ? null : $idsDeUnidad[Unidad::claveDeNombre($unidad)];
            $puesto = $existentes->get($codigo);

            if ($puesto === null) {
                Puesto::create([
                    'id_pro' => $proceso->id_pro,
                    'id_uni' => $idUnidad,
                    'codigo_pue' => $codigo,
                    'nombre_pue' => $nombre,
                    'estado_pue' => EstadoRegistro::Habilitado,
                ]);
                $conteo['creados']++;

                continue;
            }

            if ($nombre !== '') {
                $puesto->nombre_pue = $nombre;
            }

            if ($idUnidad !== null) {
                $puesto->id_uni = $idUnidad;
            }

            if ($puesto->trashed()) {
                $puesto->deleted_at = null;
            }

            if (! $puesto->isDirty()) {
                $conteo['sin_cambios']++;

                continue;
            }

            $puesto->save();
            $conteo['actualizados']++;
        }

        return $conteo;
    }
}
