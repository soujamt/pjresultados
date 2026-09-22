<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unidad>
 */
class UnidadFactory extends Factory
{
    protected $model = Unidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_uni' => fake()->randomElement([
                'MÓDULO PENAL CENTRAL',
                'SALA CIVIL - CALLERIA',
                'JUZGADO MIXTO DE ATALAYA',
                'UNIDAD DE SERVICIOS JUDICIALES',
                'JUZGADO DE PAZ LETRADO DE YARINACOCHA',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'estado_uni' => EstadoRegistro::Habilitado,
        ];
    }
}
