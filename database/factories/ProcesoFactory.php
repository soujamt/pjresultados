<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proceso>
 */
class ProcesoFactory extends Factory
{
    protected $model = Proceso::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $codigo = str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT).'-2026-UE-UCAYALI';

        return [
            'codigo_pro' => $codigo,
            'nombre_pro' => "Proceso de Selección de Personal N° {$codigo}",
            'entidad_pro' => 'Corte Superior de Justicia de Ucayali',
            'regimen_pro' => 'Decreto Legislativo N° 728, a plazo indeterminado',
            'fecha_evaluacion_pro' => '2026-09-26',
            'hora_evaluacion_pro' => '8:00 am. a 9:30 am.',
            'lugar_evaluacion_pro' => 'Pucallpa',
            'estado_pro' => EstadoRegistro::Habilitado,
        ];
    }
}
