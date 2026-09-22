<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use App\Models\Puesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Puesto>
 */
class PuestoFactory extends Factory
{
    protected $model = Puesto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pro' => Proceso::factory(),
            'codigo_pue' => str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'nombre_pue' => fake()->randomElement(['ASISTENTE JUDICIAL', 'AUXILIAR JUDICIAL', 'TECNICO JUDICIAL', 'SECRETARIO JUDICIAL']),
            'estado_pue' => EstadoRegistro::Habilitado,
        ];
    }

    public function deshabilitado(): static
    {
        return $this->state(fn () => ['estado_pue' => EstadoRegistro::Deshabilitado]);
    }
}
