<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_rol' => 'Rol '.fake()->unique()->numberBetween(1, 99999),
            'descripcion_rol' => fake()->sentence(),
            'permisos_rol' => [],
            'es_super_rol' => false,
            'estado_rol' => EstadoRegistro::Habilitado,
        ];
    }

    public function superAdministrador(): static
    {
        return $this->state(fn () => [
            'nombre_rol' => 'Super Administrador',
            'permisos_rol' => [],
            'es_super_rol' => true,
        ]);
    }

    /**
     * @param  list<Permiso>  $permisos
     */
    public function con(array $permisos): static
    {
        return $this->state(fn () => [
            'permisos_rol' => array_column($permisos, 'value'),
        ]);
    }

    public function deshabilitado(): static
    {
        return $this->state(fn () => ['estado_rol' => EstadoRegistro::Deshabilitado]);
    }
}
