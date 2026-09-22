<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    /**
     * Por ahora el unico rol es el super administrador. Se le guarda la lista
     * completa de permisos por claridad, aunque su acceso total sale de la
     * marca `es_super_rol` y no de la lista.
     */
    public function run(): void
    {
        Rol::updateOrCreate(
            ['nombre_rol' => 'Super Administrador'],
            [
                'descripcion_rol' => 'Acceso total al sistema.',
                'permisos_rol' => Permiso::valores(),
                'es_super_rol' => true,
                'estado_rol' => EstadoRegistro::Habilitado,
            ],
        );
    }
}
