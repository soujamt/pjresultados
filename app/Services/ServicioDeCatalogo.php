<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use RuntimeException;

/**
 * Base de los servicios que administran los catalogos de configuracion.
 *
 * Todos hacen lo mismo —crear, editar, habilitar y borrar— y solo se
 * diferencian en cuando esta prohibido borrar, asi que cada servicio concreto
 * se reduce a declarar su modelo y esa regla.
 *
 * @template TModelo of Model
 */
abstract class ServicioDeCatalogo
{
    /**
     * @return class-string<TModelo>
     */
    abstract public function modelo(): string;

    /**
     * Motivo por el que el registro no se puede borrar, o null si se puede.
     *
     * @param  TModelo  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        return null;
    }

    /**
     * Registro borrado que ocupa la misma clave unica que los datos nuevos.
     * Las tablas tienen indices unicos que no miran `deleted_at`, asi que al
     * volver a crear algo que se habia eliminado hay que restaurarlo en vez de
     * insertar otra fila.
     *
     * @param  array<string, mixed>  $datos
     * @return ?TModelo
     */
    protected function eliminadoEquivalente(array $datos): ?Model
    {
        return null;
    }

    /**
     * Crea o actualiza segun se reciba un registro existente.
     *
     * @param  array<string, mixed>  $datos
     * @param  ?TModelo  $registro
     * @return TModelo
     */
    public function guardar(array $datos, ?Model $registro = null): Model
    {
        $registro ??= $this->eliminadoEquivalente($datos) ?? new ($this->modelo());
        $registro->fill($datos);

        if (method_exists($registro, 'trashed') && $registro->trashed()) {
            $registro->setAttribute('deleted_at', null);
        }

        $registro->save();

        return $registro;
    }

    /**
     * Los catalogos usan el trait TieneEstado, que es quien sabe en que
     * columna vive el estado.
     *
     * @param  TModelo  $registro
     * @return TModelo
     */
    public function alternarEstado(Model $registro): Model
    {
        if (! method_exists($registro, 'alternarEstado')) {
            throw new LogicException($registro::class.' no tiene columna de estado.');
        }

        $registro->alternarEstado();

        return $registro;
    }

    /**
     * Borrado logico. Lanza cuando hay registros que dependen de este, para
     * que la pantalla muestre el motivo en vez de dejar datos huerfanos.
     *
     * @param  TModelo  $registro
     */
    public function eliminar(Model $registro): void
    {
        $razon = $this->razonParaNoEliminar($registro);

        if ($razon !== null) {
            throw new RuntimeException($razon);
        }

        $registro->delete();
    }
}
