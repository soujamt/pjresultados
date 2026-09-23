<?php

use App\Enums\CondicionResultado;
use App\Models\Descalificacion;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Evaluacion\FilaDeResultado;
use App\Services\Evaluacion\ResultadoService;

beforeEach(function () {
    $this->proceso = Proceso::factory()->create();
    $this->puesto = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00309']);
});

/**
 * Inscribe al postulante en el puesto y, si se indican aciertos, le carga su hoja.
 */
function postulante(Puesto $puesto, string $nombres, ?int $aciertos = null): Inscripcion
{
    $inscripcion = Inscripcion::factory()->create([
        'id_pro' => $puesto->id_pro,
        'id_pue' => $puesto->id_pue,
        'apellidos_nombres_ins' => $nombres,
    ]);

    if ($aciertos !== null) {
        Examen::factory()->create([
            'id_ins' => $inscripcion->id_ins,
            'puntaje_exa' => $aciertos,
            'aciertos_exa' => $aciertos,
            'errores_exa' => 30 - $aciertos,
        ]);
    }

    return $inscripcion;
}

/**
 * @return list<FilaDeResultado>
 */
function filasDelPuesto(Proceso $proceso): array
{
    return app(ResultadoService::class)->porPuesto($proceso)[0]->filas;
}

it('ordena de mayor a menor puntaje y los empates en orden alfabetico, con la Ñ despues de la N', function () {
    postulante($this->puesto, 'OCAMPO DIAZ ROSA', 24);
    postulante($this->puesto, 'ÑAUPARI PONCE MARIO', 24);
    postulante($this->puesto, 'NUÑEZ ROJAS LUIS', 24);
    postulante($this->puesto, 'ÁVILA RUIZ ANA', 24);
    postulante($this->puesto, 'SALAZAR MENDEZ KIARA', 26);

    $filas = filasDelPuesto($this->proceso);

    expect(array_map(fn (FilaDeResultado $fila): array => [$fila->numero, $fila->inscripcion->apellidos_nombres_ins], $filas))->toBe([
        [1, 'SALAZAR MENDEZ KIARA'],
        [2, 'ÁVILA RUIZ ANA'],
        [3, 'NUÑEZ ROJAS LUIS'],
        [4, 'ÑAUPARI PONCE MARIO'],
        [5, 'OCAMPO DIAZ ROSA'],
    ])
        ->and([$filas[0]->nota, $filas[0]->notaParcialTexto(), $filas[0]->puntajeTexto()])->toBe([26, '17.33', '5.20']);
});

it('es apto desde 20 aciertos con el puntaje minimo de 3,9', function () {
    postulante($this->puesto, 'GALVEZ DORADO LUCIA', 20);
    postulante($this->puesto, 'ALARCON SIERRA MATEO', 19);

    [$apto, $noApto] = filasDelPuesto($this->proceso);

    expect([$apto->puntajeTexto(), $apto->condicion, $apto->observacion])->toBe(['4.00', CondicionResultado::Apto, null])
        ->and([$noApto->puntajeTexto(), $noApto->condicion, $noApto->observacion])
        ->toBe(['3.80', CondicionResultado::NoApto, ResultadoService::NO_ALCANZO]);
});

it('respeta el puntaje minimo del proceso', function () {
    $this->proceso->update(['puntaje_minimo_pro' => 4.2]);
    postulante($this->puesto, 'GALVEZ DORADO LUCIA', 21);
    postulante($this->puesto, 'ALARCON SIERRA MATEO', 20);

    expect(array_map(fn (FilaDeResultado $fila): CondicionResultado => $fila->condicion, filasDelPuesto($this->proceso)))
        ->toBe([CondicionResultado::Apto, CondicionResultado::NoApto]);
});

it('pone en cero a quien no se presento y a quien fue descalificado aunque tenga hoja', function () {
    postulante($this->puesto, 'ZAPATA RIOS LUIS', 1);
    postulante($this->puesto, 'VALDEZ SOLANO LUZ');
    $descalificada = postulante($this->puesto, 'ABAD TORRES ELENA', 28);
    app(ResultadoService::class)->descalificar($descalificada, '  descalificado/a - se retiró de la sala  ');

    $filas = filasDelPuesto($this->proceso);

    expect(array_map(fn (FilaDeResultado $fila): array => [
        $fila->inscripcion->apellidos_nombres_ins, $fila->nota, $fila->puntajeTexto(), $fila->condicion, $fila->observacion,
    ], $filas))->toBe([
        ['ZAPATA RIOS LUIS', 1, '0.20', CondicionResultado::NoApto, ResultadoService::NO_ALCANZO],
        ['ABAD TORRES ELENA', 0, '0.00', CondicionResultado::NoApto, 'DESCALIFICADO/A - SE RETIRÓ DE LA SALA'],
        ['VALDEZ SOLANO LUZ', 0, '0.00', CondicionResultado::NoApto, ResultadoService::NO_SE_PRESENTO],
    ]);
});

it('vuelve a calificar con su hoja a quien se le quita la descalificacion', function () {
    $inscripcion = postulante($this->puesto, 'GALVEZ DORADO LUCIA', 25);
    $servicio = app(ResultadoService::class);
    $servicio->descalificar($inscripcion, Descalificacion::MOTIVOS[0]);

    $servicio->quitarDescalificacion($inscripcion);

    expect(Descalificacion::count())->toBe(0)
        ->and(filasDelPuesto($this->proceso)[0]->puntajeTexto())->toBe('5.00');
});

it('agrupa por puesto en orden de codigo y omite los puestos sin inscritos', function () {
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00301']);
    Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00300']);
    postulante($this->puesto, 'GALVEZ DORADO LUCIA', 25);
    postulante($otro, 'ALARCON SIERRA MATEO', 20);

    $codigos = array_map(fn ($delPuesto) => $delPuesto->puesto->codigo_pue, app(ResultadoService::class)->porPuesto($this->proceso));

    expect($codigos)->toBe(['00301', '00309']);
});
