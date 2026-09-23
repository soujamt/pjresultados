<?php

use App\Enums\TipoImportacion;
use App\Models\Examen;
use App\Models\Importacion;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Evaluacion\ImportadorExamenes;
use Illuminate\Support\Facades\DB;
use Tests\Support\ArchivoLectora;

beforeEach(function () {
    $this->proceso = Proceso::factory()->create();
    $this->puesto = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro]);
});

function inscribir(Puesto $puesto, string $dni, string $nombres): Inscripcion
{
    return Inscripcion::factory()->create([
        'id_pro' => $puesto->id_pro,
        'id_pue' => $puesto->id_pue,
        'documento_ins' => $dni,
        'apellidos_nombres_ins' => $nombres,
    ]);
}

it('carga las hojas de la lectora cruzandolas por DNI con los inscritos', function () {
    $laura = inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');
    $ana = inscribir($this->puesto, '72345678', 'ACUÑA RIOS ANA');
    inscribir($this->puesto, '73456789', 'ALIAGA SILVA MILTON');

    $resultado = app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCÍA DÁVILA, LAURA', aciertos: 25, errores: 3, blancos: 1, dobles: 1),
        ArchivoLectora::hoja('72345678', 'ACUÑA RIOS, ANA', aciertos: 30),
    ]), 'lote-1.txt');

    $importacion = Importacion::sole();

    expect($resultado->aplicada)->toBeTrue()
        ->and($resultado->creados)->toBe(2)
        ->and($resultado->mensaje('examen(es)'))->toContain('Ya tienen examen 2 de 3 inscritos.')
        ->and($importacion->only('tipo_imp', 'archivo_imp', 'filas_imp', 'creados_imp', 'aplicada_imp'))->toBe([
            'tipo_imp' => TipoImportacion::Examenes,
            'archivo_imp' => 'lote-1.txt',
            'filas_imp' => 2,
            'creados_imp' => 2,
            'aplicada_imp' => true,
        ])
        ->and($laura->examen->only(
            'id_imp', 'puntaje_exa', 'aciertos_exa', 'errores_exa', 'blancos_exa', 'dobles_exa',
            'respuestas_exa', 'apellidos_nombres_exa', 'nombre_coincide_exa',
        ))->toBe([
            'id_imp' => $importacion->id_imp,
            'puntaje_exa' => '25.000',
            'aciertos_exa' => 25,
            'errores_exa' => 3,
            'blancos_exa' => 1,
            'dobles_exa' => 1,
            'respuestas_exa' => str_repeat('A', 25).'BBB-*',
            'apellidos_nombres_exa' => 'GARCÍA DÁVILA, LAURA',
            'nombre_coincide_exa' => true,
        ])
        ->and($ana->examen->apellidos_nombres_exa)->toBe('ACUÑA RIOS, ANA');
});

it('no carga nada si alguna hoja tiene observaciones', function () {
    inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');
    inscribir($this->puesto, '72345678', 'ALIAGA SILVA MILTON');
    inscribir($this->puesto, '73456789', 'SERRANO CASTILLO DORIS');

    $resultado = app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA LAURA', aciertos: 20, errores: 10),
        ArchivoLectora::hoja('79999999', 'REVILLA PAREDES JUAN', aciertos: 18, errores: 12),
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA LAURA', aciertos: 20, errores: 10),
        ArchivoLectora::hoja('7234567', 'ALIAGA SILVA MILTON', aciertos: 15, errores: 15),
        ArchivoLectora::hoja('73456789', 'SERRANO CASTILLO DORIS', aciertos: 12, errores: 18, nota: '14,00000'),
        ArchivoLectora::hoja('72345678', 'ALIAGA SILVA MILTON', aciertos: 12, errores: 10, blancos: 0),
    ]));

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores)->toBe([
            'Línea 3: el DNI 79999999 no está inscrito en el proceso (en la hoja: REVILLA PAREDES JUAN).',
            'Línea 4: el DNI 71234567 ya aparece en la línea 2.',
            'Línea 5: el DNI «7234567» está incompleto o mal marcado.',
            'Línea 6: la nota (14,00000) no es igual a los aciertos (12).',
            'Línea 7: aciertos, errores, blancos y dobles suman 22 preguntas y en las demás hojas suman 30.',
        ])
        ->and(Examen::count())->toBe(0)
        ->and(Importacion::sole()->only('aplicada_imp', 'errores_imp'))->toBe([
            'aplicada_imp' => false,
            'errores_imp' => $resultado->errores,
        ]);
});

it('rechaza conteos que no son numeros enteros', function () {
    inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');

    $resultado = app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo([
        '71234567;GARCIA DAVILA LAURA;20,00000;20;diez;0;0;A;',
    ]));

    expect($resultado->errores)->toBe(['Línea 2: «diez» en Errores no es un número entero.']);
});

