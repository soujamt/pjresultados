<?php

use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Evaluacion\ImportadorExamenes;

beforeEach(function () {
    $this->proceso = Proceso::factory()->create();
    $this->puesto = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro]);
    $this->salida = tempnam(sys_get_temp_dir(), 'ficticio').'.txt';
});

it('genera una hoja por inscrito menos los faltantes, lista para importar', function () {
    Inscripcion::factory()->count(5)->create(['id_pro' => $this->proceso->id_pro, 'id_pue' => $this->puesto->id_pue]);

    $this->artisan('pj:generar-examenes-ficticios', [
        '--faltantes' => 2,
        '--proceso' => $this->proceso->codigo_pro,
        '--salida' => $this->salida,
        '--semilla' => 7,
    ])->assertSuccessful();

    $analisis = app(ImportadorExamenes::class)->analizar($this->proceso, $this->salida);

    expect($analisis->errores)->toBe([])
        ->and($analisis->filas)->toBe(3)
        ->and($analisis->preguntas)->toBe(30)
        ->and($analisis->faltantes)->toHaveCount(2);

    app(ImportadorExamenes::class)->importar($this->proceso, $this->salida);

    expect(Examen::count())->toBe(3)
        ->and(Examen::all()->every(fn (Examen $examen): bool => $examen->totalDePreguntas() === 30
            && $examen->aciertos_exa <= 30
            && (float) $examen->puntaje_exa === (float) $examen->aciertos_exa))->toBeTrue();
});

it('no permite que falten todos los inscritos', function () {
    Inscripcion::factory()->count(2)->create(['id_pro' => $this->proceso->id_pro, 'id_pue' => $this->puesto->id_pue]);

    $this->artisan('pj:generar-examenes-ficticios', ['--faltantes' => 2, '--salida' => $this->salida])
        ->expectsOutputToContain('entre 0 y 1')
        ->assertFailed();
});

it('no se ejecuta en produccion', function () {
    $this->app['env'] = 'production';

    $this->artisan('pj:generar-examenes-ficticios', ['--salida' => $this->salida])
        ->expectsOutputToContain('no se ejecuta en producción')
        ->assertFailed();

    expect($this->salida)->not->toBeFile();
});
