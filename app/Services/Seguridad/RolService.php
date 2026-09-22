<?php

namespace App\Services\Seguridad;

use App\Enums\Permiso;
use App\Models\Rol;
use App\Services\ServicioDeCatalogo;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @extends ServicioDeCatalogo<Rol>
 */
class RolService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Rol::class;
    }

    /**
     * Los permisos se filtran contra el enum para no guardar valores que el
     * sistema no reconoce. El super administrador no se toca desde aqui: su
     * acceso total no depende de la lista.
     *
     * @param  array<string, mixed>  $datos
     * @param  ?Rol  $registro
     */
    public function guardar(array $datos, ?Model $registro = null): Model
    {
        if ($registro?->esSuperAdministrador()) {
            unset($datos['permisos_rol'], $datos['estado_rol']);
        }

        if (isset($datos['permisos_rol'])) {
            $datos['permisos_rol'] = array_values(array_intersect(Permiso::valores(), $datos['permisos_rol']));
        }

        unset($datos['es_super_rol']);

        return parent::guardar($datos, $registro);
    }

    /**
     * @param  Rol  $registro
     */
    public function alternarEstado(Model $registro): Model
    {
        if ($registro->esSuperAdministrador()) {
            throw new RuntimeException('El rol super administrador no se puede deshabilitar.');
        }

        return parent::alternarEstado($registro);
    }

    /**
     * @param  Rol  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        if ($registro->esSuperAdministrador()) {
            return 'El rol super administrador no se puede eliminar.';
        }

        $usuarios = $registro->usuarios()->count();

        return $usuarios > 0
            ? "El rol tiene {$usuarios} usuario(s) asignado(s); cámbialos de rol antes de eliminarlo."
            : null;
    }
}