it('actualiza por DNI al volver a cargar y cuenta las hojas sin cambios', function () {
    $laura = inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');
    inscribir($this->puesto, '72345678', 'ALIAGA SILVA MILTON');
    inscribir($this->puesto, '73456789', 'SERRANO CASTILLO DORIS');
    $importador = app(ImportadorExamenes::class);

    $importador->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA LAURA', aciertos: 20, errores: 10),
        ArchivoLectora::hoja('72345678', 'ALIAGA SILVA MILTON', aciertos: 15, errores: 15),
    ]));

    $resultado = $importador->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA LAURA', aciertos: 22, errores: 8),
        ArchivoLectora::hoja('72345678', 'ALIAGA SILVA MILTON', aciertos: 15, errores: 15),
        ArchivoLectora::hoja('73456789', 'SERRANO CASTILLO DORIS', aciertos: 9, errores: 21),
    ]));

    expect([$resultado->creados, $resultado->actualizados, $resultado->sinCambios])->toBe([1, 1, 1])
        ->and(Examen::count())->toBe(3)
        ->and($laura->examen->aciertos_exa)->toBe(22);
});

it('carga la hoja con otro nombre pero la marca para revisarla', function () {
    $laura = inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');

    $resultado = app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'SERRANO CASTILLO, DORIS', aciertos: 20, errores: 10),
    ]));

    expect($resultado->aplicada)->toBeTrue()
        ->and($resultado->nota)->toContain('1 hoja(s) traen un nombre distinto al del padrón')
        ->and($laura->examen->nombre_coincide_exa)->toBeFalse();
});

it('lee el archivo aunque ya venga en UTF-8', function () {
    $ana = inscribir($this->puesto, '72345678', 'ACUÑA RIOS ANA');

    app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('72345678', 'ACUÑA RIOS, ANA', aciertos: 30),
    ], codificacion: 'UTF-8'));

    expect($ana->examen->apellidos_nombres_exa)->toBe('ACUÑA RIOS, ANA');
});

it('exige la cabecera de la lectora', function () {
    inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');

    app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo(
        ['71234567;GARCIA DAVILA LAURA;20,00000;20'],
        'NRO DE DNI;APELLIDOS Y NOMBRES;Nota 30;Aciertos',
    ));
})->throws(RuntimeException::class, 'faltan las columnas «Errores», «Blancos», «Dobles»');

it('exige que el proceso tenga inscritos', function () {
    app(ImportadorExamenes::class)->importar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA LAURA', aciertos: 30),
    ]));
})->throws(RuntimeException::class, 'no tiene inscritos');

it('carga las 700 hojas de un proceso completo con un numero fijo de consultas', function () {
    Inscripcion::factory()
        ->count(700)
        ->sequence(fn ($secuencia) => ['documento_ins' => sprintf('7%07d', $secuencia->index)])
        ->create(['id_pro' => $this->proceso->id_pro, 'id_pue' => $this->puesto->id_pue]);

    $archivo = ArchivoLectora::archivo(array_map(
        fn (int $indice): string => ArchivoLectora::hoja(sprintf('7%07d', $indice), '', aciertos: $indice % 31, errores: 30 - $indice % 31),
        range(0, 699),
    ));

    DB::enableQueryLog();
    $resultado = app(ImportadorExamenes::class)->importar($this->proceso, $archivo);

    expect($resultado->creados)->toBe(700)
        ->and(count(DB::getQueryLog()))->toBeLessThanOrEqual(6)
        ->and(Examen::count())->toBe(700);
});

it('muestra la vista previa sin guardar nada y cuenta a quienes quedaran sin examen', function () {
    inscribir($this->puesto, '71234567', 'GARCIA DAVILA LAURA');
    inscribir($this->puesto, '72345678', 'ALIAGA SILVA MILTON');
    inscribir($this->puesto, '73456789', 'SERRANO CASTILLO DORIS');
    $yaCargada = inscribir($this->puesto, '74567890', 'REVILLA PAREDES JUAN');
    Examen::factory()->create(['id_ins' => $yaCargada->id_ins]);

    $analisis = app(ImportadorExamenes::class)->analizar($this->proceso, ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA LAURA', aciertos: 18, errores: 12),
        ArchivoLectora::hoja('72345678', 'ALIAGA SILVA MILTON', aciertos: 15, errores: 15, nota: '16,00000'),
    ]));

    expect($analisis->puedeImportarse())->toBeFalse()
        ->and($analisis->errores)->toHaveCount(1)
        ->and($analisis->faltantes)->toBe([
            ['documento' => '73456789', 'nombres' => 'SERRANO CASTILLO DORIS', 'puesto' => $this->puesto->denominacion()],
        ])
        ->and($analisis->conExamen())->toBe(3)
        ->and(Examen::count())->toBe(1)
        ->and(Importacion::count())->toBe(0);
});
