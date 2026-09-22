<?php

use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Seleccion\ImportadorInscripciones;
use Tests\Support\Anexo06A;

beforeEach(function () {
    $this->proceso = Proceso::factory()->create();
    $this->puesto = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00340-1']);
});

it('exige la columna del DNI', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00340-1', 'ASISTENTE JUDICIAL'),
    ]);

    app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);
})->throws(RuntimeException::class, 'DNI');

it('inscribe a los postulantes en su puesto', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'garcia davila laura', '00340-1'),
        Anexo06A::filaConDni(2, '1234567', 'SERRANO CASTILLO DORIS', '00340_1'),
    ], Anexo06A::cabeceraConDni());

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);

    expect($resultado->aplicada)->toBeTrue()
        ->and($resultado->creados)->toBe(2)
        ->and(Inscripcion::orderBy('numero_ins')->get(['documento_ins', 'apellidos_nombres_ins', 'id_pue'])->toArray())
        ->toBe([
            ['documento_ins' => '71234567', 'apellidos_nombres_ins' => 'GARCIA DAVILA LAURA', 'id_pue' => $this->puesto->id_pue],
            ['documento_ins' => '01234567', 'apellidos_nombres_ins' => 'SERRANO CASTILLO DORIS', 'id_pue' => $this->puesto->id_pue],
        ]);
});

it('actualiza por DNI al volver a importar', function () {
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00301']);
    $importador = app(ImportadorInscripciones::class);

    $importador->importar($this->proceso, Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GARCIA DAVILA LAURA', '00340-1'),
    ], Anexo06A::cabeceraConDni()));

    $resultado = $importador->importar($this->proceso, Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GARCIA DAVILA LAURA', '00301', 'ASISTENTE ADMINISTRATIVO II'),
        Anexo06A::filaConDni(2, '72345678', 'ALIAGA SILVA MILTON', '00340-1'),
    ], Anexo06A::cabeceraConDni()));

    expect($resultado->creados)->toBe(1)
        ->and($resultado->actualizados)->toBe(1)
        ->and(Inscripcion::count())->toBe(2)
        ->and(Inscripcion::where('documento_ins', '71234567')->value('id_pue'))->toBe($otro->id_pue);
});

it('no guarda nada si alguna fila tiene observaciones', function () {
    $archivo = Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GARCIA DAVILA LAURA', '00340-1'),
        Anexo06A::filaConDni(2, '71234567', 'SERRANO CASTILLO DORIS', '00340-1'),
        Anexo06A::filaConDni(3, '72345678', 'ALIAGA SILVA MILTON', '99999'),
        Anexo06A::filaConDni(4, '12AB', 'REVILLA PAREDES JUAN', '00340-1'),
    ], Anexo06A::cabeceraConDni());

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, $archivo);

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores)->toHaveCount(3)
        ->and($resultado->errores[0])->toContain('Fila 14')->toContain('fila 13')
        ->and($resultado->errores[1])->toContain('99999')
        ->and($resultado->errores[2])->toContain('12AB')
        ->and(Inscripcion::count())->toBe(0);
});

it('no inscribe en un puesto deshabilitado', function () {
    $this->puesto->alternarEstado();

    $resultado = app(ImportadorInscripciones::class)->importar($this->proceso, Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GARCIA DAVILA LAURA', '00340-1'),
    ], Anexo06A::cabeceraConDni()));

    expect($resultado->aplicada)->toBeFalse()
        ->and($resultado->errores[0])->toContain('deshabilitado');
});

it('pide cargar los puestos antes que las inscripciones', function () {
    app(ImportadorInscripciones::class)->importar(Proceso::factory()->create(), Anexo06A::archivo([
        Anexo06A::filaConDni(1, '71234567', 'GARCIA DAVILA LAURA', '00340-1'),
    ], Anexo06A::cabeceraConDni()));
})->throws(RuntimeException::class, 'no tiene puestos');
