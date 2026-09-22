<?php

use App\Enums\Permiso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\Unidad;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Support\Anexo06A;

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
