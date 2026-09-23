<?php

use App\Enums\Permiso;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\Unidad;
use App\Models\Usuario;
use App\Services\Evaluacion\ExamenService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Support\Anexo06A;
use Tests\Support\ArchivoLectora;

dataset('pantallas', [
    'inicio' => ['inicio', null],
    'procesos' => ['seleccion.procesos', Permiso::ProcesosVer],
    'unidades' => ['seleccion.unidades', Permiso::UnidadesVer],
    'puestos' => ['seleccion.puestos', Permiso::PuestosVer],
    'inscripciones' => ['seleccion.inscripciones', Permiso::InscripcionesVer],
    'exámenes' => ['evaluacion.examenes', Permiso::ExamenesVer],
    'resultados' => ['evaluacion.resultados', Permiso::ResultadosVer],
    'usuarios' => ['seguridad.usuarios', Permiso::UsuariosVer],
    'roles' => ['seguridad.roles', Permiso::RolesVer],
]);

it('abre cada pantalla para el super administrador', function (string $ruta) {
    Inscripcion::factory()->create();

    $this->actingAs(Usuario::factory()->superAdministrador()->create())
        ->get(route($ruta))
        ->assertOk();
})->with('pantallas');

it('niega la pantalla a quien no tiene el permiso', function (string $ruta, ?Permiso $permiso) {
    $this->actingAs(Usuario::factory()->create(['id_rol' => Rol::factory()->con([])]))
        ->get(route($ruta))
        ->assertStatus($permiso === null ? 200 : 403);
})->with('pantallas');

it('lista los puestos del proceso con sus inscritos', function () {
    $puesto = Puesto::factory()->create(['codigo_pue' => '00340-1', 'nombre_pue' => 'ASISTENTE JUDICIAL']);
    Inscripcion::factory()->count(2)->create(['id_pue' => $puesto->id_pue]);

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.puestos', ['codigoProceso' => $puesto->proceso->codigo_pro])
        ->assertSee('00340-1')
        ->assertSee('ASISTENTE JUDICIAL')
        ->assertViewHas('puestos', fn ($puestos) => $puestos->first()->inscripciones_count === 2);
});

it('importa los puestos subiendo el anexo desde la pantalla', function () {
    $proceso = Proceso::factory()->create();
    $archivo = UploadedFile::fake()->createWithContent('Anexo 06-A.xlsx', file_get_contents(Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II'),
        Anexo06A::fila(2, 'ALIAGA SILVA MILTON', '00301', 'ASISTENTE ADMINISTRATIVO II'),
    ])));

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.puestos', ['codigoProceso' => $proceso->codigo_pro])
        ->call('abrirImportacion')
        ->set('archivo', $archivo)
        ->call('importar')
        ->assertHasNoErrors()
        ->assertSee('ASISTENTE ADMINISTRATIVO II');

    expect(Puesto::delProceso($proceso->id_pro)->count())->toBe(2);
});

it('muestra por que no se pudieron importar las inscripciones', function () {
    $puesto = Puesto::factory()->create(['codigo_pue' => '00312']);
    $archivo = UploadedFile::fake()->createWithContent('Anexo 06-A.xlsx', file_get_contents(Anexo06A::archivo([
        Anexo06A::fila(1, 'GARCIA DAVILA LAURA', '00312', 'ANALISTA II'),
    ])));

    $componente = Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.inscripciones', ['codigoProceso' => $puesto->proceso->codigo_pro])
        ->set('archivo', $archivo)
        ->call('importar')
        ->assertHasErrors('archivo');

    expect($componente->errors()->first('archivo'))->toContain('columna del DNI')
        ->and(Inscripcion::count())->toBe(0);
});

it('crea un puesto normalizando su codigo', function () {
    $proceso = Proceso::factory()->create();

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.puestos', ['codigoProceso' => $proceso->codigo_pro])
        ->call('nuevo')
        ->set('form.codigo', '00306_1')
        ->set('form.nombre', 'especialista judicial de juzgado')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(Puesto::sole()->only('codigo_pue', 'nombre_pue', 'id_pro'))->toBe([
        'codigo_pue' => '00306-1',
        'nombre_pue' => 'ESPECIALISTA JUDICIAL DE JUZGADO',
        'id_pro' => $proceso->id_pro,
    ]);
});

it('no elimina un proceso que ya tiene puestos', function () {
    $puesto = Puesto::factory()->create();

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.procesos')
        ->call('eliminar', $puesto->id_pro);

    expect(Proceso::find($puesto->id_pro))->not->toBeNull();
});

