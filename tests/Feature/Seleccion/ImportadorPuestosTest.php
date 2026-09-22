<?php

use App\Enums\TipoImportacion;
use App\Models\Importacion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Usuario;
use App\Services\Seleccion\ImportadorPuestos;
use Tests\Support\Anexo06A;
use Tests\Support\ConstructorXlsx;

it('agrupa los puestos del anexo por codigo', function () {
    $proceso = Proceso::factory()->create();
    $archivo = Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II'),
        Anexo06A::fila(2, 'SERRANO CASTILLO DORIS', '00312', 'ANALISTA II'),
        Anexo06A::fila(3, 'ALIAGA SILVA MILTON', '00340-1', 'ASISTENTE JUDICIAL'),
        Anexo06A::fila(4, 'ALVARADO DEL AGUILA ERIKA', '00340-2', 'ASISTENTE JUDICIAL'),
    ]);

    $resultado = app(ImportadorPuestos::class)->importar($proceso, $archivo);

    expect($resultado->aplicada)->toBeTrue()
        ->and($resultado->filas)->toBe(4)
        ->and($resultado->creados)->toBe(3)
        ->and(Puesto::delProceso($proceso->id_pro)->orderBy('codigo_pue')->pluck('nombre_pue', 'codigo_pue')->all())
        ->toBe([
            '00312' => 'ANALISTA II',
            '00340-1' => 'ASISTENTE JUDICIAL',
            '00340-2' => 'ASISTENTE JUDICIAL',
        ]);
});

it('unifica el guion bajo del codigo y recupera los ceros perdidos', function () {
    $proceso = Proceso::factory()->create();
    $archivo = Anexo06A::archivo([
        [1, 'PEREZ LUIS', '00306-1', 'ESPECIALISTA JUDICIAL DE JUZGADO (00306_1)'],
        [2, 'RUIZ ANA', '312', 'ANALISTA II (00312)'],
    ]);

    app(ImportadorPuestos::class)->importar($proceso, $archivo);

    expect(Puesto::delProceso($proceso->id_pro)->orderBy('codigo_pue')->pluck('codigo_pue')->all())
        ->toBe(['00306-1', '00312']);
});

it('no duplica al volver a importar y actualiza el nombre que cambio', function () {
    $proceso = Proceso::factory()->create();
    Puesto::factory()->create(['id_pro' => $proceso->id_pro, 'codigo_pue' => '00312', 'nombre_pue' => 'ANALISTA']);

    $archivo = Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II'),
        Anexo06A::fila(2, 'ALIAGA SILVA MILTON', '00301', 'ASISTENTE ADMINISTRATIVO II'),
    ]);

    $primera = app(ImportadorPuestos::class)->importar($proceso, $archivo);
    $segunda = app(ImportadorPuestos::class)->importar($proceso, $archivo);

    expect($primera->creados)->toBe(1)
        ->and($primera->actualizados)->toBe(1)
        ->and($segunda->sinCambios)->toBe(2)
        ->and(Puesto::delProceso($proceso->id_pro)->count())->toBe(2)
        ->and(Puesto::where('codigo_pue', '00312')->value('nombre_pue'))->toBe('ANALISTA II');
});

it('restaura un puesto eliminado en vez de crear otro', function () {
    $proceso = Proceso::factory()->create();
    $puesto = Puesto::factory()->create(['id_pro' => $proceso->id_pro, 'codigo_pue' => '00312', 'nombre_pue' => 'ANALISTA II']);
    $puesto->delete();

    app(ImportadorPuestos::class)->importar($proceso, Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II'),
    ]));

    expect($puesto->fresh()->trashed())->toBeFalse()
        ->and(Puesto::withTrashed()->delProceso($proceso->id_pro)->count())->toBe(1);
});

it('rechaza el archivo entero si un codigo aparece con dos nombres', function () {
    $proceso = Proceso::factory()->create();
    $archivo = Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II'),
        Anexo06A::fila(2, 'ALIAGA SILVA MILTON', '00301', 'ASISTENTE ADMINISTRATIVO II'),
        Anexo06A::fila(3, 'SERRANO CASTILLO DORIS', '00312', 'REVISOR'),
    ]);

    $resultado = app(ImportadorPuestos::class)->importar($proceso, $archivo);

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores)->toHaveCount(1)
        ->and($resultado->errores[0])->toContain('Fila 15')->toContain('00312')
        ->and(Puesto::count())->toBe(0);
});

it('registra cada carga en la bitacora con su usuario', function () {
    $proceso = Proceso::factory()->create();
    $usuario = Usuario::factory()->create();

    app(ImportadorPuestos::class)->importar(
        $proceso,
        Anexo06A::archivo([Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II')]),
        'Anexo 06-A.xlsx',
        $usuario,
    );

    $importacion = Importacion::sole();

    expect($importacion->tipo_imp)->toBe(TipoImportacion::Puestos)
        ->and($importacion->archivo_imp)->toBe('Anexo 06-A.xlsx')
        ->and($importacion->id_usu)->toBe($usuario->id_usu)
        ->and($importacion->aplicada_imp)->toBeTrue()
        ->and($importacion->hash_imp)->toHaveLength(64);
});

it('avisa cuando el archivo no tiene la cabecera del anexo', function () {
    $archivo = (new ConstructorXlsx)->hoja('Hoja1', [['NOMBRE', 'CARGO'], ['LUIS', 'JUEZ']])->escribir();

    app(ImportadorPuestos::class)->importar(Proceso::factory()->create(), $archivo);
})->throws(RuntimeException::class, 'CÓDIGO DE PUESTO');

it('avisa cuando el archivo no es un excel', function () {
    $archivo = tempnam(sys_get_temp_dir(), 'txt');
    file_put_contents($archivo, 'no soy un excel');

    app(ImportadorPuestos::class)->importar(Proceso::factory()->create(), $archivo);
})->throws(RuntimeException::class, 'no es un Excel');
