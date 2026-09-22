<?php

namespace App\Services\Auth;

use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve que puede hacer un usuario.
 *
 * Los accesos se cachean por rol —no por usuario—, porque dos usuarios del
 * mismo rol tienen exactamente los mismos permisos. Lo que se guarda son
 * strings y booleanos, no modelos: un Eloquent serializado no sobrevive al
 * `unserialize()` del store de cache sin la clase cargada de antemano.
 */
class AccesoService
{
    private const PREFIJO_CACHE = 'pj:permisos:rol:';

    /**
     * Accesos del rol tal como estan guardados. Un rol deshabilitado o
     * inexistente no concede nada.
     *
     * @return array{super: bool, permisos: list<string>}
     */
    public function accesosDelRol(int $idRol): array
    {
        return Cache::rememberForever(self::PREFIJO_CACHE.$idRol, function () use ($idRol): array {
            $rol = Rol::query()->habilitado()->whereKey($idRol)->first(['id_rol', 'permisos_rol', 'es_super_rol']);

            return [
                'super' => (bool) $rol?->es_super_rol,
                'permisos' => $rol->permisos_rol ?? [],
            ];
        });
    }

    public function esSuperAdministrador(?Usuario $usuario): bool
    {
        if ($usuario === null || ! $usuario->estaHabilitado()) {
            return false;
        }

        return $this->accesosDelRol($usuario->id_rol)['super'];
    }

    public function puede(?Usuario $usuario, Permiso $permiso): bool
    {
        if ($usuario === null || ! $usuario->estaHabilitado()) {
            return false;
        }

        $accesos = $this->accesosDelRol($usuario->id_rol);

        return $accesos['super'] || in_array($permiso->value, $accesos['permisos'], true);
    }

    /**
     * Permisos efectivos del usuario, resueltos a casos del enum. Se descartan
     * los valores que quedaron huerfanos al retirar un permiso del codigo.
     *
     * @return list<Permiso>
     */
    public function permisos(?Usuario $usuario): array
    {
        if ($usuario === null || ! $usuario->estaHabilitado()) {
            return [];
        }

        $accesos = $this->accesosDelRol($usuario->id_rol);

        if ($accesos['super']) {
            return Permiso::cases();
        }

        return array_values(array_filter(array_map(Permiso::tryFrom(...), $accesos['permisos'])));
    }

    /**
     * Si el usuario tiene alguno de los permisos indicados. Sirve para decidir
     * si se pinta un grupo entero del menu lateral.
     *
     * @param  list<Permiso>  $permisos
     */
    public function puedeAlguno(?Usuario $usuario, array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if ($this->puede($usuario, $permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Se llama al cambiar los permisos de un rol. Sin argumentos limpia todos:
     * es lo que hace falta cuando se retira un permiso del enum.
     */
    public function olvidar(?int $idRol = null): void
    {
        if ($idRol !== null) {
            Cache::forget(self::PREFIJO_CACHE.$idRol);

            return;
        }

        foreach (Rol::withTrashed()->pluck('id_rol') as $id) {
            Cache::forget(self::PREFIJO_CACHE.$id);
        }
    }
}
