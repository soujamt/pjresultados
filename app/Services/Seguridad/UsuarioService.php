<?php

namespace App\Services\Seguridad;

use App\Enums\EstadoRegistro;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Altas, cambios y bajas de cuentas. Las reglas estan pensadas para que el
 * sistema nunca se quede sin nadie que lo administre: nadie se desactiva ni se
 * borra a si mismo, y el ultimo super administrador activo no puede perder
 * ese acceso.
 */
class UsuarioService
{
    /**
     * @param  array{id_rol: int, nombre_usu: string, usuario_usu: string, estado_usu: EstadoRegistro, clave_usu?: ?string}  $datos
     */
    public function guardar(array $datos, ?Usuario $usuario, Usuario $actor): Usuario
    {
        if (blank($datos['clave_usu'] ?? null)) {
            unset($datos['clave_usu']);
        }

        return DB::transaction(function () use ($datos, $usuario, $actor): Usuario {
            if ($usuario !== null) {
                if ($usuario->is($actor) && $datos['estado_usu'] !== EstadoRegistro::Habilitado) {
                    throw new RuntimeException('No puedes deshabilitar tu propia cuenta.');
                }

                $this->asegurarQueQuedaUnSuperAdministrador($usuario, $datos['id_rol'], $datos['estado_usu']);
            }

            $usuario ??= new Usuario;
            $usuario->fill($datos);
            $usuario->save();

            return $usuario;
        });
    }

    public function alternarEstado(Usuario $usuario, Usuario $actor): Usuario
    {
        if ($usuario->is($actor)) {
            throw new RuntimeException('No puedes deshabilitar tu propia cuenta.');
        }

        $nuevoEstado = $usuario->estaHabilitado() ? EstadoRegistro::Deshabilitado : EstadoRegistro::Habilitado;
        $this->asegurarQueQuedaUnSuperAdministrador($usuario, $usuario->id_rol, $nuevoEstado);

        return $usuario->alternarEstado();
    }

    public function eliminar(Usuario $usuario, Usuario $actor): void
    {
        if ($usuario->is($actor)) {
            throw new RuntimeException('No puedes eliminar tu propia cuenta.');
        }

        $this->asegurarQueQuedaUnSuperAdministrador($usuario, null, EstadoRegistro::Deshabilitado);

        $usuario->delete();
    }

    /**
     * Impide el cambio si deja al sistema sin super administradores activos.
     */
    private function asegurarQueQuedaUnSuperAdministrador(Usuario $usuario, ?int $idRolNuevo, EstadoRegistro $estadoNuevo): void
    {
        $esSuperAhora = $usuario->estaHabilitado() && $usuario->rol->esSuperAdministrador();

        if (! $esSuperAhora) {
            return;
        }

        $sigueSiendoSuper = $estadoNuevo === EstadoRegistro::Habilitado
            && $idRolNuevo !== null
            && ($idRolNuevo === (int) $usuario->id_rol || (bool) Rol::whereKey($idRolNuevo)->value('es_super_rol'));

        if ($sigueSiendoSuper) {
            return;
        }

        $otros = Usuario::query()
            ->habilitado()
            ->whereKeyNot($usuario->id_usu)
            ->whereHas('rol', fn ($consulta) => $consulta->where('es_super_rol', true)->habilitado())
            ->exists();

        if (! $otros) {
            throw new RuntimeException('Es el único super administrador activo; el sistema no puede quedarse sin uno.');
        }
    }
}
