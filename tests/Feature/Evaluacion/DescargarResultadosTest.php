<?php

use App\Enums\CondicionResultado;
use App\Enums\Permiso;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\Unidad;
use App\Models\Usuario;
use App\Services\Evaluacion\FilaDeResultado;
use App\Services\Evaluacion\ResultadoDePuesto;
use App\Services\Evaluacion\ResultadoService;
use App\Services\Reportes\FuenteArial;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->proceso = Proceso::factory()->conPublicacion()->create(['codigo_pro' => '014-2026-UE-UCAYALI']);
    $this->puesto = Puesto::factory()->create([
        'id_pro' => $this->proceso->id_pro,
        'id_uni' => Unidad::factory()->create(['nombre_uni' => 'MÓDULO PENAL CENTRAL']),
        'codigo_pue' => '00309',
        'nombre_pue' => 'SECRETARIO JUDICIAL',
    ]);

    foreach (['GALVEZ DORADO LUCIA' => 26, 'ALARCON SIERRA MATEO' => 19] as $nombres => $aciertos) {
        Examen::factory()->create([
            'id_ins' => Inscripcion::factory()->create(['id_pue' => $this->puesto->id_pue, 'apellidos_nombres_ins' => $nombres]),
            'puntaje_exa' => $aciertos,
            'aciertos_exa' => $aciertos,
            'errores_exa' => 30 - $aciertos,
        ]);
    }
});

function descargarResultados(string $formato, array $filtros = [], ?Usuario $usuario = null)
{
    return test()->actingAs($usuario ?? Usuario::factory()->superAdministrador()->create())
        ->get(route('evaluacion.resultados.descargar', ['proceso' => test()->proceso->codigo_pro, 'formato' => $formato] + $filtros));
}

it('descarga el PDF del Anexo 07 de un puesto', function () {
    $respuesta = descargarResultados('pdf', ['puesto' => $this->puesto->id_pue]);

    $respuesta->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('resultados-evaluacion-tecnica-014-2026-ue-ucayali-00309.pdf');

    expect($respuesta->getContent())->toStartWith('%PDF');
});

it('arma el PDF con el orden de merito, la condicion y el pie de la publicacion', function () {
    $this->view('reportes.resultados-tecnica', [
        'proceso' => $this->proceso,
        'resultados' => app(ResultadoService::class)->porPuesto($this->proceso),
    ])
        ->assertSeeInOrder(['ANEXO N.° 07', 'SECRETARIO JUDICIAL', '00309', 'MÓDULO PENAL CENTRAL'])
        ->assertSeeInOrder(['GALVEZ DORADO LUCIA', '26', '17.33', '5.20', 'APTO'])
        ->assertSeeInOrder(['ALARCON SIERRA MATEO', '19', '12.67', '3.80', 'NO APTO', ResultadoService::NO_ALCANZO])
        ->assertSeeInOrder(['mayor o igual a 3,9 puntos', '28/09/2026', 'convocatorias@ejemplo.gob.pe'])
        ->assertSee('Pucallpa, 27 de Setiembre del año 2026')
        ->assertSee('El Comité de Selección de Personal Permanente');
});

it('descarga el Excel con una hoja por puesto en el formato del anexo', function () {
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00421']);
    Inscripcion::factory()->create(['id_pue' => $otro->id_pue]);

    $respuesta = descargarResultados('excel')
        ->assertOk()
        ->assertDownload('resultados-evaluacion-tecnica-014-2026-ue-ucayali.xlsx');

    $libro = IOFactory::load($respuesta->getFile()->getPathname());
    $hoja = $libro->getSheetByName('00309');

    expect($libro->getSheetNames())->toBe(['00309', '00421'])
        ->and($hoja?->getCell('B1')->getValue())->toBe('ANEXO N.° 07')
        ->and($hoja?->rangeToArray('B16:H17', formatData: false))->toBe([
            [1, 'GALVEZ DORADO LUCIA', 26, 17.33, 5.2, 'APTO', null],
            [2, 'ALARCON SIERRA MATEO', 19, 12.67, 3.8, 'NO APTO', ResultadoService::NO_ALCANZO],
        ]);
});

