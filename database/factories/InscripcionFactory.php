<?php

namespace Database\Factories;

use App\Models\Inscripcion;
use App\Models\Puesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscripcion>
 */
class InscripcionFactory extends Factory
{
    protected $model = Inscripcion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pue' => Puesto::factory(),
            'id_pro' => fn (array $atributos) => Puesto::query()->whereKey($atributos['id_pue'])->value('id_pro'),
            'numero_ins' => fake()->numberBetween(1, 999),
            'documento_ins' => fake()->unique()->numerify('########'),
            'apellidos_nombres_ins' => mb_strtoupper(fake()->lastName().' '.fake()->lastName().' '.fake()->firstName()),
        ];
    }
}
