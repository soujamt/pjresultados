<?php

namespace Database\Factories;

use App\Models\Descalificacion;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Descalificacion>
 */
class DescalificacionFactory extends Factory
{
    protected $model = Descalificacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_ins' => Inscripcion::factory(),
            'id_pro' => fn (array $atributos) => Inscripcion::query()->whereKey($atributos['id_ins'])->value('id_pro'),
            'motivo_des' => Descalificacion::MOTIVOS[0],
        ];
    }
}