it('no descarga sin los datos de la publicacion', function () {
    $this->proceso->update(['correo_documentos_pro' => null]);

    descargarResultados('pdf')->assertUnprocessable();
});

it('no descarga sin el permiso de exportar', function () {
    $usuario = Usuario::factory()->create(['id_rol' => Rol::factory()->con([Permiso::ResultadosVer])]);

    descargarResultados('pdf', usuario: $usuario)->assertForbidden();
});

it('no descarga el puesto de otro proceso', function () {
    $ajeno = Puesto::factory()->create();

    descargarResultados('pdf', ['puesto' => $ajeno->id_pue])->assertNotFound();
});

it('no deja la firma ni el pie solos en una pagina del PDF', function (int $postulantes) {
    $filas = array_map(fn (int $numero): FilaDeResultado => new FilaDeResultado(
        numero: $numero,
        inscripcion: new Inscripcion(['documento_ins' => sprintf('7%07d', $numero), 'apellidos_nombres_ins' => "POSTULANTE {$numero} DE PRUEBA"]),
        nota: 10,
        notaParcial: 10 * 20 / 30,
        puntaje: 2.0,
        condicion: CondicionResultado::NoApto,
        observacion: ResultadoService::NO_ALCANZO,
        rindio: true,
        descalificado: false,
    ), range(1, $postulantes));

    $pagina = ['fila' => 0, 'filasEnLaUltima' => 0, 'pie' => 0, 'firma' => 0];
    $pdf = Pdf::loadView('reportes.resultados-tecnica', [
        'proceso' => $this->proceso,
        'resultados' => [new ResultadoDePuesto($this->puesto, $filas)],
    ])->setPaper('a4', 'landscape');

    $pdf->getDomPDF()->setCallbacks([[
        'event' => 'begin_frame',
        'f' => function ($frame, $canvas) use (&$pagina): void {
            $nodo = $frame->get_node();
            $numero = $canvas->get_page_number();

            if ($nodo->nodeName === 'tr' && $nodo->parentNode?->nodeName === 'tbody') {
                $pagina['filasEnLaUltima'] = $numero === $pagina['fila'] ? $pagina['filasEnLaUltima'] + 1 : 1;
                $pagina['fila'] = $numero;
            } elseif ($nodo instanceof DOMElement && in_array($nodo->getAttribute('class'), ['pie', 'firma'], true)) {
                $pagina[$nodo->getAttribute('class')] = $numero;
            }
        },
    ]]);
    $pdf->output();

    expect([$pagina['pie'], $pagina['firma']])->toBe([$pagina['fila'], $pagina['fila']])
        ->and($pagina['filasEnLaUltima'])->toBeGreaterThanOrEqual(min(4, $postulantes));
})->with([
    /*
     * Tamaños en los que, sin las reglas de salto de página, el cierre queda
     * mal. Cambian con la fuente: los PDF salen en Arial donde el servidor la
     * tiene y en Helvetica donde no (como en la integración continua).
     */
    'Arial: la firma sola en la segunda página' => 12,
    'Arial: el pie sin filas' => 15,
    'Arial: una sola fila en la última página' => 18,
    'Arial: la firma sola en la tercera página' => 45,
    'Helvetica: la firma sola en la segunda página' => 17,
    'Helvetica: el pie sin filas' => 20,
    'Helvetica: una sola fila en la última página' => 23,
    'Helvetica: la firma sola en la tercera página' => 56,
]);

it('descarga el PDF en Arial cuando el servidor la tiene instalada', function () {
    $respuesta = descargarResultados('pdf', ['puesto' => $this->puesto->id_pue])->assertOk();

    expect($respuesta->getContent())->toContain('+ArialMT')->toContain('+Arial-BoldMT');
})->skip(fn () => app(FuenteArial::class)->ubicar() === null, 'El equipo no tiene Arial instalada; el PDF sale en Helvetica.');