it('agrupa los puestos y filtra las inscripciones por unidad de organizacion', function () {
    $proceso = Proceso::factory()->create();
    $sala = Unidad::factory()->create(['nombre_uni' => 'SALA CIVIL - CALLERIA']);
    $modulo = Unidad::factory()->create(['nombre_uni' => 'MÓDULO PENAL CENTRAL']);
    $deLaSala = Puesto::factory()->create(['id_pro' => $proceso->id_pro, 'id_uni' => $sala->id_uni, 'codigo_pue' => '00340-3']);
    $delModulo = Puesto::factory()->create(['id_pro' => $proceso->id_pro, 'id_uni' => $modulo->id_uni, 'codigo_pue' => '00335-2']);
    Inscripcion::factory()->create(['id_pue' => $deLaSala->id_pue, 'apellidos_nombres_ins' => 'GARCIA DAVILA LAURA']);
    Inscripcion::factory()->create(['id_pue' => $delModulo->id_pue, 'apellidos_nombres_ins' => 'ALIAGA SILVA MILTON']);
    $admin = Usuario::factory()->superAdministrador()->create();

    Livewire::actingAs($admin)
        ->test('pages::seleccion.puestos', ['codigoProceso' => $proceso->codigo_pro])
        ->assertViewHas('porUnidad', fn ($grupos) => $grupos->keys()->all() === ['MÓDULO PENAL CENTRAL', 'SALA CIVIL - CALLERIA'])
        ->set('filtroUnidad', (string) $sala->id_uni)
        ->assertSee('00340-3')
        ->assertDontSee('00335-2');

    Livewire::actingAs($admin)
        ->test('pages::seleccion.inscripciones', ['codigoProceso' => $proceso->codigo_pro])
        ->set('filtroUnidad', (string) $modulo->id_uni)
        ->assertSee('ALIAGA SILVA MILTON')
        ->assertDontSee('GARCIA DAVILA LAURA');

    Livewire::actingAs($admin)
        ->test('pages::seleccion.unidades', ['codigoProceso' => $proceso->codigo_pro])
        ->assertSee('SALA CIVIL - CALLERIA')
        ->assertViewHas('totalInscritos', 2);
});

it('no elimina una unidad que tiene puestos', function () {
    $puesto = Puesto::factory()->create(['id_uni' => Unidad::factory()]);

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.unidades')
        ->call('eliminar', $puesto->id_uni);

    expect(Unidad::find($puesto->id_uni))->not->toBeNull();
});

it('filtra las inscripciones por DNI o nombre', function () {
    $puesto = Puesto::factory()->create();
    Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'documento_ins' => '71234567', 'apellidos_nombres_ins' => 'GARCIA DAVILA LAURA']);
    Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'documento_ins' => '72345678', 'apellidos_nombres_ins' => 'ALIAGA SILVA MILTON']);

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.inscripciones', ['codigoProceso' => $puesto->proceso->codigo_pro])
        ->set('busqueda', 'garcia')
        ->assertSee('GARCIA DAVILA LAURA')
        ->assertDontSee('ALIAGA SILVA MILTON')
        ->set('busqueda', '7234')
        ->assertSee('ALIAGA SILVA MILTON')
        ->assertDontSee('GARCIA DAVILA LAURA');
});

it('muestra la vista previa del archivo y lo importa al confirmar', function () {
    $inscripcion = Inscripcion::factory()->create(['documento_ins' => '71234567', 'apellidos_nombres_ins' => 'GARCIA DAVILA LAURA']);
    Inscripcion::factory()->create(['id_pue' => $inscripcion->id_pue, 'documento_ins' => '72345678', 'apellidos_nombres_ins' => 'ALIAGA SILVA MILTON']);
    $archivo = UploadedFile::fake()->createWithContent('lote-1.txt', file_get_contents(ArchivoLectora::archivo([
        ArchivoLectora::hoja('71234567', 'GARCIA DAVILA, LAURA', aciertos: 27, errores: 3),
    ])));

    $pantalla = Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::evaluacion.examenes', ['codigoProceso' => $inscripcion->proceso->codigo_pro])
        ->call('abrirImportacion')
        ->set('archivo', $archivo)
        ->assertSet('vistaPrevia.importable', true)
        ->assertSet('vistaPrevia.total_faltantes', 1)
        ->assertSee(['Quedarán sin examen', 'ALIAGA SILVA MILTON', 'Importar 1 hoja(s)']);

    expect(Examen::count())->toBe(0);

    $pantalla->call('importar')
        ->assertHasNoErrors()
        ->assertSee('Ya tienen examen 1 de 2 inscritos.')
        ->assertViewHas('resumen', ['inscritos' => 2, 'con_examen' => 1, 'sin_examen' => 1, 'nombre_distinto' => 0]);

    expect($inscripcion->examen->aciertos_exa)->toBe(27);
});

