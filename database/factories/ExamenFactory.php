<?php

namespace Database\Factories;

use App\Models\Examen;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Hoja de 30 preguntas sin blancos ni dobles: lo que no es acierto es error.
 *
 * @extends Factory<Examen>
 */
class ExamenFactory extends Factory
{
    protected $model = Examen::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aciertos = fake()->numberBetween(0, 30);

        return [
            'id_ins' => Inscripcion::factory(),
            'id_pro' => fn (array $atributos) => Inscripcion::query()->whereKey($atributos['id_ins'])->value('id_pro'),
            'apellidos_nombres_exa' => fn (array $atributos) => Inscripcion::query()->whereKey($atributos['id_ins'])->value('apellidos_nombres_ins'),
            'nombre_coincide_exa' => true,
            'puntaje_exa' => $aciertos,
            'aciertos_exa' => $aciertos,
            'errores_exa' => 30 - $aciertos,
            'blancos_exa' => 0,
            'dobles_exa' => 0,
            'respuestas_exa' => str_repeat('A', $aciertos).str_repeat('B', 30 - $aciertos),
        ];
    }

    /**
     * La lectora imprimio en la hoja un nombre distinto al del padron.
     */
    public function conOtroNombre(string $nombre = 'OTRO NOMBRE DISTINTO'): static
    {
        return $this->state([
            'apellidos_nombres_exa' => $nombre,
            'nombre_coincide_exa' => false,
        ]);
    }
}
