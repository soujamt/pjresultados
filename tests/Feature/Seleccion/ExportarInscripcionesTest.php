<?php

use App\Enums\Permiso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\Unidad;
use App\Models\Usuario;
use App\Services\Excel\LectorXlsx;
use Illuminate\Testing\TestResponse;

/**
 * Filas del Excel descargado, indexadas por el titulo de su columna.
 *
 * @return list<array<string, string>>
 */
function filasDelExcel(TestResponse $respuesta): array
{
    $lector = new LectorXlsx($respuesta->baseResponse->getFile()->getPathname());

    return array_values(iterator_to_array($lector->filas('Postulantes')));
}

beforeEach(function () {
    $this->proceso = Proceso::factory()->create(['codigo_pro' => '002-2026-UE-UCAYALI']);
    $this->admin = Usuario::factory()->superAdministrador()->create();
});

it('descarga el DNI y los apellidos y nombres para la lectora', function () {
    $puesto = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro]);
    Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'numero_ins' => 2, 'documento_ins' => '71234567', 'apellidos_nombres_ins' => 'SERRANO CASTILLO DORIS']);
    Inscripcion::factory()->create(['id_pue' => $puesto->id_pue, 'numero_ins' => 1, 'documento_ins' => '01234567', 'apellidos_nombres_ins' => 'BRICEÑO PAIMA LESLIE']);

    $respuesta = $this->actingAs($this->admin)
        ->get(route('seleccion.inscripciones.excel', ['proceso' => '002-2026-UE-UCAYALI']))
        ->assertOk()
        ->assertDownload('postulantes-002-2026-ue-ucayali.xlsx');

    expect(filasDelExcel($respuesta))->toBe([
        ['DNI' => '01234567', 'APELLIDOS Y NOMBRES' => 'BRICEÑO PAIMA LESLIE'],
        ['DNI' => '71234567', 'APELLIDOS Y NOMBRES' => 'SERRANO CASTILLO DORIS'],
    ]);
});

it('exporta solo la unidad de organizacion filtrada', function () {
    $sala = Unidad::factory()->create(['nombre_uni' => 'SALA CIVIL - CALLERIA']);
    $deLaSala = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'id_uni' => $sala->id_uni]);
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'id_uni' => Unidad::factory()]);
    Inscripcion::factory()->create(['id_pue' => $deLaSala->id_pue, 'documento_ins' => '71234567']);
    Inscripcion::factory()->create(['id_pue' => $otro->id_pue, 'documento_ins' => '72345678']);

    $respuesta = $this->actingAs($this->admin)
        ->get(route('seleccion.inscripciones.excel', ['proceso' => '002-2026-UE-UCAYALI', 'unidad' => $sala->id_uni]))
        ->assertOk()
        ->assertDownload('postulantes-002-2026-ue-ucayali-sala-civil-calleria.xlsx');

    expect(array_column(filasDelExcel($respuesta), 'DNI'))->toBe(['71234567']);
});

it('no acepta un puesto de otro proceso', function () {
    $ajeno = Puesto::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('seleccion.inscripciones.excel', ['proceso' => '002-2026-UE-UCAYALI', 'puesto' => $ajeno->id_pue]))
        ->assertNotFound();
});

it('niega la descarga a quien no tiene el permiso de exportar', function () {
    $usuario = Usuario::factory()->create(['id_rol' => Rol::factory()->con([Permiso::InscripcionesVer])]);

    $this->actingAs($usuario)
        ->get(route('seleccion.inscripciones.excel', ['proceso' => '002-2026-UE-UCAYALI']))
        ->assertForbidden();
});