it('no deja confirmar un archivo con observaciones', function () {
    $inscripcion = Inscripcion::factory()->create(['documento_ins' => '71234567']);
    $archivo = UploadedFile::fake()->createWithContent('lote-1.txt', file_get_contents(ArchivoLectora::archivo([
        ArchivoLectora::hoja('79999999', 'REVILLA PAREDES JUAN', aciertos: 20, errores: 10),
    ])));

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::evaluacion.examenes', ['codigoProceso' => $inscripcion->proceso->codigo_pro])
        ->set('archivo', $archivo)
        ->assertSet('vistaPrevia.importable', false)
        ->assertSee(['No se puede importar: 1 observación(es)', 'el DNI 79999999 no está inscrito']);
});

it('explica que el archivo subido no es el de la lectora', function () {
    $inscripcion = Inscripcion::factory()->create();

    $componente = Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::evaluacion.examenes', ['codigoProceso' => $inscripcion->proceso->codigo_pro])
        ->set('archivo', UploadedFile::fake()->createWithContent('lote-1.txt', file_get_contents(Anexo06A::archivo([]))))
        ->assertHasErrors('archivo')
        ->assertSet('vistaPrevia', null);

    expect($componente->errors()->first('archivo'))->toContain('no tiene la cabecera de la lectora óptica')
        ->and(Examen::count())->toBe(0);
});

it('filtra a los inscritos por la situacion de su hoja', function () {
    $puesto = Puesto::factory()->create();
    $conExamen = Examen::factory()->create([
        'id_ins' => Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'apellidos_nombres_ins' => 'GARCIA DAVILA LAURA']),
    ]);
    Examen::factory()->conOtroNombre('REVILLA PAREDES JUAN')->create([
        'id_ins' => Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'apellidos_nombres_ins' => 'ALIAGA SILVA MILTON']),
    ]);
    Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'apellidos_nombres_ins' => 'SERRANO CASTILLO DORIS']);

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::evaluacion.examenes', ['codigoProceso' => $puesto->proceso->codigo_pro])
        ->assertViewHas('resumen', ['inscritos' => 3, 'con_examen' => 2, 'sin_examen' => 1, 'nombre_distinto' => 1])
        ->call('filtrarPorEstado', ExamenService::SIN_EXAMEN)
        ->assertSee('SERRANO CASTILLO DORIS')
        ->assertDontSee('GARCIA DAVILA LAURA')
        ->call('filtrarPorEstado', ExamenService::NOMBRE_DISTINTO)
        ->assertSee('En la hoja: REVILLA PAREDES JUAN')
        ->assertDontSee('SERRANO CASTILLO DORIS')
        ->call('filtrarPorEstado', ExamenService::CON_EXAMEN)
        ->assertSee(['GARCIA DAVILA LAURA', 'ALIAGA SILVA MILTON'])
        ->assertDontSee('SERRANO CASTILLO DORIS')
        ->call('verHoja', $conExamen->id_exa)
        ->assertSee('Respuestas marcadas');
});

it('vacia los examenes del proceso solo con el permiso', function () {
    $examen = Examen::factory()->create();
    $codigo = $examen->inscripcion->proceso->codigo_pro;

    Livewire::actingAs(Usuario::factory()->create(['id_rol' => Rol::factory()->con([Permiso::ExamenesVer, Permiso::ExamenesImportar])]))
        ->test('pages::evaluacion.examenes', ['codigoProceso' => $codigo])
        ->call('vaciar')
        ->assertForbidden();

    expect(Examen::count())->toBe(1);

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::evaluacion.examenes', ['codigoProceso' => $codigo])
        ->call('vaciar');

    expect(Examen::count())->toBe(0);
});

it('no elimina la inscripcion de quien ya tiene su examen', function () {
    $examen = Examen::factory()->create();

    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seleccion.inscripciones', ['codigoProceso' => $examen->inscripcion->proceso->codigo_pro])
        ->call('eliminar', $examen->id_ins);

    expect(Inscripcion::find($examen->id_ins))->not->toBeNull();
});
