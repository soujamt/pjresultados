<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Cuenta inicial del super administrador. El correo y la contrasena se
     * pueden fijar con ADMIN_USUARIO y ADMIN_CLAVE para no dejar una clave
     * conocida en produccion.
     */
    public function run(): void
    {
        $idRol = Rol::where('es_super_rol', true)->value('id_rol');

        Usuario::firstOrCreate(
            ['usuario_usu' => config('app.administrador.usuario')],
            [
                'id_rol' => $idRol,
                'nombre_usu' => 'Administrador del sistema',
                'clave_usu' => config('app.administrador.clave'),
                'estado_usu' => EstadoRegistro::Habilitado,
            ],
        );
    }
}
