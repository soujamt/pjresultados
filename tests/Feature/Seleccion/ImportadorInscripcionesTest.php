<?php

use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Seleccion\ImportadorInscripciones;
use Tests\Support\Anexo06A;

beforeEach(function () {
    $this->proceso = Proceso::factory()->create();
    $this->puesto = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00340-1']);
});

it('exige la columna del DNI', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::fila(1, 'GALVEZ DORADO LUCIA', '00340-1', 'ASISTENTE JUDICIAL'),
    ]);

    app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);
})->throws(RuntimeException::class, 'DNI');

it('inscribe a los postulantes en su puesto', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'galvez dorado lucia', '00340-1'),
        Anexo06A::filaConDni(2, '1234567', 'SEGURA CAMPOS DELIA', '00340_1'),
    ], Anexo06A::cabeceraConDni());

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);

    expect($resultado->aplicada)->toBeTrue()
        ->and($resultado->creados)->toBe(2)
        ->and(Inscripcion::orderBy('numero_ins')->get(['documento_ins', 'apellidos_nombres_ins', 'id_pue'])->toArray())
        ->toBe([
            ['documento_ins' => '71234567', 'apellidos_nombres_ins' => 'GALVEZ DORADO LUCIA', 'id_pue' => $this->puesto->id_pue],
            ['documento_ins' => '01234567', 'apellidos_nombres_ins' => 'SEGURA CAMPOS DELIA', 'id_pue' => $this->puesto->id_pue],
        ]);
});

it('actualiza por DNI al volver a importar', function () {
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00301']);
    $importador = app(ImportadorInscripciones::class);

    $importador->importar($this->proceso, Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GALVEZ DORADO LUCIA', '00340-1'),
    ], Anexo06A::cabeceraConDni()));

    $resultado = $importador->importar($this->proceso, Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GALVEZ DORADO LUCIA', '00301', 'ASISTENTE ADMINISTRATIVO II'),
        Anexo06A::filaConDni(2, '72345678', 'ALARCON SIERRA MATEO', '00340-1'),
    ], Anexo06A::cabeceraConDni()));

    expect($resultado->creados)->toBe(1)
        ->and($resultado->actualizados)->toBe(1)
        ->and(Inscripcion::count())->toBe(2)
        ->and(Inscripcion::where('documento_ins', '71234567')->value('id_pue'))->toBe($otro->id_pue);
});

it('no guarda nada si alguna fila tiene observaciones', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GALVEZ DORADO LUCIA', '00340-1'),
        Anexo06A::filaConDni(2, '71234567', 'SEGURA CAMPOS DELIA', '00340-1'),
        Anexo06A::filaConDni(3, '72345678', 'ALARCON SIERRA MATEO', '00340-1', 'REVISOR'),
        Anexo06A::filaConDni(4, '12AB', 'REVILLA PAREDES JUAN', '00340-1'),
    ], Anexo06A::cabeceraConDni());

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores)->toHaveCount(3)
        ->and($resultado->errores[0])->toContain('Fila 14')->toContain('fila 13')
        ->and($resultado->errores[1])->toContain('Fila 15')->toContain('REVISOR')
        ->and($resultado->errores[2])->toContain('12AB')
        ->and(Inscripcion::count())->toBe(0);
});

it('rechaza un puesto que no existe si el archivo no trae su nombre', function () {
    $archivo = Anexo06A::archivo([
        ['1', '71234567', 'GALVEZ DORADO LUCIA', '99999'],
    ], ['Nº', 'DNI', 'APELLIDOS Y NOMBRES', 'CÓDIGO DE PUESTO']);

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores[0])->toContain('el puesto 99999 no está registrado');
});

it('carga el listado completo con DNI y unidad en una sola importacion', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::filaCompleta(1, '71234567', 'GALVEZ DORADO LUCIA', '00340-1', 'ASISTENTE JUDICIAL', 'PRIMER JUZGADO DE TRABAJO - CALLERIA'),
        Anexo06A::filaCompleta(2, '72345678', 'ALARCON SIERRA MATEO', '00413', 'ASISTENTE EN SERVICIOS DE COMUNICACIONES', 'MÓDULO PENAL DE CONTAMANA'),
        Anexo06A::filaCompleta(3, '73456789', 'SEGURA CAMPOS DELIA', '00413', 'ASISTENTE EN SERVICIOS DE COMUNICACIONES', 'MÓDULO PENAL DE CONTAMANA'),
    ], Anexo06A::cabeceraCompleta());

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);

    $contamana = Unidad::where('nombre_uni', 'MÓDULO PENAL DE CONTAMANA')->sole();

    expect($resultado->aplicada)->toBeTrue()
        ->and($resultado->creados)->toBe(3)
        ->and($resultado->mensaje('inscrito(s)'))->toContain('Puestos: 1 nuevo(s) y 1 actualizado(s)')
        ->and($this->puesto->fresh()->unidad->nombre_uni)->toBe('PRIMER JUZGADO DE TRABAJO - CALLERIA')
        ->and($contamana->puestos()->sole()->codigo_pue)->toBe('00413')
        ->and($contamana->inscripciones()->count())->toBe(2);
});

it('no inscribe en un puesto deshabilitado', function () {
    $this->puesto->alternarEstado();

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GALVEZ DORADO LUCIA', '00340-1'),
    ], Anexo06A::cabeceraConDni()));

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores[0])->toContain('deshabilitado');
});

it('pide cargar los puestos si el archivo no trae sus nombres', function () {
    app(ImportadorInscripciones::class)->importar(Proceso::factory()->create(), Anexo06A::archivo([
        ['1', '71234567', 'GALVEZ DORADO LUCIA', '00340-1'],
    ], ['Nº', 'DNI', 'APELLIDOS Y NOMBRES', 'CÓDIGO DE PUESTO']));
})->throws(RuntimeException::class, 'no tiene puestos');
